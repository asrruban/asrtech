<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Events\OrderPaid;
use App\Models\License;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Payments\Stripe\StripeGateway;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SubscriptionService
{
    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly SubscriptionNotificationService $notifications,
    ) {}

    /** Create local subscription records after an initial recurring order is paid. */
    public function activateForPaidOrder(Order $order): void
    {
        if ($order->subscription_id !== null || $order->status !== OrderStatus::Paid) {
            return;
        }

        $order->loadMissing(['items', 'licenses']);

        if ($order->items->isEmpty()) {
            if ($order->billing_cycle === BillingCycle::OneTime) {
                return;
            }

            $license = $order->licenses->firstWhere('product_id', $order->product_id);

            if ($license instanceof License) {
                $this->createInitialSubscription(
                    $order,
                    $license,
                    $order->product_id,
                    $order->product_price_id,
                    $order->billing_cycle,
                    (float) $order->amount,
                );
            }

            return;
        }

        foreach ($order->items as $item) {
            if ($item->billing_cycle === BillingCycle::OneTime) {
                continue;
            }

            $license = $order->licenses->firstWhere('product_id', $item->product_id);

            if (! $license instanceof License) {
                continue;
            }

            $this->createInitialSubscription(
                $order,
                $license,
                $item->product_id,
                $item->product_price_id,
                $item->billing_cycle,
                (float) $item->amount,
            );
        }
    }

    /** Attach the provider's identifiers to subscription records created for an order. */
    public function bindInitialGateway(
        Order $order,
        ?string $gatewaySubscriptionId,
        ?string $gatewayCustomerId,
        ?string $gatewayEventId = null,
    ): void {
        $this->activateForPaidOrder($order);

        $subscriptions = Subscription::query()->where('order_id', $order->id)->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'gateway' => 'stripe',
                'gateway_subscription_id' => $gatewaySubscriptionId,
                'gateway_customer_id' => $gatewayCustomerId,
                'status' => SubscriptionStatus::Active,
            ]);

            if ($gatewayEventId !== null) {
                $this->recordEvent(
                    $subscription,
                    'stripe',
                    $gatewayEventId,
                    'checkout.session.completed',
                    ['order_id' => $order->id],
                );
            }
        }
    }

    /** Create a paid renewal order, invoice, transaction, and extend the existing license. */
    public function renewFromGateway(
        Subscription $subscription,
        string $gatewayEventId,
        string $reference,
        float $amount,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?string $gatewayCustomerId = null,
    ): ?Order {
        $renewal = DB::transaction(function () use (
            $subscription,
            $gatewayEventId,
            $reference,
            $amount,
            $periodStart,
            $periodEnd,
            $gatewayCustomerId,
        ): ?Order {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);

            if (SubscriptionEvent::query()
                ->where('gateway', $locked->gateway)
                ->where('gateway_event_id', $gatewayEventId)
                ->exists()) {
                return null;
            }

            $locked->loadMissing(['product', 'productPrice', 'license']);

            $order = Order::query()->create([
                'user_id' => $locked->user_id,
                'product_id' => $locked->product_id,
                'product_price_id' => $locked->product_price_id,
                'subscription_id' => $locked->id,
                'order_number' => $this->generateOrderNumber(),
                'currency' => $locked->currency,
                'subtotal' => $amount,
                'amount' => $amount,
                'discount_amount' => 0,
                'setup_fee' => 0,
                'tax_amount' => 0,
                'billing_cycle' => $locked->billing_cycle,
                'status' => OrderStatus::Paid,
                'payment_method' => $locked->gateway,
                'payment_reference' => $reference,
                'paid_at' => now(),
            ]);

            $order->items()->create([
                'product_id' => $locked->product_id,
                'product_price_id' => $locked->product_price_id,
                'product_name' => $locked->product->name,
                'price_name' => $locked->productPrice?->name,
                'currency' => $locked->currency,
                'amount' => $amount,
                'setup_fee' => 0,
                'billing_cycle' => $locked->billing_cycle,
            ]);

            $order->transactions()->create([
                'type' => 'payment',
                'gateway' => $locked->gateway,
                'reference' => $reference,
                'amount' => $amount,
                'description' => "Subscription renewal for {$locked->product->name}",
            ]);

            $locked->update([
                'gateway_customer_id' => $gatewayCustomerId ?? $locked->gateway_customer_id,
                'status' => SubscriptionStatus::Active,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'last_payment_at' => now(),
                'ended_at' => null,
            ]);

            $locked->license->update([
                'status' => LicenseStatus::Active,
                'expires_at' => $periodEnd,
            ]);

            SubscriptionEvent::query()->create([
                'subscription_id' => $locked->id,
                'gateway' => $locked->gateway,
                'gateway_event_id' => $gatewayEventId,
                'event_type' => 'invoice.paid',
                'payload' => ['reference' => $reference, 'amount' => $amount],
                'processed_at' => now(),
            ]);

            return $order;
        });

        if ($renewal !== null) {
            OrderPaid::dispatch($renewal->refresh());
            $this->notifications->renewed($subscription->fresh(), $renewal->refresh());
        }

        return $renewal;
    }

    public function markPaymentFailed(
        Subscription $subscription,
        string $gatewayEventId,
        ?string $reference = null,
    ): void {
        $recorded = DB::transaction(function () use ($subscription, $gatewayEventId, $reference): bool {
            if (! $this->recordEvent(
                $subscription,
                $subscription->gateway,
                $gatewayEventId,
                'invoice.payment_failed',
                ['reference' => $reference],
            )) {
                return false;
            }

            $subscription->update(['status' => SubscriptionStatus::PastDue]);
            $subscription->loadMissing('license');
            $licenseChanges = ['expires_at' => $this->gracePeriodEnd($subscription->current_period_end)];

            if ($subscription->license->status === LicenseStatus::Expired) {
                $licenseChanges['status'] = LicenseStatus::Active;
            }

            $subscription->license->update($licenseChanges);

            return true;
        });

        if ($recorded) {
            $this->notifications->paymentFailed($subscription->fresh(), $reference);
        }
    }

    /** Synchronize asynchronous provider status and period changes. */
    public function syncGatewayStatus(
        Subscription $subscription,
        string $gatewayEventId,
        SubscriptionStatus $status,
        ?CarbonInterface $periodStart,
        ?CarbonInterface $periodEnd,
        bool $cancelAtPeriodEnd,
        ?CarbonInterface $canceledAt = null,
        ?CarbonInterface $endedAt = null,
    ): void {
        DB::transaction(function () use (
            $subscription,
            $gatewayEventId,
            $status,
            $periodStart,
            $periodEnd,
            $cancelAtPeriodEnd,
            $canceledAt,
            $endedAt,
        ): void {
            if (! $this->recordEvent(
                $subscription,
                $subscription->gateway,
                $gatewayEventId,
                'customer.subscription.updated',
                ['status' => $status->value],
            )) {
                return;
            }

            $subscription->update([
                'status' => $status,
                'current_period_start' => $periodStart ?? $subscription->current_period_start,
                'current_period_end' => $periodEnd ?? $subscription->current_period_end,
                'cancel_at_period_end' => $cancelAtPeriodEnd,
                'canceled_at' => $canceledAt,
                'ended_at' => $endedAt,
            ]);

            $effectivePeriodEnd = $periodEnd ?? $subscription->current_period_end;
            $licenseChanges = [
                'expires_at' => $status === SubscriptionStatus::PastDue
                    ? $this->gracePeriodEnd($effectivePeriodEnd)
                    : $effectivePeriodEnd,
            ];

            if ($status->providesAccess()) {
                $licenseChanges['status'] = LicenseStatus::Active;
            } elseif ($status === SubscriptionStatus::Paused) {
                $licenseChanges['status'] = LicenseStatus::Suspended;
            } elseif ($status === SubscriptionStatus::Canceled) {
                $licenseChanges['status'] = LicenseStatus::Expired;
            }

            $subscription->license()->update($licenseChanges);
        });
    }

    public function requestCancellation(Subscription $subscription): void
    {
        if ($subscription->cancel_at_period_end || $subscription->status === SubscriptionStatus::Canceled) {
            return;
        }

        if ($subscription->gateway === 'stripe' && filled($subscription->gateway_subscription_id)) {
            $this->stripe->client()->subscriptions->update(
                (string) $subscription->gateway_subscription_id,
                ['cancel_at_period_end' => true],
            );
        }

        $subscription->update([
            'cancel_at_period_end' => true,
            'canceled_at' => now(),
        ]);

        $this->recordSystemEvent($subscription, 'subscription.cancellation_scheduled');

        $this->notifications->cancellationScheduled($subscription->fresh());
    }

    public function resume(Subscription $subscription): void
    {
        if (! $subscription->cancel_at_period_end || $subscription->status === SubscriptionStatus::Canceled) {
            return;
        }

        if ($subscription->gateway === 'stripe' && filled($subscription->gateway_subscription_id)) {
            $this->stripe->client()->subscriptions->update(
                (string) $subscription->gateway_subscription_id,
                ['cancel_at_period_end' => false],
            );
        }

        $subscription->update([
            'cancel_at_period_end' => false,
            'canceled_at' => null,
        ]);

        $this->recordSystemEvent($subscription, 'subscription.renewal_resumed');
    }

    /** Expire access when a scheduled cancellation reaches its period end. */
    public function endDueSubscriptions(): int
    {
        $ended = 0;

        Subscription::query()
            ->where('cancel_at_period_end', true)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now())
            ->where('status', '!=', SubscriptionStatus::Canceled)
            ->eachById(function (Subscription $subscription) use (&$ended): void {
                DB::transaction(function () use ($subscription): void {
                    $subscription->update([
                        'status' => SubscriptionStatus::Canceled,
                        'ended_at' => now(),
                    ]);
                    $subscription->license()->update([
                        'status' => LicenseStatus::Expired,
                        'expires_at' => $subscription->current_period_end,
                    ]);
                    $this->recordSystemEvent($subscription, 'subscription.ended', [
                        'reason' => 'cancellation',
                    ]);
                });

                $ended++;
            });

        Subscription::query()
            ->where('status', SubscriptionStatus::PastDue)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now()->subDays($this->graceDays()))
            ->whereHas('license', fn ($query) => $query->where('status', '!=', LicenseStatus::Expired))
            ->eachById(function (Subscription $subscription): void {
                $graceEndsAt = $this->gracePeriodEnd($subscription->current_period_end);
                $subscription->license()->update([
                    'status' => LicenseStatus::Expired,
                    'expires_at' => $graceEndsAt,
                ]);
                $this->recordSystemEvent($subscription, 'subscription.grace_expired', [
                    'grace_ended_at' => $graceEndsAt?->toIso8601String(),
                ]);
            });

        return $ended;
    }

    /** Create subscription records for recurring orders paid before this feature existed. */
    public function backfillPaidOrders(): int
    {
        $created = 0;

        Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereNull('subscription_id')
            ->where(function ($query) {
                $query->where('billing_cycle', '!=', BillingCycle::OneTime)
                    ->orWhereHas('items', fn ($query) => $query
                        ->where('billing_cycle', '!=', BillingCycle::OneTime));
            })
            ->eachById(function (Order $order) use (&$created): void {
                $before = Subscription::query()->where('order_id', $order->id)->count();
                $this->activateForPaidOrder($order);
                $created += Subscription::query()->where('order_id', $order->id)->count() - $before;
            });

        return $created;
    }

    /** @param array<string, mixed> $payload */
    private function recordEvent(
        Subscription $subscription,
        string $gateway,
        string $gatewayEventId,
        string $eventType,
        array $payload,
    ): bool {
        try {
            $event = SubscriptionEvent::query()->firstOrCreate(
                ['gateway' => $gateway, 'gateway_event_id' => $gatewayEventId],
                [
                    'subscription_id' => $subscription->id,
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'processed_at' => now(),
                ],
            );

            return $event->wasRecentlyCreated;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function createInitialSubscription(
        Order $order,
        License $license,
        int $productId,
        ?int $productPriceId,
        BillingCycle $cycle,
        float $amount,
    ): void {
        $isTrial = ($order->payment_method === 'free_trial');
        $periodStart = $order->paid_at ?? now();
        $periodEnd = $isTrial ? $periodStart->copy()->addDays(7) : match ($cycle) {
            BillingCycle::Monthly => $periodStart->copy()->addMonth(),
            BillingCycle::Yearly => $periodStart->copy()->addYear(),
            BillingCycle::OneTime => null,
        };

        Subscription::query()->firstOrCreate(
            ['license_id' => $license->id],
            [
                'user_id' => $order->user_id,
                'product_id' => $productId,
                'product_price_id' => $productPriceId,
                'order_id' => $order->id,
                'gateway' => $order->payment_method ?? 'manual',
                'status' => $isTrial ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
                'billing_cycle' => $cycle,
                'currency' => $order->currency,
                'amount' => $amount,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'last_payment_at' => $order->paid_at,
            ],
        );
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'REN-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function graceDays(): int
    {
        return max(0, min(60, (int) config('asrtech.subscriptions.grace_days', 3)));
    }

    private function gracePeriodEnd(?CarbonInterface $periodEnd): ?CarbonInterface
    {
        return $periodEnd?->copy()->addDays($this->graceDays());
    }

    /** @param array<string, mixed> $payload */
    private function recordSystemEvent(Subscription $subscription, string $eventType, array $payload = []): void
    {
        $this->recordEvent(
            $subscription,
            'system',
            $eventType.':'.Str::uuid(),
            $eventType,
            $payload,
        );
    }
}
