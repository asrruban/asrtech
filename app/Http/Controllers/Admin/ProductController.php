<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Models\Category;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\ProductType;
use App\Services\ProductReleaseFileService;
use App\Services\ProductReleaseNotificationService;
use App\Services\ProductService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly StorageService $storage,
        private readonly ProductReleaseFileService $releaseFiles,
        private readonly ProductReleaseNotificationService $releaseNotifications,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        return Inertia::render('Admin/Catalog/Products/Index', [
            'filters' => ['search' => $search],
            'products' => Product::query()
                ->with([
                    'category:id,name',
                    'group:id,name',
                    'productType:id,name,key,slug',
                    'prices',
                ])
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                }))
                ->latest()
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Catalog/Products/Create', $this->formOptions());
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        $data = $request->productData();

        if ($request->hasFile('featured_image_upload')) {
            $data['featured_image'] = $this->storage->storeUpload(
                $request->file('featured_image_upload'),
                'products',
            );
        }

        /** @var array{disk: string, file_path: string}|null $storedReleaseFile */
        $storedReleaseFile = null;
        $release = null;

        try {
            DB::transaction(function () use ($request, $data, &$storedReleaseFile, &$release): void {
                $product = $this->products->execute(
                    new Product,
                    $data,
                    $request->prices(),
                    $request->seoData(),
                );

                $metadata = $request->initialReleaseMetadata();
                $file = $request->file('initial_release.file');

                if ($metadata === null || ! $file instanceof UploadedFile) {
                    return;
                }

                $storedReleaseFile = $this->releaseFiles->store($product, $file);
                $release = $product->releases()->create([
                    ...$metadata,
                    ...$storedReleaseFile,
                ]);
                $product->update([
                    'version' => $metadata['version'],
                    'release_date' => $metadata['released_at'],
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedReleaseFile !== null) {
                $this->releaseFiles->deleteStoredFile($storedReleaseFile);
            }

            throw $exception;
        }

        if ($release instanceof ProductRelease) {
            try {
                $this->releaseNotifications->schedule($release);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        $product->load(['prices', 'seo']);

        return Inertia::render('Admin/Catalog/Products/Edit', [
            ...$this->formOptions($product),
            'product' => $product,
        ]);
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->productData();

        if ($request->hasFile('featured_image_upload')) {
            $data['featured_image'] = $this->storage->storeUpload(
                $request->file('featured_image_upload'),
                'products',
            );
        }

        $this->products->execute(
            $product,
            $data,
            $request->prices(),
            $request->seoData(),
        );

        return redirect()->route('admin.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index');
    }

    /** @return array{categories: mixed, groups: mixed, productTypes: mixed, billingCycles: list<string>, currency: string} */
    private function formOptions(?Product $product = null): array
    {
        return [
            'categories' => Category::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'groups' => Group::query()->where('status', true)->orderBy('name')->get(['id', 'category_id', 'name']),
            'productTypes' => ProductType::query()
                ->where(function ($query) use ($product) {
                    $query->where('status', true)
                        ->when($product, fn ($query) => $query->orWhere('key', $product->type));
                })
                ->orderBy('name')
                ->get(['name', 'key', 'slug']),
            'billingCycles' => BillingCycle::values(),
            'currency' => (string) config('app.currency', 'USD'),
        ];
    }
}
