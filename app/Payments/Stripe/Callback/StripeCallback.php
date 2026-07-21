<?php

namespace App\Payments\Stripe\Callback;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Payments\Stripe\StripeGateway;
use App\Services\CheckoutService;
use App\Services\PaymentDisputeService;
use App\Services\SubscriptionService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeCallback
{
    public function __construct(
        private readonly StripeGateway $gateway,
        private readonly CheckoutService $checkout,
        private readonly SubscriptionService $subscriptions,
        private readonly PaymentDisputeService $disputes,
    ) {}

    /** Stripe payment and subscription webhook endpoint. */
    public function webhook(Request $request): Response
    {
        $secret = $this->gateway->config('webhook_secret');

        if (filled($secret)) {
            try {
                $event = Webhook::constructEvent(
                    $request->getContent(),
                    (string) $request->header('Stripe-Signature', ''),
                    (string) $secret,
                );
                $payload = $event->toArray();
            } catch (Throwable) {
                return response('Invalid signature', 400);
            }
        } else {
            $payload = (array) $request->all();
        }

        // The reliability layer may safely replay payloads only after this
        // callback has accepted the provider signature (or local no-secret
        // development mode has explicitly accepted the request).
        $request->attributes->set('webhook_payload_verified', true);

        return $this->processPayload($payload);
    }

    /**
     * Replay a payload already accepted and stored by the webhook receiver.
     *
     * @param  array<string, mixed>  $payload
     */
    public function replay(array $payload): Response
    {
        return $this->processPayload($payload);
    }

    /** @param array<string, mixed> $payload */
    private function processPayload(array $payload): Response
    {

        $type = (string) ($payload['type'] ?? '');
        $object = (array) ($payload['data']['object'] ?? []);
        $eventId = (string) ($payload['id'] ?? ($type.':'.($object['id'] ?? Str::uuid())));

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object, $eventId),
            'invoice.paid' => $this->handleRenewalPaid($object, $eventId),
            'invoice.payment_failed' => $this->handlePaymentFailed($object, $eventId),
            'customer.subscription.updated' => $this->handleSubscriptionChanged($object, $eventId, false),
            'customer.subscription.deleted' => $this->handleSubscriptionChanged($object, $eventId, true),
            'charge.dispute.created',
            'charge.dispute.updated',
            'charge.dispute.closed',
            'charge.dispute.funds_withdrawn',
            'charge.dispute.funds_reinstated' => $this->disputes->syncStripe($object, $eventId, $type),
            default => null,
        };

        return response('OK');
    }

    /**
     * The customer returning from Stripe Checkout. The webhook is the
     * source of truth; this verifies with the API as a fallback so
     * local setups without webhooks still complete.
     */
    public function handleReturn(Request $request): RedirectResponse
    {
        $sessionId = $request->string('session_id')->toString();
        $order = $sessionId === ''
            ? null
            : Order::query()->where('payment_reference', $sessionId)->first();

        if ($order === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('We could not find that payment.')]);

            return redirect()->route('products.index');
        }

        if ($order->status !== OrderStatus::Paid) {
            try {
                $session = $this->gateway->client()->checkout->sessions->retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $order = $this->checkout->markPaid($order, 'stripe', $sessionId);
                    $sessionData = $session->toArray();
                    $this->storeGatewayPaymentId($order, $sessionData);
                    $this->bindSubscription($order, $sessionData, "return:{$sessionId}");
                    $this->storeCard($order, $sessionData);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        if ($order->status === OrderStatus::Paid) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment confirmed — your license is ready.')]);

            return redirect()->route('account.index');
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Your payment is still processing. The license will appear once it is confirmed.')]);

        return redirect()->route('account.index');
    }

    /** @param array<string, mixed> $session */
    private function findOrder(array $session): ?Order
    {
        $orderId = $session['metadata']['order_id'] ?? $session['client_reference_id'] ?? null;

        if ($orderId !== null) {
            $order = Order::query()->find((int) $orderId);

            if ($order !== null) {
                return $order;
            }
        }

        $sessionId = $session['id'] ?? null;

        return $sessionId === null
            ? null
            : Order::query()->where('payment_reference', $sessionId)->first();
    }

    /** @param array<string, mixed> $session */
    private function handleCheckoutCompleted(array $session, string $eventId): void
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $order = $this->findOrder($session);

        if ($order === null) {
            return;
        }

        $reference = is_string($session['id'] ?? null) ? $session['id'] : null;
        $order = $this->checkout->markPaid($order, 'stripe', $reference);
        $this->storeGatewayPaymentId($order, $session);
        $this->bindSubscription($order, $session, $eventId);
        $this->storeCard($order, $session);
    }

    /** @param array<string, mixed> $session */
    private function bindSubscription(Order $order, array $session, string $eventId): void
    {
        $subscriptionId = $this->objectId($session['subscription'] ?? null);

        if ($subscriptionId === null) {
            return;
        }

        $this->subscriptions->bindInitialGateway(
            $order,
            $subscriptionId,
            $this->objectId($session['customer'] ?? null),
            $eventId,
        );
    }

    /** @param array<string, mixed> $session */
    private function storeGatewayPaymentId(Order $order, array $session): void
    {
        $paymentIntent = $this->objectId($session['payment_intent'] ?? null)
            ?? $this->objectId(data_get($session, 'invoice.payment_intent'));

        if ($paymentIntent !== null) {
            $order->update(['gateway_payment_id' => $paymentIntent]);
        }
    }

    /** @param array<string, mixed> $invoice */
    private function handleRenewalPaid(array $invoice, string $eventId): void
    {
        if (($invoice['billing_reason'] ?? null) !== 'subscription_cycle') {
            return;
        }

        $subscription = $this->findSubscriptionForInvoice($invoice);
        $period = $this->invoicePeriod($invoice);

        if ($subscription === null || $period === null) {
            return;
        }

        $this->subscriptions->renewFromGateway(
            $subscription,
            $eventId,
            (string) ($invoice['id'] ?? $eventId),
            ((float) ($invoice['amount_paid'] ?? 0)) / 100,
            $period[0],
            $period[1],
            $this->objectId($invoice['customer'] ?? null),
        );
    }

    /** @param array<string, mixed> $invoice */
    private function handlePaymentFailed(array $invoice, string $eventId): void
    {
        $subscription = $this->findSubscriptionForInvoice($invoice);

        if ($subscription !== null) {
            $this->subscriptions->markPaymentFailed(
                $subscription,
                $eventId,
                is_string($invoice['id'] ?? null) ? $invoice['id'] : null,
            );
        }
    }

    /** @param array<string, mixed> $providerSubscription */
    private function handleSubscriptionChanged(array $providerSubscription, string $eventId, bool $deleted): void
    {
        $providerId = $this->objectId($providerSubscription['id'] ?? null);

        if ($providerId === null) {
            return;
        }

        $subscriptions = Subscription::query()
            ->where('gateway', 'stripe')
            ->where('gateway_subscription_id', $providerId)
            ->get();
        $status = $deleted
            ? SubscriptionStatus::Canceled
            : $this->mapStatus((string) ($providerSubscription['status'] ?? 'incomplete'));

        foreach ($subscriptions as $subscription) {
            $this->subscriptions->syncGatewayStatus(
                $subscription,
                $eventId,
                $status,
                $this->timestamp($providerSubscription['current_period_start'] ?? null),
                $this->timestamp($providerSubscription['current_period_end'] ?? null),
                (bool) ($providerSubscription['cancel_at_period_end'] ?? false),
                $this->timestamp($providerSubscription['canceled_at'] ?? null),
                $this->timestamp($providerSubscription['ended_at'] ?? null),
            );
        }
    }

    /** @param array<string, mixed> $invoice */
    private function findSubscriptionForInvoice(array $invoice): ?Subscription
    {
        $providerId = $this->objectId($invoice['subscription'] ?? null)
            ?? $this->objectId(data_get($invoice, 'parent.subscription_details.subscription'));

        return $providerId === null
            ? null
            : Subscription::query()
                ->where('gateway', 'stripe')
                ->where('gateway_subscription_id', $providerId)
                ->first();
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array{CarbonInterface, CarbonInterface}|null
     */
    private function invoicePeriod(array $invoice): ?array
    {
        $period = data_get($invoice, 'lines.data.0.period');

        if (! is_array($period)) {
            return null;
        }

        $start = $this->timestamp($period['start'] ?? null);
        $end = $this->timestamp($period['end'] ?? null);

        return $start !== null && $end !== null ? [$start, $end] : null;
    }

    private function timestamp(mixed $value): ?CarbonInterface
    {
        return is_numeric($value) ? Date::createFromTimestamp((int) $value) : null;
    }

    private function objectId(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_array($value) && is_string($value['id'] ?? null)) {
            return $value['id'];
        }

        return null;
    }

    private function mapStatus(string $status): SubscriptionStatus
    {
        return match ($status) {
            'active' => SubscriptionStatus::Active,
            'trialing' => SubscriptionStatus::Trialing,
            'past_due', 'unpaid' => SubscriptionStatus::PastDue,
            'paused' => SubscriptionStatus::Paused,
            'canceled', 'incomplete_expired' => SubscriptionStatus::Canceled,
            default => SubscriptionStatus::Incomplete,
        };
    }

    /**
     * Best-effort tokenized card capture, WHMCS tblpaymethods style:
     * brand, last four, expiry, and the Stripe payment-method token.
     * Never the card number itself.
     *
     * @param  array<string, mixed>  $session
     */
    private function storeCard(Order $order, array $session): void
    {
        try {
            $intentId = $session['payment_intent'] ?? null;

            if (! is_string($intentId) || $intentId === '') {
                return;
            }

            $intent = $this->gateway->client()->paymentIntents->retrieve($intentId, [
                'expand' => ['latest_charge'],
            ]);

            $charge = $intent->latest_charge;
            $card = is_object($charge) ? ($charge->payment_method_details->card ?? null) : null;

            if ($card === null) {
                return;
            }

            PaymentMethod::query()->updateOrCreate(
                [
                    'user_id' => $order->user_id,
                    'gateway' => 'stripe',
                    'card_brand' => (string) $card->brand,
                    'card_last_four' => (string) $card->last4,
                ],
                [
                    'type' => 'card',
                    'card_expiry_month' => (int) $card->exp_month,
                    'card_expiry_year' => (int) $card->exp_year,
                    'token' => is_string($intent->payment_method) ? $intent->payment_method : null,
                    'name_on_card' => $charge->billing_details->name ?? null,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
