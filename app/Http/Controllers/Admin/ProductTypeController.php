<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductTypeRequest;
use App\Models\ProductType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Catalog/ProductTypes/Index', [
            'productTypes' => ProductType::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(SaveProductTypeRequest $request): RedirectResponse
    {
        $data = $request->payload();

        ProductType::query()->create([
            ...$data,
            'key' => $this->uniqueKey($data['slug']),
        ]);

        return redirect()->route('admin.product-types.index');
    }

    public function update(SaveProductTypeRequest $request, ProductType $productType): RedirectResponse
    {
        $productType->update($request->payload());

        return redirect()->route('admin.product-types.index');
    }

    public function destroy(ProductType $productType): RedirectResponse
    {
        if ($productType->products()->exists()) {
            throw ValidationException::withMessages([
                'product_type' => 'Move or delete this product type’s products first.',
            ]);
        }

        $productType->delete();

        return redirect()->route('admin.product-types.index');
    }

    private function uniqueKey(string $slug): string
    {
        $base = Str::of($slug)->replace('-', '_')->toString();
        $key = $base;
        $counter = 2;

        while (ProductType::query()->where('key', $key)->exists()) {
            $key = "{$base}_{$counter}";
            $counter++;
        }

        return $key;
    }
}
