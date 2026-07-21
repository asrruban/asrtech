<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\GatewayRegistry;
use App\Payments\Stripe\StripeGateway;
use App\Services\CheckoutService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly StripeGateway $stripe,
        private readonly CheckoutService $checkout,
        private readonly GatewayRegistry $gateways,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return Inertia::render('Client/Account/Subscriptions', [
            'subscriptions' => $user->subscriptions()
                ->with([
                    'product:id,name,slug,type,featured_image',
                    'license:id,license_key,status',
                    'latestPaymentFailure',
                ])
                ->withCount([
                    'events as failed_payments_count' => fn ($query) => $query
                        ->where('event_type', 'invoice.payment_failed'),
                ])
                ->orderByRaw("case when status in ('active', 'trialing', 'past_due') then 0 else 1 end")
                ->latest()
                ->get()
                ->map(fn (Subscription $subscription): array => $this->payload($subscription)),
        ]);
    }

    public function show(Request $request, Subscription $subscription): Response
    {
        $this->authorizeOwner($request, $subscription);
        $subscription->load([
            'product',
            'productPrice',
            'license',
            'order.invoice',
            'latestPaymentFailure',
        ])->loadCount([
            'events as failed_payments_count' => fn ($query) => $query
                ->where('event_type', 'invoice.payment_failed'),
        ]);

        return Inertia::render('Client/Account/Subscription', [
            'subscription' => [
                ...$this->payload($subscription),
                'created_at' => $subscription->created_at?->toIso8601String(),
                'grace_period_ends_at' => $this->gracePeriodEndsAt($subscription),
                'initial_order' => [
                    'order_number' => $subscription->order->order_number,
                    'paid_at' => $subscription->order->paid_at?->toIso8601String(),
                    'invoice' => $subscription->order->invoice === null ? null : [
                        'invoice_number' => $subscription->order->invoice->invoice_number,
                        'url' => route('account.invoice', $subscription->order->invoice),
                    ],
                ],
            ],
            'events' => $subscription->events()
                ->latest('processed_at')
                ->take(100)
                ->get(['id', 'event_type', 'processed_at'])
                ->map(fn ($event): array => [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'occurred_at' => $event->processed_at->toIso8601String(),
                ]),
            'renewals' => $subscription->renewalOrders()
                ->with('invoice:id,order_id,invoice_number,status')
                ->latest('paid_at')
                ->get()
                ->map(fn ($order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                    'paid_at' => $order->paid_at?->toIso8601String(),
                    'invoice' => $order->invoice === null ? null : [
                        'invoice_number' => $order->invoice->invoice_number,
                        'status' => $order->invoice->status->value,
                        'url' => route('account.invoice', $order->invoice),
                    ],
                ]),
        ]);
    }

    public function billingPortal(Request $request, Subscription $subscription): SymfonyResponse
    {
        $this->authorizeOwner($request, $subscription);
        abort_unless($this->canUpdatePaymentMethod($subscription), 404);

        try {
            return Inertia::location($this->stripe->billingPortalUrl($subscription));
        } catch (Throwable $exception) {
            report($exception);
            $this->flash('error', 'Stripe billing is temporarily unavailable. Please try again shortly.');

            return back();
        }
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwner($request, $subscription);

        try {
            $this->subscriptions->requestCancellation($subscription);
            $this->flash('success', 'Subscription will cancel at the end of the current billing period.');
        } catch (Throwable $exception) {
            report($exception);
            $this->flash('error', 'The payment provider could not schedule cancellation. Please try again.');
        }

        return back();
    }

    public function resume(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwner($request, $subscription);

        try {
            $this->subscriptions->resume($subscription);
            $this->flash('success', 'Automatic renewal has been restored.');
        } catch (Throwable $exception) {
            report($exception);
            $this->flash('error', 'The payment provider could not restore this subscription. Please try again.');
        }

        return back();
    }

    /** @return array<string, mixed> */
    private function payload(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'status' => $subscription->status->value,
            'billing_cycle' => $subscription->billing_cycle->value,
            'currency' => $subscription->currency,
            'amount' => $subscription->amount,
            'gateway' => $subscription->gateway,
            'current_period_start' => $subscription->current_period_start?->toIso8601String(),
            'current_period_end' => $subscription->current_period_end?->toIso8601String(),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'canceled_at' => $subscription->canceled_at?->toIso8601String(),
            'last_payment_at' => $subscription->last_payment_at?->toIso8601String(),
            'last_payment_failure_at' => $subscription->latestPaymentFailure?->processed_at?->toIso8601String(),
            'failed_payments_count' => (int) $subscription->failed_payments_count,
            'payment_attention_required' => in_array($subscription->status, [
                SubscriptionStatus::PastDue,
                SubscriptionStatus::Incomplete,
            ], true),
            'can_update_payment_method' => $this->canUpdatePaymentMethod($subscription),
            'details_url' => route('account.subscriptions.show', $subscription),
            'can_cancel' => in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::Trialing], true)
                && ! $subscription->cancel_at_period_end,
            'can_resume' => $subscription->cancel_at_period_end
                && $subscription->status !== SubscriptionStatus::Canceled,
            'can_extend' => $subscription->gateway === 'free_trial'
                && in_array($subscription->status, [SubscriptionStatus::Trialing, SubscriptionStatus::Active], true),
            'product' => [
                'name' => $subscription->product->name,
                'featured_image' => $subscription->product->featured_image,
                'url' => $subscription->product->storefrontUrl(),
            ],
            'license' => [
                'id' => $subscription->license->id,
                'license_key' => $subscription->license->license_key,
                'status' => $subscription->license->status->value,
                'url' => route('account.product', $subscription->license),
            ],
        ];
    }

    private function gracePeriodEndsAt(Subscription $subscription): ?string
    {
        if ($subscription->status !== SubscriptionStatus::PastDue || $subscription->current_period_end === null) {
            return null;
        }

        $days = max(0, min(60, (int) config('asrtech.subscriptions.grace_days', 3)));

        return $subscription->current_period_end->copy()->addDays($days)->toIso8601String();
    }

    private function canUpdatePaymentMethod(Subscription $subscription): bool
    {
        return $subscription->gateway === 'stripe'
            && filled($subscription->gateway_customer_id)
            && $subscription->status !== SubscriptionStatus::Canceled;
    }

    private function authorizeOwner(Request $request, Subscription $subscription): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $subscription->user_id === $user->id, 404);
    }

    private function flash(string $type, string $message): void
    {
        Inertia::flash('toast', compact('type', 'message'));
    }

    public function showExtend(Request $request, Subscription $subscription): Response
    {
        $this->authorizeOwner($request, $subscription);
        abort_unless($subscription->gateway === 'free_trial', 404);

        $subscription->load(['product']);
        $availableGateways = $this->gateways->enabled();

        if ($this->stripe->implemented()) {
            $availableGateways[$this->stripe->key()] = $this->stripe;
        }

        return Inertia::render('Client/Account/ExtendSubscription', [
            'subscription' => [
                'id' => $subscription->id,
                'billing_cycle' => $subscription->billing_cycle->value,
                'currency' => $subscription->currency,
                'amount' => $subscription->amount,
                'product' => [
                    'name' => $subscription->product->name,
                    'featured_image' => $subscription->product->featured_image,
                ],
            ],
            'paymentGateways' => collect($availableGateways)
                ->filter(fn ($gateway) => $gateway->key() !== 'free_trial')
                ->map(fn ($gateway): array => [
                    'key' => $gateway->key(),
                    'name' => $gateway->displayName(),
                    'description' => $gateway->description(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function extend(Request $request, Subscription $subscription): RedirectResponse|SymfonyResponse
    {
        $this->authorizeOwner($request, $subscription);
        abort_unless($subscription->gateway === 'free_trial', 404);

        $validated = $request->validate([
            'gateway' => ['required', 'string', Rule::in(
                collect($this->stripe->implemented() ? ['stripe'] : [])
                    ->merge($this->gateways->enabledKeys())
                    ->filter(fn ($key) => $key !== 'free_trial')
                    ->all()
            )],
        ]);

        $gateway = $this->gateways->find($validated['gateway']);
        abort_unless($gateway !== null && $gateway->ready(), 400);

        $order = Order::query()->create([
            'user_id' => $subscription->user_id,
            'product_id' => $subscription->product_id,
            'product_price_id' => $subscription->product_price_id,
            'subscription_id' => $subscription->id,
            'order_number' => 'EXT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'currency' => $subscription->currency,
            'amount' => $subscription->amount,
            'setup_fee' => 0,
            'billing_cycle' => $subscription->billing_cycle,
            'status' => OrderStatus::Pending,
            'payment_method' => $gateway->key(),
        ]);

        $order->items()->create([
            'product_id' => $subscription->product_id,
            'product_price_id' => $subscription->product_price_id,
            'product_name' => $subscription->product->name.' (Subscription Extension)',
            'price_name' => $subscription->product_price_id === null
                ? 'License Plan'
                : $subscription->productPrice->name,
            'currency' => $subscription->currency,
            'amount' => $subscription->amount,
            'setup_fee' => 0,
            'billing_cycle' => $subscription->billing_cycle,
        ]);

        $result = $gateway->charge($order);

        if ($result->needsRedirect()) {
            $order->update([
                'payment_reference' => $result->reference,
            ]);

            return Inertia::location((string) $result->redirectUrl);
        }

        if (! $result->successful) {
            $order->update(['status' => OrderStatus::Failed]);
            $this->flash('error', 'Payment failed. Please try again.');

            return back();
        }

        $this->checkout->markPaid($order, $result->method, $result->reference);

        $this->flash('success', 'Subscription extended successfully!');

        return redirect()->route('account.subscriptions.show', $subscription);
    }
}
