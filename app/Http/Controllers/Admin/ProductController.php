<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Models\Category;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\ProductService;
use App\Services\StorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly StorageService $storage,
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

        $this->products->execute(
            new Product,
            $data,
            $request->prices(),
            $request->seoData(),
        );

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
