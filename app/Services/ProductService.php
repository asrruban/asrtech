<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductService
{
    public function __construct(private readonly SlugService $slugs) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $prices
     * @param  array<string, mixed>  $seo
     */
    public function execute(Product $product, array $data, array $prices, array $seo): Product
    {
        return DB::transaction(function () use ($product, $data, $prices, $seo): Product {
            $data['slug'] = filled($data['slug'] ?? null)
                ? (string) $data['slug']
                : $this->slugs->generate($product, (string) $data['name'], 'product');

            $enabledPrices = array_filter($prices, fn (array $price): bool => (bool) $price['enabled']);
            $referencePrices = $enabledPrices !== [] ? $enabledPrices : $prices;

            if ($referencePrices === []) {
                throw new InvalidArgumentException('At least one product price is required.');
            }

            $data['price'] = min(array_map(
                fn (array $price): float => (float) ($price['sale_price'] ?? $price['price']),
                $referencePrices,
            ));

            $product->fill($data)->save();
            $product->prices()->delete();
            $product->prices()->createMany($prices);
            $product->seo()->updateOrCreate([], $seo);

            return $product->refresh()->load(['prices', 'seo']);
        });
    }
}
