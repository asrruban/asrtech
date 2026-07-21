<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductReleaseRequest;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Services\ProductReleaseFileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProductReleaseController extends Controller
{
    public function __construct(private readonly ProductReleaseFileService $files) {}

    public function index(Product $product): Response
    {
        $product->load('productType:id,name,key,slug');

        return Inertia::render('Admin/Catalog/ProductReleases/Index', [
            'product' => [
                ...$product->only(['id', 'name', 'slug', 'version']),
                'url' => $product->storefrontUrl(),
            ],
            'releases' => $product->releases()
                ->withCount('downloads')
                ->orderByDesc('released_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (ProductRelease $release): array => [
                    'id' => $release->id,
                    'version' => $release->version,
                    'title' => $release->title,
                    'release_notes' => $release->release_notes,
                    'original_filename' => $release->original_filename,
                    'mime_type' => $release->mime_type,
                    'file_size' => $release->file_size,
                    'checksum_sha256' => $release->checksum_sha256,
                    'released_at' => $release->released_at,
                    'available_until' => $release->available_until,
                    'download_limit' => $release->download_limit,
                    'downloads_count' => $release->downloads_count,
                    'status' => $release->status,
                ]),
        ]);
    }

    public function store(SaveProductReleaseRequest $request, Product $product): RedirectResponse
    {
        $storedFile = $this->files->store($product, $request->file('release_file'));

        try {
            $product->releases()->create([
                ...$request->metadata(),
                ...$storedFile,
            ]);
        } catch (Throwable $exception) {
            $this->files->deleteStoredFile($storedFile);

            throw $exception;
        }

        $this->syncLatestVersion($product);
        $this->flash('Release uploaded successfully.');

        return redirect()->route('admin.products.releases.index', $product);
    }

    public function update(
        SaveProductReleaseRequest $request,
        Product $product,
        ProductRelease $release,
    ): RedirectResponse {
        $this->ensureReleaseBelongsToProduct($product, $release);
        $storedFile = null;

        if ($request->hasFile('release_file')) {
            $storedFile = $this->files->store($product, $request->file('release_file'));
        }

        $oldFile = [
            'disk' => $release->disk,
            'file_path' => $release->file_path,
        ];

        try {
            $release->update([
                ...$request->metadata(),
                ...($storedFile ?? []),
            ]);
        } catch (Throwable $exception) {
            if ($storedFile !== null) {
                $this->files->deleteStoredFile($storedFile);
            }

            throw $exception;
        }

        if ($storedFile !== null) {
            $this->files->deleteStoredFile($oldFile);
        }

        $this->syncLatestVersion($product);
        $this->flash('Release updated successfully.');

        return redirect()->route('admin.products.releases.index', $product);
    }

    public function destroy(Product $product, ProductRelease $release): RedirectResponse
    {
        $this->ensureReleaseBelongsToProduct($product, $release);
        $this->files->delete($release);
        $release->delete();
        $this->syncLatestVersion($product);
        $this->flash('Release deleted successfully.');

        return redirect()->route('admin.products.releases.index', $product);
    }

    private function ensureReleaseBelongsToProduct(Product $product, ProductRelease $release): void
    {
        abort_unless($release->product_id === $product->id, 404);
    }

    private function syncLatestVersion(Product $product): void
    {
        $latest = $product->releases()
            ->where('status', true)
            ->where('released_at', '<=', now())
            ->orderByDesc('released_at')
            ->orderByDesc('id')
            ->first();

        $product->update([
            'version' => $latest?->version,
            'release_date' => $latest?->released_at?->toDateString(),
        ]);
    }

    private function flash(string $message): void
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $message,
        ]);
    }
}
