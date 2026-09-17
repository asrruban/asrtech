<?php

namespace Tests\Feature;

use App\Jobs\DeliverWebhookDelivery;
use App\Models\Admin;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutboundWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_creates_deliveries_only_for_subscribed_endpoints(): void
    {
        $subscribed = $this->endpoint(['order.paid']);
        $unsubscribed = $this->endpoint(['refund.decided']);

        Http::fake(['*' => Http::response('ok', 200)]);

        $created = app(WebhookDispatcher::class)->dispatch('order.paid', ['order_number' => 'ORD-1']);

        $this->assertSame(1, $created);
        $this->assertSame(1, $subscribed->deliveries()->count());
        $this->assertSame(0, $unsubscribed->deliveries()->count());
    }

    public function test_delivery_posts_signed_payload_and_marks_success(): void
    {
        Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

        $endpoint = $this->endpoint(['order.paid']);
        $delivery = $endpoint->deliveries()->create([
            'event' => 'order.paid',
            'payload' => ['order_number' => 'ORD-9'],
            'status' => 'pending',
        ]);

        (new DeliverWebhookDelivery($delivery->id))->handle();

        $delivery->refresh();
        $this->assertSame('success', $delivery->status);
        $this->assertSame(200, $delivery->response_code);
        $this->assertNotNull($delivery->delivered_at);

        Http::assertSent(function ($request) use ($endpoint): bool {
            $body = (string) $request->body();
            $expected = 'sha256='.hash_hmac('sha256', $body, $endpoint->secret);

            return $request->hasHeader('X-ASRTech-Signature', $expected)
                && $request->hasHeader('X-ASRTech-Event', 'order.paid');
        });
    }

    public function test_failed_response_marks_delivery_failed(): void
    {
        Http::fake(['https://hooks.example.com/*' => Http::response('nope', 500)]);

        $endpoint = $this->endpoint(['order.paid']);
        $delivery = $endpoint->deliveries()->create([
            'event' => 'order.paid',
            'payload' => ['order_number' => 'ORD-9'],
            'status' => 'pending',
        ]);

        $job = new DeliverWebhookDelivery($delivery->id);

        try {
            $job->handle();
        } catch (\Throwable) {
            // release() throws outside a queue worker; ignore in tests.
        }

        $this->assertSame('failed', $delivery->refresh()->status);
        $this->assertSame(500, $delivery->response_code);
    }

    public function test_admin_can_manage_endpoints(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/webhooks')
            ->assertOk();

        $this->actingAs($admin, 'admin')
            ->post('/admin/settings/webhooks', [
                'name' => 'Zapier',
                'url' => 'https://hooks.example.com/asr',
                'events' => ['order.paid'],
            ])
            ->assertRedirect('/admin/settings/webhooks');

        $endpoint = WebhookEndpoint::query()->sole();
        $this->assertTrue($endpoint->subscribesTo('order.paid'));

        $this->actingAs($admin, 'admin')
            ->patch("/admin/settings/webhooks/{$endpoint->id}", ['enabled' => false])
            ->assertRedirect('/admin/settings/webhooks');

        $this->assertFalse($endpoint->fresh()->enabled);
    }

    private function endpoint(array $events): WebhookEndpoint
    {
        return WebhookEndpoint::query()->create([
            'name' => 'Hook '.fake()->unique()->numerify('##'),
            'url' => 'https://hooks.example.com/asr',
            'secret' => WebhookEndpoint::generateSecret(),
            'events' => $events,
            'enabled' => true,
        ]);
    }
}
