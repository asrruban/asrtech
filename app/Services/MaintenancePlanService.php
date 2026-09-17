<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\SubscriptionStatus;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\ProductPrice;
use App\Models\Quote;
use App\Models\Subscription;
use Illuminate\Validation\ValidationException;

class MaintenancePlanService
{
    public function availablePrice(?ProductPrice $price): bool
    {
        return $price !== null && $price->enabled && $price->product?->status
            && in_array($price->billing_cycle, [BillingCycle::Monthly, BillingCycle::Yearly], true)
            && ! filled($price->purchase_url);
    }

    /** @return array<string, mixed> */
    public function planPayload(MaintenancePlan $plan): array
    {
        $price = $plan->productPrice;
        $billing = $price === null ? null : [
            'price_id' => $price->id,
            'product_id' => $price->product_id,
            'amount' => $price->sale_price ?? $price->price,
            'currency' => $price->currency,
            'cycle' => $price->billing_cycle->value,
            'setup_fee' => $price->setup_fee ?? '0.00',
        ];

        return [
            'id' => $plan->id, 'name' => $plan->name, 'slug' => $plan->slug,
            'platform' => $plan->platform, 'summary' => $plan->summary,
            'scope' => $plan->scope, 'exclusions' => $plan->exclusions,
            'support_arrangements' => $plan->support_arrangements,
            'published' => $plan->published, 'product_price_id' => $plan->product_price_id,
            'version' => hash('sha256', json_encode($plan->only(['name', 'slug', 'platform', 'summary', 'scope', 'exclusions', 'support_arrangements', 'product_price_id', 'published'])).json_encode($billing)),
            'billing' => $billing,
            'available' => $plan->published && ($price === null || $this->availablePrice($price)),
        ];
    }

    /** @return array<string, mixed> */
    public function requestPayload(MaintenanceRequest $request, bool $admin = false): array
    {
        $quote = $request->quote;
        $visibleQuote = $quote !== null && ($admin || $quote->status !== QuoteStatus::Draft);
        $subscription = $request->subscription ?? ($request->checkout_order_id
            ? Subscription::query()->where('order_id', $request->checkout_order_id)->where('user_id', $request->user_id)->first()
            : null);

        return [
            'id' => $request->id, 'plan' => $request->plan_snapshot,
            'website' => $request->website, 'requirements' => $request->requirements,
            'status' => $request->status, 'client_update' => $request->client_update,
            'created_at' => $request->created_at?->toIso8601String(),
            'scope_acknowledged_at' => $request->scope_acknowledged_at->toIso8601String(),
            'subscription' => $subscription === null ? null : [
                'id' => $subscription->id, 'status' => $subscription->status->value,
                'url' => route('account.subscriptions.show', $subscription),
            ],
            'quote' => $visibleQuote ? ['id' => $quote->id, 'number' => $quote->quote_number, 'status' => $quote->status->value, 'url' => route('account.quotes.show', $quote)] : null,
            'checkout_url' => $this->canCheckout($request) ? route('account.maintenance.checkout', $request) : null,
            'checkout_order' => $request->checkoutOrder?->only(['order_number', 'status']),
            'checkout_resume_url' => $request->checkoutOrder?->status === OrderStatus::Pending ? $request->checkout_redirect_url : null,
            ...($admin ? ['internal_notes' => $request->internal_notes, 'subscription_id' => $subscription?->id, 'quote_id' => $request->quote_id, 'user' => $request->user->only(['id', 'name', 'email'])] : []),
        ];
    }

    public function canCheckout(MaintenanceRequest $request): bool
    {
        try {
            $this->checkoutPrice($request);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    public function checkoutPrice(MaintenanceRequest $request, bool $lock = false): ProductPrice
    {
        if ($request->status !== 'awaiting_payment' || $request->subscription_id !== null
            || ($request->checkoutOrder !== null && $request->checkoutOrder->status !== OrderStatus::Failed)) {
            throw ValidationException::withMessages(['checkout' => 'This request is not ready for a new payment. Check its billing status or contact support.']);
        }

        $priceId = data_get($request->plan_snapshot, 'billing.price_id');
        $price = $priceId ? ProductPrice::query()->when($lock, fn ($query) => $query->lockForUpdate())->with('product')->whereKey($priceId)->first() : null;
        if (! $price instanceof ProductPrice || ! $this->availablePrice($price)
            || (float) ($price->sale_price ?? $price->price) !== (float) data_get($request->plan_snapshot, 'billing.amount')
            || $price->currency !== data_get($request->plan_snapshot, 'billing.currency')
            || $price->billing_cycle->value !== data_get($request->plan_snapshot, 'billing.cycle')
            || (float) $price->setup_fee !== (float) data_get($request->plan_snapshot, 'billing.setup_fee')) {
            throw ValidationException::withMessages(['checkout' => 'The available price no longer matches your agreed plan. Contact support before paying.']);
        }

        if (MaintenanceRequest::query()->whereKeyNot($request->id)->where('user_id', $request->user_id)
            ->whereHas('checkoutOrder', fn ($query) => $query->where('product_price_id', $price->id)->where('status', OrderStatus::Pending))->exists()) {
            throw ValidationException::withMessages(['checkout' => 'Another maintenance request already has a pending payment for this price. Contact support before paying again.']);
        }

        if (Subscription::query()->where('user_id', $request->user_id)->where('product_price_id', $price->id)->where('status', '!=', SubscriptionStatus::Canceled->value)->exists()) {
            throw ValidationException::withMessages(['checkout' => 'You already have a subscription for this price. Contact support to link it to this request.']);
        }

        return $price;
    }

    public function validateBilling(MaintenanceRequest $request, ?Subscription $subscription, ?Quote $quote, bool $activating): void
    {
        if ($subscription !== null && ((int) $subscription->user_id !== (int) $request->user_id
            || (int) $subscription->product_price_id !== (int) data_get($request->plan_snapshot, 'billing.price_id'))) {
            throw ValidationException::withMessages(['subscription_id' => 'Choose this customer’s subscription for the agreed maintenance price.']);
        }
        if ($subscription !== null && ($subscription->currency !== data_get($request->plan_snapshot, 'billing.currency')
            || (float) $subscription->amount !== (float) data_get($request->plan_snapshot, 'billing.amount')
            || $subscription->billing_cycle->value !== data_get($request->plan_snapshot, 'billing.cycle'))) {
            throw ValidationException::withMessages(['subscription_id' => 'This subscription does not match the agreed billing details.']);
        }
        if ($quote !== null && ((int) $quote->user_id !== (int) $request->user_id || data_get($request->plan_snapshot, 'billing') !== null)) {
            throw ValidationException::withMessages(['quote_id' => 'Choose this customer’s quote for a plan with custom pricing.']);
        }
        if ($activating && ! ($subscription?->status->providesAccess()
            || ($quote?->status === QuoteStatus::Converted && $quote->order?->status === OrderStatus::Paid
                && $quote->order->user_id === $request->user_id
                && $quote->order->currency === $quote->currency
                && $quote->order->totalAmount() === (float) $quote->total))) {
            throw ValidationException::withMessages(['status' => 'Activation requires a matching active subscription or an accepted, paid custom quote.']);
        }
    }
}
