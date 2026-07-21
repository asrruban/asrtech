<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->trim()->toString();
        $search = $request->string('search')->trim()->toString();

        if ($status !== '') {
            validator(['status' => $status], ['status' => [Rule::enum(SubscriptionStatus::class)]])->validate();
        }

        return Inertia::render('Admin/Subscriptions/Index', [
            'filters' => compact('status', 'search'),
            'statusOptions' => SubscriptionStatus::values(),
            'subscriptions' => Subscription::query()
                ->with([
                    'user:id,name,email',
                    'product:id,name',
                    'license:id,license_key,status',
                    'latestPaymentFailure',
                ])
                ->withCount([
                    'renewalOrders',
                    'events as failed_payments_count' => fn ($query) => $query
                        ->where('event_type', 'invoice.payment_failed'),
                ])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('gateway_subscription_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                }))
                ->latest()
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString()
                ->through(fn (Subscription $subscription): array => [
                    'id' => $subscription->id,
                    'status' => $subscription->status->value,
                    'billing_cycle' => $subscription->billing_cycle->value,
                    'currency' => $subscription->currency,
                    'amount' => $subscription->amount,
                    'gateway' => $subscription->gateway,
                    'gateway_subscription_id' => $subscription->gateway_subscription_id,
                    'current_period_end' => $subscription->current_period_end,
                    'cancel_at_period_end' => $subscription->cancel_at_period_end,
                    'last_payment_at' => $subscription->last_payment_at,
                    'renewal_orders_count' => $subscription->renewal_orders_count,
                    'failed_payments_count' => (int) $subscription->failed_payments_count,
                    'last_payment_failure_at' => $subscription->latestPaymentFailure?->processed_at,
                    'user' => $subscription->user->only(['id', 'name', 'email']),
                    'product' => $subscription->product->only(['id', 'name']),
                    'license' => [
                        'id' => $subscription->license->id,
                        'license_key' => $subscription->license->license_key,
                        'status' => $subscription->license->status->value,
                    ],
                ]),
        ]);
    }

    public function show(Subscription $subscription): Response
    {
        $subscription->load([
            'user:id,name,email',
            'product:id,name,slug',
            'productPrice:id,name,price,billing_cycle,currency',
            'license:id,license_key,status,expires_at',
            'order.invoice:id,order_id,invoice_number,status',
        ]);

        $graceDays = max(0, min(60, (int) config('asrtech.subscriptions.grace_days', 3)));

        return Inertia::render('Admin/Subscriptions/Show', [
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'billing_cycle' => $subscription->billing_cycle->value,
                'currency' => $subscription->currency,
                'amount' => $subscription->amount,
                'gateway' => $subscription->gateway,
                'gateway_customer_id' => $subscription->gateway_customer_id,
                'gateway_subscription_id' => $subscription->gateway_subscription_id,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'canceled_at' => $subscription->canceled_at,
                'ended_at' => $subscription->ended_at,
                'last_payment_at' => $subscription->last_payment_at,
                'created_at' => $subscription->created_at,
                'grace_period_ends_at' => $subscription->status === SubscriptionStatus::PastDue
                    ? $subscription->current_period_end?->copy()->addDays($graceDays)
                    : null,
                'user' => $subscription->user->only(['id', 'name', 'email']),
                'product' => $subscription->product->only(['id', 'name', 'slug']),
                'product_price' => $subscription->productPrice?->only(['id', 'name', 'price', 'billing_cycle', 'currency']),
                'license' => [
                    'id' => $subscription->license->id,
                    'license_key' => $subscription->license->license_key,
                    'status' => $subscription->license->status->value,
                    'expires_at' => $subscription->license->expires_at,
                ],
                'initial_order' => [
                    'id' => $subscription->order->id,
                    'order_number' => $subscription->order->order_number,
                    'invoice' => $subscription->order->invoice?->only(['id', 'invoice_number', 'status']),
                ],
            ],
            'events' => $subscription->events()
                ->latest('processed_at')
                ->take(100)
                ->get(['id', 'gateway', 'gateway_event_id', 'event_type', 'payload', 'processed_at']),
            'renewals' => $subscription->renewalOrders()
                ->with('invoice:id,order_id,invoice_number,status')
                ->latest('paid_at')
                ->get(['id', 'subscription_id', 'order_number', 'currency', 'amount', 'payment_reference', 'paid_at']),
        ]);
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        return $this->runAction(
            fn () => $this->subscriptions->requestCancellation($subscription),
            'Subscription cancellation scheduled.',
        );
    }

    public function resume(Subscription $subscription): RedirectResponse
    {
        return $this->runAction(
            fn () => $this->subscriptions->resume($subscription),
            'Subscription renewal restored.',
        );
    }

    private function runAction(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
            Inertia::flash('toast', ['type' => 'success', 'message' => $success]);
        } catch (Throwable $exception) {
            report($exception);
            Inertia::flash('toast', ['type' => 'error', 'message' => 'The gateway could not update this subscription.']);
        }

        return back();
    }
}
