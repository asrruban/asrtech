<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\GatewayWebhookEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PaymentReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_event_is_recorded_and_duplicate_delivery_is_not_processed_twice(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->paidPayload($order, 'evt_reliability_1');

        $this->postJson('/gateways/callback/stripe', $payload)->assertOk();
        $this->postJson('/gateways/callback/stripe', $payload)
            ->assertOk()
            ->assertHeader('X-Webhook-Duplicate', 'true');

        $event = GatewayWebhookEvent::query()->sole();

        $this->assertSame(GatewayWebhookEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame('evt_reliability_1', $event->external_id);
        $this->assertSame('checkout.session.completed', $event->event_type);
        $this->assertSame(1, $event->attempts);
        $this->assertSame(1, $event->duplicate_count);
        $this->assertNotNull($event->verified_at);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(1, $order->transactions()->count());
    }

    public function test_invalid_signature_is_failed_and_cannot_be_replayed(): void
    {
        app(SettingService::class)->put('gateway.stripe.webhook_secret', 'whsec_test');

        $this->postJson('/gateways/callback/stripe', [
            'id' => 'evt_invalid_signature',
            'type' => 'checkout.session.completed',
            'data' => ['object' => []],
        ])->assertStatus(400);

        $event = GatewayWebhookEvent::query()->sole();
        $this->assertSame(GatewayWebhookEvent::STATUS_FAILED, $event->status);
        $this->assertSame(400, $event->response_code);
        $this->assertSame('Invalid signature', $event->last_error);
        $this->assertNull($event->verified_at);
        $this->assertFalse($event->canReplay());
    }

    public function test_admin_can_monitor_webhooks_and_recent_transactions(): void
    {
        $order = $this->pendingOrder();
        $this->postJson('/gateways/callback/stripe', $this->paidPayload($order, 'evt_dashboard'));

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/payments')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Payments/Index')
                ->where('stats.events_24h', 1)
                ->where('stats.processed_24h', 1)
                ->where('stats.failed_24h', 0)
                ->where('events.data.0.external_id', 'evt_dashboard')
                ->where('events.data.0.can_replay', true)
                ->where('transactions.0.order.order_number', $order->order_number));
    }

    public function test_admin_event_detail_redacts_secrets(): void
    {
        $event = GatewayWebhookEvent::query()->create([
            'gateway' => 'stripe',
            'external_id' => 'evt_secret_fields',
            'event_type' => 'test.event',
            'status' => GatewayWebhookEvent::STATUS_PROCESSED,
            'payload' => ['client_secret' => 'do-not-render', 'safe' => 'visible'],
            'headers' => ['stripe-signature' => 'do-not-render', 'source-ip' => '127.0.0.1'],
            'payload_hash' => hash('sha256', 'payload'),
            'attempts' => 1,
            'verified_at' => now(),
            'processed_at' => now(),
            'last_received_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/payments/webhooks/{$event->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Payments/Show')
                ->where('event.payload.client_secret', '[REDACTED]')
                ->where('event.payload.safe', 'visible')
                ->where('event.headers.stripe-signature', '[REDACTED]')
                ->where('event.can_replay', true));
    }

    public function test_admin_can_replay_a_verified_event(): void
    {
        $order = $this->pendingOrder();
        $payload = $this->paidPayload($order, 'evt_replay');
        $event = GatewayWebhookEvent::query()->create([
            'gateway' => 'stripe',
            'external_id' => 'evt_replay',
            'event_type' => 'checkout.session.completed',
            'status' => GatewayWebhookEvent::STATUS_FAILED,
            'payload' => $payload,
            'headers' => [],
            'payload_hash' => hash('sha256', json_encode($payload)),
            'attempts' => 1,
            'response_code' => 500,
            'last_error' => 'Temporary processing failure',
            'verified_at' => now(),
            'last_received_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->post("/admin/payments/webhooks/{$event->id}/replay")
            ->assertRedirect("/admin/payments/webhooks/{$event->id}");

        $event->refresh();
        $this->assertSame(GatewayWebhookEvent::STATUS_PROCESSED, $event->status);
        $this->assertSame(2, $event->attempts);
        $this->assertNull($event->last_error);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(1, $order->transactions()->count());
    }

    public function test_payment_reliability_pages_require_admin_authentication(): void
    {
        $event = GatewayWebhookEvent::query()->create([
            'gateway' => 'stripe',
            'external_id' => 'evt_private',
            'status' => GatewayWebhookEvent::STATUS_PENDING,
            'payload_hash' => hash('sha256', 'private'),
        ]);

        $this->get('/admin/payments')->assertRedirect('/admin/login');
        $this->get("/admin/payments/webhooks/{$event->id}")->assertRedirect('/admin/login');
        $this->post("/admin/payments/webhooks/{$event->id}/replay")->assertRedirect('/admin/login');
    }

    private function pendingOrder(): Order
    {
        $category = Category::query()->create([
            'name' => 'Modules',
            'slug' => 'modules',
            'status' => true,
        ]);
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
            'order_number' => 'ORD-'.strtoupper(fake()->unique()->bothify('########????')),
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => OrderStatus::Pending,
            'payment_method' => 'stripe',
        ]);
    }

    /** @return array<string, mixed> */
    private function paidPayload(Order $order, string $eventId): array
    {
        return [
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => "cs_{$eventId}",
                'payment_status' => 'paid',
                'client_reference_id' => (string) $order->id,
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Payment Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ]);
    }
}
