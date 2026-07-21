<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use App\Models\ProductPrice;
use App\Models\PromotionCode;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CommercePricingService
{
    /**
     * @param  Collection<int, ProductPrice>  $prices
     */
    public function quote(Collection $prices, ?User $user = null, ?string $promotionCode = null, bool $lockPromotion = false): PricingQuote
    {
        if ($prices->isEmpty()) {
            throw new InvalidArgumentException('Pricing requires at least one item.');
        }

        $prices->each(fn (ProductPrice $price): ProductPrice => $price->loadMissing('product'));
        $currencies = $prices->pluck('currency')->unique();
        if ($currencies->count() !== 1) {
            throw new InvalidArgumentException('All cart items must use the same currency.');
        }

        $currency = (string) $currencies->first();
        $subtotal = $this->round($prices->sum(fn (ProductPrice $price): float => (float) ($price->sale_price ?? $price->price)));
        $setupFee = $this->round($prices->sum(fn (ProductPrice $price): float => (float) ($price->setup_fee ?? 0)));
        $promotion = null;
        $discount = 0.0;

        if (filled($promotionCode)) {
            $query = PromotionCode::query()->with('products:id');
            if ($lockPromotion) {
                $query->lockForUpdate();
            }

            $promotion = $query->whereRaw('UPPER(code) = ?', [strtoupper(trim((string) $promotionCode))])->first();
            if (! $promotion) {
                throw new InvalidArgumentException('This promotion code is not valid.');
            }

            $discount = $this->promotionDiscount($promotion, $prices, $user, $currency);
        }

        $taxRate = $this->matchingTaxRate($user);
        $taxable = max(0, $subtotal - $discount) + $setupFee;
        $tax = $taxRate ? $this->round($taxable * (float) $taxRate->rate / 100) : 0.0;
        $taxPending = ($user === null || blank($user->country))
            && TaxRate::query()->where('active', true)->exists();

        return new PricingQuote(
            $currency,
            $subtotal,
            $setupFee,
            $discount,
            $tax,
            $this->round($taxable + $tax),
            $promotion,
            $taxRate,
            $taxPending,
        );
    }

    /** @param Collection<int, ProductPrice> $prices */
    private function promotionDiscount(PromotionCode $promotion, Collection $prices, ?User $user, string $currency): float
    {
        if (! $promotion->active || ($promotion->starts_at && $promotion->starts_at->isFuture()) || ($promotion->ends_at && $promotion->ends_at->isPast())) {
            throw new InvalidArgumentException('This promotion code is not currently available.');
        }

        if ($promotion->discount_type === PromotionDiscountType::Percentage && (float) $promotion->value > 100) {
            throw new InvalidArgumentException('This promotion code has an invalid discount.');
        }

        if ($promotion->discount_type === PromotionDiscountType::Fixed && strtoupper((string) $promotion->currency) !== strtoupper($currency)) {
            throw new InvalidArgumentException("This promotion code is not available for {$currency} orders.");
        }

        $productIds = $promotion->products->pluck('id')->map(fn (int $id): int => $id);
        $eligible = $prices->filter(function (ProductPrice $price) use ($promotion, $productIds): bool {
            $scopeMatches = match ($promotion->scope) {
                PromotionScope::All => true,
                PromotionScope::OneTime => $price->billing_cycle === BillingCycle::OneTime,
                PromotionScope::Recurring => $price->billing_cycle !== BillingCycle::OneTime,
            };

            return $scopeMatches && ($productIds->isEmpty() || $productIds->contains($price->product_id));
        });
        $eligibleSubtotal = $this->round($eligible->sum(fn (ProductPrice $price): float => (float) ($price->sale_price ?? $price->price)));

        if ($eligibleSubtotal <= 0) {
            throw new InvalidArgumentException('This promotion code does not apply to the products in your cart.');
        }

        if ($promotion->minimum_subtotal !== null && $eligibleSubtotal < (float) $promotion->minimum_subtotal) {
            throw new InvalidArgumentException('Your eligible subtotal does not meet this promotion code’s minimum.');
        }

        $usedQuery = $promotion->redemptions()->where(function ($query) {
            $query->where('status', 'redeemed')
                ->orWhere(fn ($query) => $query
                    ->where('status', 'reserved')
                    ->where('created_at', '>=', now()->subDay()));
        });
        if ($promotion->usage_limit !== null && (clone $usedQuery)->count() >= $promotion->usage_limit) {
            throw new InvalidArgumentException('This promotion code has reached its usage limit.');
        }
        if ($user && $promotion->per_customer_limit !== null && (clone $usedQuery)->where('user_id', $user->id)->count() >= $promotion->per_customer_limit) {
            throw new InvalidArgumentException('You have already used this promotion code.');
        }

        $discount = $promotion->discount_type === PromotionDiscountType::Percentage
            ? $eligibleSubtotal * (float) $promotion->value / 100
            : (float) $promotion->value;
        $discount = min($eligibleSubtotal, $discount);
        if ($promotion->maximum_discount !== null) {
            $discount = min($discount, (float) $promotion->maximum_discount);
        }

        return $this->round($discount);
    }

    private function matchingTaxRate(?User $user): ?TaxRate
    {
        if (! $user || blank($user->country)) {
            return null;
        }

        $country = strtoupper((string) $user->country);
        $state = strtoupper(trim((string) $user->state));

        return TaxRate::query()
            ->where('active', true)
            ->where(fn ($query) => $query->whereNull('country_code')->orWhere('country_code', $country))
            ->get()
            ->filter(fn (TaxRate $rate): bool => blank($rate->state) || strtoupper(trim((string) $rate->state)) === $state)
            ->sortByDesc(fn (TaxRate $rate): string => sprintf(
                '%d%d%010d',
                filled($rate->country_code) ? 1 : 0,
                filled($rate->state) ? 1 : 0,
                $rate->priority,
            ))
            ->first();
    }

    private function round(float $amount): float
    {
        return round($amount, 2, PHP_ROUND_HALF_UP);
    }
}
