<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Arr;
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

            $replaceCompatibility = array_key_exists('compatibility_ranges', $data);
            $compatibilityRanges = Arr::pull($data, 'compatibility_ranges', []);
            $product->fill($data)->save();

            if ($replaceCompatibility) {
                $product->compatibilityRanges()->delete();
                $product->compatibilityRanges()->createMany($compatibilityRanges);
            }
            // Preserve price identities used by subscriptions and maintenance agreements.
            $cycles = array_column($prices, 'billing_cycle');
            $product->prices()->whereNotIn('billing_cycle', $cycles)->update(['enabled' => false]);
            foreach ($prices as $price) {
                $product->prices()->updateOrCreate(['billing_cycle' => $price['billing_cycle']], $price);
            }
            $product->seo()->updateOrCreate([], $seo);

            return $product->refresh()->load(['prices', 'seo']);
        });
    }
}
