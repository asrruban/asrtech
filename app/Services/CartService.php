<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CartService
{
    private const SESSION_KEY = 'storefront.cart.price_ids';

    private const PROMOTION_KEY = 'storefront.cart.promotion_code';

    public function __construct(private readonly CommercePricingService $pricing) {}

    /** @return list<int> */
    public function ids(): array
    {
        $ids = request()->session()->get(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id): bool => $id > 0,
        )));
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function add(Product $product, ProductPrice $price): void
    {
        $items = $this->items();
        $currency = $items->first()?->currency;

        if ($currency !== null && $currency !== $price->currency) {
            throw ValidationException::withMessages([
                'cart' => __('All cart items must use the same currency.'),
            ]);
        }

        $ids = $items
            ->reject(fn (ProductPrice $item): bool => $item->product_id === $product->id)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->values()
            ->all();
        $ids[] = $price->id;

        request()->session()->put(self::SESSION_KEY, $ids);
    }

    public function remove(int $priceId): void
    {
        request()->session()->put(self::SESSION_KEY, array_values(array_filter(
            $this->ids(),
            fn (int $id): bool => $id !== $priceId,
        )));
    }

    public function clear(): void
    {
        request()->session()->forget([self::SESSION_KEY, self::PROMOTION_KEY]);
    }

    public function promotionCode(): ?string
    {
        $code = request()->session()->get(self::PROMOTION_KEY);

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function applyPromotion(string $code): void
    {
        $items = $this->items();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['code' => __('Add a product before applying a promotion code.')]);
        }

        try {
            $this->pricing->quote($items, $this->user(), $code);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }

        request()->session()->put(self::PROMOTION_KEY, strtoupper(trim($code)));
    }

    public function removePromotion(): void
    {
        request()->session()->forget(self::PROMOTION_KEY);
    }

    /** @return Collection<int, ProductPrice> */
    public function items(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return collect();
        }

        $prices = ProductPrice::query()
            ->whereKey($ids)
            ->where('enabled', true)
            ->whereHas('product', fn ($query) => $query->where('status', true))
            ->with([
                'product:id,name,slug,type,featured_image,status',
                'product.productType:id,key,slug',
            ])
            ->get()
            ->keyBy('id');

        $items = collect($ids)
            ->map(fn (int $id): ?ProductPrice => $prices->get($id))
            ->filter()
            ->values();

        if ($items->count() !== count($ids)) {
            request()->session()->put(self::SESSION_KEY, $items->pluck('id')->all());
        }

        return $items;
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $items = $this->items();
        $serializedItems = [];

        foreach ($items as $price) {
            $serializedItems[] = [
                'id' => $price->id,
                'billing_cycle' => $price->billing_cycle->value,
                'name' => $price->name,
                'description' => $price->description,
                'currency' => $price->currency,
                'price' => $price->price,
                'sale_price' => $price->sale_price,
                'setup_fee' => $price->setup_fee,
                'amount' => $price->sale_price ?? $price->price,
                'product' => [
                    'name' => $price->product->name,
                    'slug' => $price->product->slug,
                    'url' => $price->product->storefrontUrl(),
                    'featured_image' => $price->product->featured_image,
                ],
            ];
        }

        $summary = [
            'items' => $serializedItems,
            'currency' => $items->first()?->currency,
            'subtotal' => '0.00',
            'setup_fee' => '0.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total' => '0.00',
            'promotion' => null,
            'promotion_error' => null,
            'tax' => null,
            'tax_pending' => true,
        ];

        if ($items->isEmpty()) {
            return $summary;
        }

        try {
            $quote = $this->pricing->quote($items, $this->user(), $this->promotionCode());
        } catch (InvalidArgumentException $exception) {
            $summary['promotion_error'] = $exception->getMessage();
            $quote = $this->pricing->quote($items, $this->user());
        }

        return [...$summary, ...[
            'subtotal' => number_format($quote->subtotal, 2, '.', ''),
            'setup_fee' => number_format($quote->setupFee, 2, '.', ''),
            'discount_amount' => number_format($quote->discountAmount, 2, '.', ''),
            'tax_amount' => number_format($quote->taxAmount, 2, '.', ''),
            'total' => number_format($quote->total, 2, '.', ''),
            'promotion' => $quote->promotion ? [
                'code' => $quote->promotion->code,
                'name' => $quote->promotion->name,
            ] : null,
            'tax' => $quote->taxRate ? [
                'name' => $quote->taxRate->name,
                'rate' => $quote->taxRate->rate,
            ] : null,
            'tax_pending' => $quote->taxPending,
        ]];
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
