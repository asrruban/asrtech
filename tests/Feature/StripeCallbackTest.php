<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_session_completed_webhook_marks_the_order_paid(): void
    {
        $order = $this->makePendingStripeOrder();

        $this->postJson('/gateways/callback/stripe', $this->sessionCompletedPayload($order))
            ->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('stripe', $order->payment_method);
        $this->assertSame('cs_test_abc', $order->payment_reference);
        $this->assertSame(LicenseStatus::Active, $order->license()->sole()->status);
        $this->assertNotNull($order->invoice);
    }

    public function test_webhook_is_idempotent_for_already_paid_orders(): void
    {
        $order = $this->makePendingStripeOrder();

        $this->postJson('/gateways/callback/stripe', $this->sessionCompletedPayload($order))->assertOk();
        $this->postJson('/gateways/callback/stripe', $this->sessionCompletedPayload($order))->assertOk();

        $this->assertSame(1, $order->license()->count());
        $this->assertSame(1, $order->invoice()->count());
    }

    public function test_unpaid_sessions_do_not_mark_orders_paid(): void
    {
        $order = $this->makePendingStripeOrder();

        $payload = $this->sessionCompletedPayload($order);
        $payload['data']['object']['payment_status'] = 'unpaid';

        $this->postJson('/gateways/callback/stripe', $payload)->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, $order->license()->count());
    }

    public function test_return_redirects_paid_orders_to_the_account_page(): void
    {
        $order = $this->makePendingStripeOrder();
        $this->postJson('/gateways/callback/stripe', $this->sessionCompletedPayload($order));

        $this->actingAs($order->user)
            ->get('/gateways/return/stripe?session_id=cs_test_abc')
            ->assertRedirect('/client-area');
    }

    public function test_unknown_gateway_callbacks_are_rejected(): void
    {
        $this->postJson('/gateways/callback/nonexistent', [])->assertNotFound();
        $this->postJson('/gateways/callback/paypal', [])->assertNotFound();
    }

    private function makePendingStripeOrder(): Order
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Automation Toolkit',
            'slug' => 'automation-toolkit',
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);
        $user = User::factory()->create();

        return Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-20260721-TEST01',
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => OrderStatus::Pending,
            'payment_method' => 'stripe',
            'payment_reference' => 'cs_test_abc',
        ]);
    }

    /** @return array<string, mixed> */
    private function sessionCompletedPayload(Order $order): array
    {
        return [
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_abc',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_123',
                    'client_reference_id' => (string) $order->id,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
            ],
        ];
    }
}
