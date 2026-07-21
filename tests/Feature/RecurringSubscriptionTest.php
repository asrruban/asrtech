<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\SubscriptionCancellationScheduledMail;
use App\Mail\SubscriptionPaymentFailedMail;
use App\Mail\SubscriptionRenewalReminderMail;
use App\Mail\SubscriptionRenewedMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\Stripe\StripeGateway;
use App\Services\CheckoutService;
use App\Services\LicenseVerificationService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Tests\TestCase;

class RecurringSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_checkout_creates_an_active_subscription_for_the_provisioned_license(): void
    {
        [$product, $price] = $this->productAndPrice('monthly', 29);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox'])
            ->assertRedirect('/client-area');

        $subscription = $user->subscriptions()->sole();
        $license = $user->licenses()->sole();

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame($license->id, $subscription->license_id);
        $this->assertSame('sandbox', $subscription->gateway);
        $this->assertSame('29.00', $subscription->amount);
        $this->assertTrue($subscription->current_period_end->isSameDay(now()->addMonth()));
        $this->assertTrue($license->expires_at->isSameDay($subscription->current_period_end));
    }

    public function test_one_time_checkout_does_not_create_a_subscription(): void
    {
        [$product, $price] = $this->productAndPrice('one_time', 149);
        $user = User::factory()->create();

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}");

        $this->assertSame(0, $user->subscriptions()->count());
        $this->assertNull($user->licenses()->sole()->expires_at);
    }

    public function test_stripe_checkout_event_attaches_provider_subscription_and_customer_ids(): void
    {
        $subscription = $this->sandboxSubscription();
        $order = $subscription->order;
        $subscription->delete();
        $order->forceFill(['status' => 'pending', 'paid_at' => null, 'payment_method' => 'stripe'])->save();
        $order->licenses()->delete();

        $this->postJson('/gateways/callback/stripe', [
            'id' => 'evt_checkout_subscription',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_sub_123',
                'payment_status' => 'paid',
                'subscription' => 'sub_123',
                'customer' => 'cus_123',
                'client_reference_id' => (string) $order->id,
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ])->assertOk();

        $created = Subscription::query()->sole();
        $this->assertSame('stripe', $created->gateway);
        $this->assertSame('sub_123', $created->gateway_subscription_id);
        $this->assertSame('cus_123', $created->gateway_customer_id);
        $this->assertDatabaseHas('subscription_events', [
            'gateway_event_id' => 'evt_checkout_subscription',
            'event_type' => 'checkout.session.completed',
        ]);
    }

    public function test_paid_renewal_webhook_extends_the_same_license_and_is_idempotent(): void
    {
        Mail::fake();
        $subscription = $this->sandboxSubscription();
        $subscription->update([
            'gateway' => 'stripe',
            'gateway_subscription_id' => 'sub_renew_123',
            'gateway_customer_id' => 'cus_renew_123',
        ]);
        $licenseId = $subscription->license_id;
        $periodStart = now()->addMonth()->startOfDay();
        $periodEnd = $periodStart->addMonth();
        $payload = [
            'id' => 'evt_renewal_paid',
            'type' => 'invoice.paid',
            'data' => ['object' => [
                'id' => 'in_renew_123',
                'billing_reason' => 'subscription_cycle',
                'subscription' => 'sub_renew_123',
                'customer' => 'cus_renew_123',
                'amount_paid' => 2900,
                'lines' => ['data' => [[
                    'period' => ['start' => $periodStart->timestamp, 'end' => $periodEnd->timestamp],
                ]]],
            ]],
        ];

        $this->postJson('/gateways/callback/stripe', $payload)->assertOk();
        $this->postJson('/gateways/callback/stripe', $payload)->assertOk();

        $subscription->refresh();
        $renewalOrder = $subscription->renewalOrders()->sole();
        $this->assertSame('in_renew_123', $renewalOrder->payment_reference);
        $this->assertNotNull($renewalOrder->invoice);
        $this->assertSame(1, $renewalOrder->transactions()->count());
        $this->assertSame(1, $subscription->renewalOrders()->count());
        $this->assertSame($licenseId, $subscription->license_id);
        $this->assertSame(LicenseStatus::Active, $subscription->license->fresh()->status);
        $this->assertTrue($subscription->license->fresh()->expires_at->equalTo($periodEnd));
        $this->assertTrue($subscription->current_period_end->equalTo($periodEnd));
        Mail::assertSent(
            SubscriptionRenewedMail::class,
            fn (SubscriptionRenewedMail $mail): bool => $mail->hasTo($subscription->user->email),
        );
        Mail::assertSentCount(1);
    }

    public function test_failed_payment_and_subscription_deletion_update_access_status(): void
    {
        Mail::fake();
        $subscription = $this->sandboxSubscription();
        $subscription->update([
            'gateway' => 'stripe',
            'gateway_subscription_id' => 'sub_status_123',
            'gateway_customer_id' => 'cus_status_123',
        ]);

        $this->postJson('/gateways/callback/stripe', [
            'id' => 'evt_payment_failed',
            'type' => 'invoice.payment_failed',
            'data' => ['object' => ['id' => 'in_failed', 'subscription' => 'sub_status_123']],
        ])->assertOk();
        $this->postJson('/gateways/callback/stripe', [
            'id' => 'evt_payment_failed',
            'type' => 'invoice.payment_failed',
            'data' => ['object' => ['id' => 'in_failed', 'subscription' => 'sub_status_123']],
        ])->assertOk();
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->fresh()->status);
        Mail::assertSent(SubscriptionPaymentFailedMail::class, 1);

        $this->actingAs($subscription->user)
            ->get('/client-area/subscriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('subscriptions.0.failed_payments_count', 1)
                ->where('subscriptions.0.payment_attention_required', true)
                ->where('subscriptions.0.can_update_payment_method', true)
                ->where('subscriptions.0.last_payment_failure_at', fn ($value) => is_string($value)));

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('subscriptions.data.0.failed_payments_count', 1)
                ->where('subscriptions.data.0.last_payment_failure_at', fn ($value) => is_string($value)));

        $this->postJson('/gateways/callback/stripe', [
            'id' => 'evt_subscription_deleted',
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => [
                'id' => 'sub_status_123',
                'status' => 'canceled',
                'ended_at' => now()->timestamp,
                'current_period_end' => now()->timestamp,
            ]],
        ])->assertOk();

        $this->assertSame(SubscriptionStatus::Canceled, $subscription->fresh()->status);
        $this->assertSame(LicenseStatus::Expired, $subscription->license->fresh()->status);
    }

    public function test_customer_can_open_the_stripe_payment_method_portal_for_their_subscription(): void
    {
        $subscription = $this->sandboxSubscription();
        $subscription->update([
            'gateway' => 'stripe',
            'gateway_customer_id' => 'cus_portal_123',
            'gateway_subscription_id' => 'sub_portal_123',
        ]);
        $portalUrl = 'https://billing.stripe.com/p/session/test_portal';
        $stripe = \Mockery::mock(StripeGateway::class)->makePartial();
        $stripe->shouldReceive('billingPortalUrl')
            ->once()
            ->withArgs(fn (Subscription $candidate): bool => $candidate->is($subscription))
            ->andReturn($portalUrl);
        $this->app->instance(StripeGateway::class, $stripe);

        $this->actingAs($subscription->user)
            ->withHeader('X-Inertia', 'true')
            ->post("/client-area/subscriptions/{$subscription->id}/billing-portal")
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', $portalUrl);

        $stranger = User::factory()->create();
        $this->actingAs($stranger)
            ->post("/client-area/subscriptions/{$subscription->id}/billing-portal")
            ->assertNotFound();
    }

    public function test_customer_can_schedule_and_resume_local_subscription_cancellation(): void
    {
        Mail::fake();
        $subscription = $this->sandboxSubscription();

        $this->actingAs($subscription->user)
            ->post("/client-area/subscriptions/{$subscription->id}/cancel")
            ->assertRedirect();
        $this->assertTrue($subscription->fresh()->cancel_at_period_end);
        $this->assertSame(LicenseStatus::Active, $subscription->license->fresh()->status);
        Mail::assertSent(SubscriptionCancellationScheduledMail::class, 1);

        $this->actingAs($subscription->user)
            ->post("/client-area/subscriptions/{$subscription->id}/resume")
            ->assertRedirect();
        $this->assertFalse($subscription->fresh()->cancel_at_period_end);
    }

    public function test_subscription_pages_are_private_and_visible_to_customer_and_admin(): void
    {
        $subscription = $this->sandboxSubscription();
        $stranger = User::factory()->create();
        $admin = $this->admin();

        $this->actingAs($subscription->user)
            ->get('/client-area/subscriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Subscriptions')
                ->has('subscriptions', 1)
                ->where('subscriptions.0.id', $subscription->id)
                ->where('subscriptions.0.details_url', route('account.subscriptions.show', $subscription)));

        $this->actingAs($subscription->user)
            ->get("/client-area/subscriptions/{$subscription->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Subscription')
                ->where('subscription.id', $subscription->id)
                ->has('events')
                ->has('renewals'));

        $this->actingAs($stranger)
            ->post("/client-area/subscriptions/{$subscription->id}/cancel")
            ->assertNotFound();
        $this->actingAs($stranger)
            ->get("/client-area/subscriptions/{$subscription->id}")
            ->assertNotFound();

        $this->actingAs($admin, 'admin')
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Subscriptions/Index')
                ->has('subscriptions.data', 1)
                ->where('subscriptions.data.0.id', $subscription->id));

        $this->actingAs($admin, 'admin')
            ->get("/admin/subscriptions/{$subscription->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Subscriptions/Show')
                ->where('subscription.id', $subscription->id)
                ->has('events')
                ->has('renewals'));
    }

    public function test_renewal_reminder_is_sent_only_once_for_each_billing_period(): void
    {
        Mail::fake();
        config([
            'asrtech.subscriptions.reminders_enabled' => true,
            'asrtech.subscriptions.reminder_days' => 40,
        ]);
        $subscription = $this->sandboxSubscription();

        $this->artisan('subscriptions:send-renewal-reminders')->assertSuccessful();
        $this->artisan('subscriptions:send-renewal-reminders')->assertSuccessful();

        Mail::assertSent(
            SubscriptionRenewalReminderMail::class,
            fn (SubscriptionRenewalReminderMail $mail): bool => $mail->hasTo($subscription->user->email),
        );
        Mail::assertSentCount(1);
        $this->assertDatabaseCount('subscription_events', 1);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => 'subscription.renewal_reminder_sent',
        ]);
    }

    public function test_failed_payment_keeps_license_active_until_configured_grace_period_ends(): void
    {
        Mail::fake();
        config(['asrtech.subscriptions.grace_days' => 4]);
        $subscription = $this->sandboxSubscription();
        $periodEnd = now();
        $subscription->update(['current_period_end' => $periodEnd]);
        $subscription->license->update(['expires_at' => $periodEnd]);

        app(SubscriptionService::class)->markPaymentFailed(
            $subscription,
            'evt_grace_period',
            'in_grace_period',
        );

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertSame(LicenseStatus::Active, $subscription->license->fresh()->status);
        $this->assertTrue(
            $subscription->license->fresh()->expires_at->isSameSecond($periodEnd->copy()->addDays(4)),
        );
        $this->assertSame('active', app(LicenseVerificationService::class)
            ->verify($subscription->license->license_key, null, null, null)['status']);

        $this->travel(5)->days();
        app(SubscriptionService::class)->endDueSubscriptions();

        $this->assertSame(LicenseStatus::Expired, $subscription->license->fresh()->status);
        $this->assertSame('expired', app(LicenseVerificationService::class)
            ->verify($subscription->license->license_key, null, null, null)['status']);
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $subscription->id,
            'event_type' => 'subscription.grace_expired',
        ]);
    }

    public function test_scheduled_cancellation_expires_access_at_period_end(): void
    {
        $subscription = $this->sandboxSubscription();
        $subscription->update([
            'cancel_at_period_end' => true,
            'canceled_at' => now()->subMonth(),
            'current_period_end' => now()->subMinute(),
        ]);

        $this->assertSame(1, app(SubscriptionService::class)->endDueSubscriptions());
        $this->assertSame(SubscriptionStatus::Canceled, $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->ended_at);
        $this->assertSame(LicenseStatus::Expired, $subscription->license->fresh()->status);
    }

    public function test_existing_paid_recurring_orders_can_be_backfilled(): void
    {
        $subscription = $this->sandboxSubscription();
        $subscription->delete();

        $this->assertSame(1, app(SubscriptionService::class)->backfillPaidOrders());
        $this->assertSame(1, Subscription::query()->count());
        $this->assertSame(0, app(SubscriptionService::class)->backfillPaidOrders());
    }

    public function test_checkout_rejects_multiple_recurring_plans_in_one_order(): void
    {
        [, $monthly] = $this->productAndPrice('monthly', 29, 'monthly-plan');
        [, $yearly] = $this->productAndPrice('yearly', 199, 'yearly-plan');
        $user = User::factory()->create();

        try {
            app(CheckoutService::class)->purchaseCart($user, collect([$monthly, $yearly]), 'sandbox');
            $this->fail('Expected recurring cart validation to reject multiple plans.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Checkout supports one recurring subscription plan at a time.', $exception->getMessage());
        }

        $this->assertSame(0, $user->orders()->count());
    }

    private function sandboxSubscription(): Subscription
    {
        [$product, $price] = $this->productAndPrice('monthly', 29, 'subscription-product-'.fake()->unique()->numerify('###'));
        $user = User::factory()->create();

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox']);

        return $user->subscriptions()->with(['order', 'license', 'user'])->sole();
    }

    /** @return array{Product, ProductPrice} */
    private function productAndPrice(string $cycle, float $amount, string $slug = 'automation-toolkit'): array
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => str($slug)->headline(),
            'slug' => $slug,
            'type' => 'whmcs_module',
            'price' => $amount,
            'status' => true,
            'featured' => false,
        ]);
        $price = $product->prices()->create([
            'billing_cycle' => $cycle,
            'currency' => 'USD',
            'price' => $amount,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        return [$product, $price];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Subscription Admin',
            'email' => 'subscriptions@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
