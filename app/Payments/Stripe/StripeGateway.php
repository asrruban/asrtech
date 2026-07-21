<?php

namespace App\Payments\Stripe;

use App\Enums\BillingCycle;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Payments\Gateway;
use App\Payments\PaymentResult;
use App\Payments\RefundableGateway;
use App\Payments\RefundResult;
use RuntimeException;
use Stripe\StripeClient;
use Throwable;

class StripeGateway extends Gateway implements RefundableGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function name(): string
    {
        return 'Stripe';
    }

    public function description(): string
    {
        return 'One-time and recurring card payments through Stripe Checkout. Customers are redirected to Stripe and returned after paying.';
    }

    public function ready(): bool
    {
        return $this->isConfigured();
    }

    public function webhookInstructions(): ?string
    {
        return 'Enable and brand the Stripe Customer Portal in both sandbox and live mode. Then add the webhook URL in Stripe Workbench, subscribe to checkout.session.completed, invoice.paid, invoice.payment_failed, customer.subscription.updated, customer.subscription.deleted, charge.dispute.created, charge.dispute.updated, charge.dispute.closed, charge.dispute.funds_withdrawn, and charge.dispute.funds_reinstated, then paste the signing secret (whsec_...) below.';
    }

    public function configFields(): array
    {
        return [
            ['name' => 'publishable_key', 'label' => 'Publishable key', 'type' => 'text', 'description' => 'pk_live_... or pk_test_...'],
            ['name' => 'secret_key', 'label' => 'Secret key', 'type' => 'password', 'description' => 'sk_live_... or sk_test_...'],
            ['name' => 'webhook_secret', 'label' => 'Webhook signing secret', 'type' => 'password', 'description' => 'whsec_... — strongly recommended in production', 'required' => false],
        ];
    }

    public function client(): StripeClient
    {
        return new StripeClient((string) $this->config('secret_key'));
    }

    public function charge(Order $order): PaymentResult
    {
        try {
            $order->loadMissing(['product:id,name,slug', 'user:id,email', 'items']);
            $hasRecurringItem = $order->items->isNotEmpty()
                ? $order->items->contains(fn (OrderItem $item): bool => $item->billing_cycle !== BillingCycle::OneTime)
                : $order->billing_cycle !== BillingCycle::OneTime;
            $lineItems = $order->items->isNotEmpty()
                ? $order->items->flatMap(fn (OrderItem $item): array => $this->lineItemsForOrderItem($item, $order))
                    ->values()
                    ->all()
                : $this->lineItemsForOrder($order);

            if ((float) $order->tax_amount > 0) {
                $lineItems[] = [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'unit_amount' => (int) round((float) $order->tax_amount * 100),
                        'product_data' => ['name' => $order->tax_name ?: 'Tax'],
                    ],
                ];
            }

            $sessionData = [
                'mode' => $hasRecurringItem ? 'subscription' : 'payment',
                'client_reference_id' => (string) $order->id,
                'customer_email' => $order->user->email,
                'line_items' => $lineItems,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ],
                'success_url' => route('gateways.return', 'stripe').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $order->items->count() > 1
                    ? route('cart.index')
                    : route('products.show', $order->product->storefrontRouteParameters()),
            ];

            if ($hasRecurringItem) {
                $sessionData['subscription_data'] = [
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ],
                ];
            }

            if ((float) $order->discount_amount > 0) {
                $coupon = $this->client()->coupons->create([
                    'duration' => 'once',
                    'amount_off' => (int) round((float) $order->discount_amount * 100),
                    'currency' => strtolower($order->currency),
                    'max_redemptions' => 1,
                    'name' => $order->promotion_code
                        ? "Promotion {$order->promotion_code}"
                        : "Order {$order->order_number} discount",
                    'metadata' => ['order_id' => (string) $order->id],
                ]);
                $sessionData['discounts'] = [['coupon' => $coupon->id]];
            }

            $session = $this->client()->checkout->sessions->create($sessionData);

            return PaymentResult::redirect('stripe', (string) $session->url, $session->id);
        } catch (Throwable $exception) {
            report($exception);

            return PaymentResult::failure('stripe', $exception->getMessage());
        }
    }

    public function refund(Order $order, float $amount, string $idempotencyKey): RefundResult
    {
        try {
            $paymentIntent = $this->refundablePaymentIntent($order);
            if ($paymentIntent === null) {
                return RefundResult::failed('Stripe payment intent could not be resolved for this order.');
            }

            $refund = $this->client()->refunds->create([
                'payment_intent' => $paymentIntent,
                'amount' => (int) round($amount * 100),
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            return $refund->status === 'succeeded'
                ? RefundResult::succeeded((string) $refund->id)
                : RefundResult::processing((string) $refund->id);
        } catch (Throwable $exception) {
            report($exception);

            return RefundResult::failed($exception->getMessage());
        }
    }

    private function refundablePaymentIntent(Order $order): ?string
    {
        if (is_string($order->gateway_payment_id) && str_starts_with($order->gateway_payment_id, 'pi_')) {
            return $order->gateway_payment_id;
        }

        $reference = (string) $order->payment_reference;
        if (str_starts_with($reference, 'pi_')) {
            return $reference;
        }

        if (str_starts_with($reference, 'cs_')) {
            $session = $this->client()->checkout->sessions->retrieve($reference, [
                'expand' => ['payment_intent', 'invoice.payment_intent'],
            ])->toArray();

            $paymentIntent = $this->objectId($session['payment_intent'] ?? null)
                ?? $this->objectId(data_get($session, 'invoice.payment_intent'));
            if ($paymentIntent !== null) {
                return $paymentIntent;
            }

            $invoiceId = $this->objectId($session['invoice'] ?? null);
            if ($invoiceId !== null) {
                return $this->paymentIntentForInvoice($invoiceId);
            }
        }

        return str_starts_with($reference, 'in_')
            ? $this->paymentIntentForInvoice($reference)
            : null;
    }

    private function paymentIntentForInvoice(string $invoiceId): ?string
    {
        $invoice = $this->client()->invoices->retrieve($invoiceId, [
            'expand' => ['payment_intent'],
        ])->toArray();

        return $this->objectId($invoice['payment_intent'] ?? null)
            ?? $this->objectId(data_get($invoice, 'payments.data.0.payment.payment_intent'));
    }

    private function objectId(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return is_array($value) && is_string($value['id'] ?? null)
            ? $value['id']
            : null;
    }

    /** Create a short-lived, payment-method-only Stripe Billing Portal link. */
    public function billingPortalUrl(Subscription $subscription): string
    {
        if (! $this->ready() || blank($subscription->gateway_customer_id)) {
            throw new RuntimeException('Stripe billing portal is not available for this subscription.');
        }

        $returnUrl = route('account.subscriptions');
        $session = $this->client()->billingPortal->sessions->create([
            'customer' => (string) $subscription->gateway_customer_id,
            'return_url' => $returnUrl,
            'flow_data' => [
                'type' => 'payment_method_update',
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => ['return_url' => $returnUrl],
                ],
            ],
        ]);

        if (blank($session->url)) {
            throw new RuntimeException('Stripe did not return a billing portal URL.');
        }

        return (string) $session->url;
    }

    /** @return list<array<string, mixed>> */
    private function lineItemsForOrderItem(OrderItem $item, Order $order): array
    {
        $priceData = [
            'currency' => strtolower($item->currency),
            'unit_amount' => (int) round((float) $item->amount * 100),
            'product_data' => [
                'name' => $item->product_name,
                'description' => $item->price_name ?: "Order {$order->order_number}",
            ],
        ];

        if ($item->billing_cycle !== BillingCycle::OneTime) {
            $priceData['recurring'] = [
                'interval' => $item->billing_cycle === BillingCycle::Monthly ? 'month' : 'year',
            ];
        }

        $items = [['quantity' => 1, 'price_data' => $priceData]];

        if ((float) $item->setup_fee > 0) {
            $items[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($item->currency),
                    'unit_amount' => (int) round((float) $item->setup_fee * 100),
                    'product_data' => ['name' => "{$item->product_name} setup fee"],
                ],
            ];
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function lineItemsForOrder(Order $order): array
    {
        $priceData = [
            'currency' => strtolower($order->currency),
            'unit_amount' => (int) round((float) $order->amount * 100),
            'product_data' => [
                'name' => $order->product->name,
                'description' => "Order {$order->order_number}",
            ],
        ];

        if ($order->billing_cycle !== BillingCycle::OneTime) {
            $priceData['recurring'] = [
                'interval' => $order->billing_cycle === BillingCycle::Monthly ? 'month' : 'year',
            ];
        }

        $items = [['quantity' => 1, 'price_data' => $priceData]];

        if ((float) $order->setup_fee > 0) {
            $items[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => (int) round((float) $order->setup_fee * 100),
                    'product_data' => ['name' => "{$order->product->name} setup fee"],
                ],
            ];
        }

        return $items;
    }
}
