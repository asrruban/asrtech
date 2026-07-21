<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\GatewayRegistry;
use App\Payments\PaymentResult;
use App\Payments\PurchaseOutcome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly SubscriptionService $subscriptions,
        private readonly CommercePricingService $pricing,
    ) {}

    /**
     * Create an order for the given plan and charge it through the
     * chosen enabled gateway. Instant gateways mark the order paid
     * here; redirect gateways leave it pending until their callback
     * confirms the payment.
     */
    public function purchase(User $user, Product $product, ProductPrice $price, ?string $gatewayKey = null): PurchaseOutcome
    {
        $price->setRelation('product', $product);

        return $this->purchaseCart($user, collect([$price]), $gatewayKey);
    }

    /**
     * Create one payable order containing every selected cart plan.
     *
     * @param  Collection<int, ProductPrice>  $prices
     */
    public function purchaseCart(User $user, Collection $prices, ?string $gatewayKey = null, ?string $promotionCode = null): PurchaseOutcome
    {
        $prices = $prices->values();

        if ($prices->isEmpty()) {
            throw new InvalidArgumentException('Checkout requires at least one cart item.');
        }

        $prices->each(fn (ProductPrice $price): ProductPrice => $price->loadMissing('product'));

        if ($prices->pluck('currency')->unique()->count() !== 1) {
            throw new InvalidArgumentException('All cart items must use the same currency.');
        }

        if ($prices->where('billing_cycle', '!=', BillingCycle::OneTime)->count() > 1) {
            throw new InvalidArgumentException('Checkout supports one recurring subscription plan at a time.');
        }

        if ($gatewayKey === 'free_trial') {
            foreach ($prices as $price) {
                $product = $price->product;
                if (! $product->has_free_trial) {
                    throw new InvalidArgumentException("Product {$product->name} does not offer a free trial.");
                }
                if ($user->hasUsedFreeTrial($product)) {
                    throw new InvalidArgumentException("You have already used the free trial for {$product->name}.");
                }
            }
        }

        $gateway = null;
        if ($gatewayKey === 'free_trial') {
            $gateway = $this->gateways->find('free_trial');
        }
        $gateway ??= ($gatewayKey === null ? null : $this->gateways->findEnabled($gatewayKey))
            ?? $this->gateways->default();
        $first = $prices->first();

        if (! $first instanceof ProductPrice) {
            throw new InvalidArgumentException('Checkout requires a valid cart item.');
        }

        $order = DB::transaction(function () use ($user, $prices, $first, $gatewayKey, $promotionCode): Order {
            $isFreeTrial = ($gatewayKey === 'free_trial');
            $quote = $isFreeTrial
                ? null
                : $this->pricing->quote($prices, $user, $promotionCode, true);
            $order = Order::query()->create([
                'user_id' => $user->id,
                'product_id' => $first->product_id,
                'product_price_id' => $first->id,
                'order_number' => $this->generateOrderNumber(),
                'currency' => $first->currency,
                'promotion_code_id' => $quote?->promotion?->id,
                'promotion_code' => $quote?->promotion?->code,
                'subtotal' => $isFreeTrial ? 0 : $quote->subtotal,
                'amount' => $quote ? $quote->subtotal - $quote->discountAmount : 0,
                'discount_amount' => $isFreeTrial ? 0 : $quote->discountAmount,
                'setup_fee' => $isFreeTrial ? 0 : $quote->setupFee,
                'tax_amount' => $isFreeTrial ? 0 : $quote->taxAmount,
                'tax_rate' => $quote?->taxRate?->rate,
                'tax_name' => $quote?->taxRate?->name,
                'billing_cycle' => $first->billing_cycle,
                'status' => OrderStatus::Pending,
            ]);

            $order->items()->createMany($prices->map(fn (ProductPrice $price): array => [
                'product_id' => $price->product_id,
                'product_price_id' => $price->id,
                'product_name' => $price->product->name.($isFreeTrial ? ' (7 Days Free Trial)' : ''),
                'price_name' => $price->name,
                'currency' => $price->currency,
                'amount' => $isFreeTrial ? 0 : ($price->sale_price ?? $price->price),
                'setup_fee' => $isFreeTrial ? 0 : ($price->setup_fee ?? 0),
                'billing_cycle' => $price->billing_cycle,
            ])->all());

            if ($quote?->promotion) {
                $order->promotionRedemption()->create([
                    'promotion_code_id' => $quote->promotion->id,
                    'user_id' => $user->id,
                    'discount_amount' => $quote->discountAmount,
                    'status' => 'reserved',
                ]);
            }

            return $order->load('items');
        });

        $result = $order->totalAmount() <= 0 && $gatewayKey !== 'free_trial'
            ? PaymentResult::success('promotion', 'PROMO-'.$order->order_number)
            : $gateway->charge($order);

        if ($result->needsRedirect()) {
            $order->update([
                'payment_method' => $result->method,
                'payment_reference' => $result->reference,
            ]);

            return new PurchaseOutcome($order->refresh(), $result);
        }

        if (! $result->successful) {
            $order->update([
                'status' => OrderStatus::Failed,
                'payment_method' => $result->method,
            ]);
            $order->promotionRedemption()->where('status', 'reserved')->delete();

            return new PurchaseOutcome($order->refresh(), $result);
        }

        return new PurchaseOutcome(
            $this->markPaid($order, $result->method, $result->reference),
            $result,
        );
    }

    /**
     * Idempotently mark an order paid and provision license and
     * invoice through the OrderPaid event. Called by instant gateways
     * and by gateway callbacks once an async payment settles.
     */
    public function markPaid(Order $order, string $method, ?string $reference = null): Order
    {
        if ($order->status === OrderStatus::Paid) {
            return $order;
        }

        $order->update([
            'status' => OrderStatus::Paid,
            'payment_method' => $method,
            'payment_reference' => $reference ?? $order->payment_reference,
            'paid_at' => now(),
        ]);

        $order->transactions()->create([
            'type' => 'payment',
            'gateway' => $method,
            'reference' => $reference ?? $order->payment_reference,
            'amount' => $order->totalAmount(),
            'description' => "Payment for order {$order->order_number}",
        ]);

        $order->promotionRedemption()->where('status', 'reserved')->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);

        OrderPaid::dispatch($order->refresh());

        if ($order->subscription_id !== null) {
            $subscription = Subscription::find($order->subscription_id);
            if ($subscription) {
                $periodStart = now();
                $periodEnd = match ($subscription->billing_cycle) {
                    BillingCycle::Monthly => $periodStart->copy()->addMonth(),
                    BillingCycle::Yearly => $periodStart->copy()->addYear(),
                    BillingCycle::OneTime => null,
                };

                $subscription->update([
                    'gateway' => $method,
                    'status' => SubscriptionStatus::Active,
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'last_payment_at' => now(),
                ]);

                if ($subscription->license) {
                    $subscription->license->update([
                        'status' => LicenseStatus::Active,
                        'expires_at' => $periodEnd,
                    ]);
                }
            }
        } else {
            $this->subscriptions->activateForPaidOrder($order->refresh());
        }

        return $order->refresh();
    }

    /**
     * Create an order on a customer's behalf from the admin panel.
     * Complimentary orders are zero-amount and marked paid, which
     * provisions the license without charging the customer.
     */
    public function manual(
        User $user,
        Product $product,
        ?ProductPrice $price,
        bool $markPaid,
        bool $complimentary = false,
    ): Order {
        $baseAmount = (float) ($price->sale_price ?? $price->price ?? $product->price);
        $setupFee = (float) ($price->setup_fee ?? 0);
        $order = DB::transaction(fn (): Order => Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_price_id' => $price?->id,
            'order_number' => $this->generateOrderNumber(),
            'currency' => $price->currency ?? (string) config('asrtech.currency', 'USD'),
            'subtotal' => $complimentary ? 0 : $baseAmount,
            'amount' => $complimentary ? 0 : $baseAmount,
            'discount_amount' => 0,
            'setup_fee' => $complimentary ? 0 : $setupFee,
            'tax_amount' => 0,
            'billing_cycle' => $price->billing_cycle ?? BillingCycle::OneTime,
            'status' => ($markPaid || $complimentary) ? OrderStatus::Paid : OrderStatus::Pending,
            'payment_method' => $complimentary ? 'complimentary' : 'manual',
            'paid_at' => ($markPaid || $complimentary) ? now() : null,
        ]));

        if ($order->status === OrderStatus::Paid) {
            OrderPaid::dispatch($order);
            $this->subscriptions->activateForPaidOrder($order->refresh());
        }

        return $order->refresh();
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
