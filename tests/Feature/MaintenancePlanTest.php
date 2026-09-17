<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\User;
use App\Payments\Gateway;
use App\Payments\GatewayRegistry;
use App\Payments\PaymentResult;
use App\Services\CheckoutService;
use App\Services\MaintenancePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MaintenancePlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_public_catalog_starts_empty_and_only_shows_published_real_plans(): void
    {
        $this->get('/maintenance')->assertOk()->assertInertia(fn (Assert $page) => $page->has('plans', 0));
        $draft = $this->plan();
        $draft->update(['published' => false]);
        $this->get('/maintenance/'.$draft->slug)->assertNotFound();
        $this->get('/maintenance')->assertInertia(fn (Assert $page) => $page->has('plans', 0));
        $draft->update(['published' => true]);
        $this->get('/maintenance')->assertInertia(fn (Assert $page) => $page->has('plans', 1)->where('plans.0.billing', null));
    }

    public function test_plan_authoring_requires_billing_permission_and_an_available_recurring_checkout_price(): void
    {
        $price = $this->price();
        $payload = $this->planData() + ['product_price_id' => $price->id];
        $this->actingAs($this->admin(AdminRole::Support), 'admin')->post('/admin/maintenance/plans', $payload)->assertForbidden();
        $this->actingAs($this->admin(AdminRole::Catalog), 'admin')->post('/admin/maintenance/plans', $payload)->assertForbidden();
        $this->actingAs($this->admin(AdminRole::Billing), 'admin');
        foreach ([['billing_cycle' => 'one_time'], ['enabled' => false], ['purchase_url' => 'https://example.test/pay']] as $change) {
            $price->update(['billing_cycle' => 'monthly', 'enabled' => true, 'purchase_url' => null, ...$change]);
            $this->post('/admin/maintenance/plans', $payload)->assertSessionHasErrors('product_price_id');
        }
        $price->update(['billing_cycle' => 'yearly', 'enabled' => true, 'purchase_url' => null]);
        $this->post('/admin/maintenance/plans', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('maintenance_plans', ['product_price_id' => $price->id, 'published' => true]);
        $price->product->update(['status' => false]);
        $this->get('/maintenance/test-maintenance')->assertInertia(fn (Assert $page) => $page->where('plan.available', false));
    }

    public function test_customer_accepts_current_scope_and_cannot_duplicate_open_requests(): void
    {
        $plan = $this->plan();
        $user = User::factory()->create();
        $this->post('/maintenance/'.$plan->slug.'/request', $this->requestData($plan))->assertRedirect('/login');
        $this->actingAs($user);
        $data = $this->requestData($plan);
        $this->post('/maintenance/'.$plan->slug.'/request', [...$data, 'acknowledge_scope' => false])->assertSessionHasErrors('acknowledge_scope');
        $this->post('/maintenance/'.$plan->slug.'/request', [...$data, 'version' => 'stale'])->assertSessionHasErrors('version');
        $this->post('/maintenance/'.$plan->slug.'/request', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/maintenance/'.$plan->slug.'/request', $data)->assertSessionHasErrors('requirements');
        $this->assertDatabaseCount('maintenance_requests', 1);
        $this->assertSame($plan->scope, MaintenanceRequest::query()->sole()->plan_snapshot['scope']);
        $plan->update(['scope' => 'This is a different maintenance agreement.']);
        $this->assertNotSame($plan->scope, MaintenanceRequest::query()->sole()->plan_snapshot['scope']);
        $other = User::factory()->create();
        $this->flushSession();
        $this->actingAs($other)->post('/maintenance/'.$plan->slug.'/request', $data)->assertSessionHasErrors('version');
    }

    public function test_owner_boundaries_withdrawal_and_private_admin_notes(): void
    {
        $entry = $this->entry();
        $entry->update(['internal_notes' => 'Private staff note.']);
        $this->actingAs(User::factory()->create());
        $this->get('/client-area/maintenance/'.$entry->id)->assertNotFound();
        $this->post('/client-area/maintenance/'.$entry->id.'/withdraw')->assertNotFound();
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout')->assertNotFound();
        $this->get('/client-area/maintenance')->assertOk()->assertInertia(fn (Assert $page) => $page->has('requests', 0));
        $this->flushSession();
        $this->actingAs($entry->user)->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->missing('request.internal_notes'));
        $this->post('/client-area/maintenance/'.$entry->id.'/withdraw')->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $entry->fresh()->status);
        $this->actingAs($this->admin(), 'admin')->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'reviewing'])->assertSessionHasErrors('status');
        $entry->update(['status' => 'awaiting_payment']);
        $this->actingAs($entry->user)->post('/client-area/maintenance/'.$entry->id.'/withdraw')->assertSessionHasErrors('status');
    }

    public function test_price_changes_disable_checkout_and_old_scope_is_not_overwritten(): void
    {
        $price = $this->price();
        $entry = $this->entry($this->plan($price));
        $entry->update(['status' => 'awaiting_payment']);
        $this->flushSession();
        $this->actingAs($entry->user)->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->where('request.checkout_url', route('account.maintenance.checkout', $entry)));
        $price->update(['price' => 50]);
        $this->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->where('request.checkout_url', null)->where('request.plan.billing.amount', '29.00'));
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout')->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_billing_links_reject_wrong_customer_price_amount_and_inactive_subscription(): void
    {
        $price = $this->price();
        $entry = $this->entry($this->plan($price));
        $otherUserSubscription = $this->subscription(User::factory()->create(), $price);
        $otherPriceSubscription = $this->subscription($entry->user, $this->price());
        $matched = $this->subscription($entry->user, $price);
        $this->actingAs($this->admin(), 'admin');
        foreach ([$otherUserSubscription, $otherPriceSubscription] as $invalid) {
            $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'subscription_id' => $invalid->id])->assertSessionHasErrors('subscription_id');
        }
        $matched->update(['amount' => 28]);
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'subscription_id' => $matched->id])->assertSessionHasErrors('subscription_id');
        $matched->update(['amount' => 29, 'status' => 'past_due']);
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'subscription_id' => $matched->id])->assertSessionHasErrors('status');
        $matched->update(['status' => 'active']);
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'subscription_id' => $matched->id])->assertSessionHasNoErrors();
        $this->assertSame('active', $entry->fresh()->status);
        $this->actingAs($this->admin(AdminRole::Support), 'admin')->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'paused', 'subscription_id' => null])->assertForbidden();
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'paused', 'subscription_id' => $matched->id])->assertSessionHasNoErrors();
    }

    public function test_custom_priced_plan_requires_customers_accepted_paid_quote_and_draft_stays_private(): void
    {
        $entry = $this->entry();
        $price = $this->price();
        $order = app(CheckoutService::class)->manual($entry->user, $price->product, $price, false);
        $quote = Quote::query()->create(['quote_number' => 'MAINT-QUOTE', 'user_id' => $entry->user_id, 'status' => QuoteStatus::Draft, 'currency' => 'USD', 'subtotal' => 29, 'tax_amount' => 0, 'total' => 29, 'order_id' => $order->id]);
        $entry->update(['quote_id' => $quote->id]);
        $this->flushSession();
        $this->actingAs($entry->user)->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->where('request.quote', null));
        $this->actingAs($this->admin(), 'admin');
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'quote_id' => $quote->id])->assertSessionHasErrors('status');
        $quote->update(['status' => QuoteStatus::Converted]);
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'quote_id' => $quote->id])->assertSessionHasErrors('status');
        $order->update(['status' => OrderStatus::Paid]);
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'quote_id' => $quote->id])->assertSessionHasNoErrors();
        $this->assertSame('active', $entry->fresh()->status);
        $quote->update(['user_id' => User::factory()->create()->id]);
        $this->patch('/admin/maintenance/requests/'.$entry->id, ['status' => 'active', 'quote_id' => $quote->id])->assertSessionHasErrors('quote_id');
    }

    public function test_checkout_uses_existing_review_and_prevents_repeat_payment_for_same_request(): void
    {
        $entry = $this->entry($this->plan($this->price()));
        $entry->update(['status' => 'awaiting_payment']);
        $this->actingAs($entry->user);
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout')->assertRedirect(route('account.maintenance.checkout.review', $entry));
        $this->get('/client-area/maintenance/'.$entry->id.'/checkout')->assertInertia(fn (Assert $page) => $page->component('Client/Checkout/Create')->where('checkoutUrl', route('account.maintenance.checkout.pay', $entry))->where('cart.total', '29.00'));
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasNoErrors()->assertRedirect(route('account.maintenance.show', $entry));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertNotNull($entry->fresh()->subscription_id);
        $this->assertSame('awaiting_payment', $entry->fresh()->status);
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_pending_gateway_redirect_can_resume_without_creating_a_second_order(): void
    {
        $entry = $this->entry($this->plan($this->price()));
        $entry->update(['status' => 'awaiting_payment']);
        $this->fakeGateway(PaymentResult::redirect('sandbox', 'https://payments.example.test/session/maintenance', 'session-1'));
        $this->actingAs($entry->user)->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertRedirect('https://payments.example.test/session/maintenance');
        $this->assertDatabaseCount('orders', 1);
        $this->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->where('request.checkout_url', null)->where('request.checkout_resume_url', 'https://payments.example.test/session/maintenance')->where('request.checkout_order.status', 'pending'));
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 1);
        app(CheckoutService::class)->markPaid($entry->fresh()->checkoutOrder, 'sandbox', 'settled-session');
        $this->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->where('request.checkout_resume_url', null)->where('request.subscription.status', 'active'));
    }

    public function test_pending_checkout_for_another_request_with_the_same_price_cannot_create_a_second_charge(): void
    {
        $price = $this->price();
        $entry = $this->entry($this->plan($price));
        $entry->update(['status' => 'awaiting_payment']);
        $other = $this->entry($this->plan($price));
        $other->update(['user_id' => $entry->user_id, 'status' => 'awaiting_payment']);
        $this->fakeGateway(PaymentResult::redirect('sandbox', 'https://payments.example.test/session/maintenance', 'session-1'));
        $this->actingAs($entry->user)->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertRedirect();
        $this->post('/client-area/maintenance/'.$other->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_uncertain_gateway_exception_reserves_order_and_blocks_automatic_recharge(): void
    {
        $entry = $this->entry($this->plan($this->price()));
        $entry->update(['status' => 'awaiting_payment']);
        $gateway = Mockery::mock(Gateway::class);
        $gateway->shouldReceive('charge')->once()->andThrow(new RuntimeException('Simulated network timeout'));
        $this->instance(GatewayRegistry::class, Mockery::mock(GatewayRegistry::class, function ($mock) use ($gateway): void {
            $mock->shouldReceive('enabledKeys')->andReturn(['sandbox']);
            $mock->shouldReceive('findEnabled')->once()->with('sandbox')->andReturn($gateway);
        }));
        $this->actingAs($entry->user)->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertNotNull($entry->fresh()->checkout_order_id);
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_confirmed_failed_payment_can_be_retried_but_existing_subscription_cannot_be_rebought(): void
    {
        $price = $this->price();
        $entry = $this->entry($this->plan($price));
        $entry->update(['status' => 'awaiting_payment']);
        $this->fakeGateway(PaymentResult::failure('sandbox', 'Test decline'), 2);
        $this->actingAs($entry->user)->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertSame(OrderStatus::Failed, $entry->fresh()->checkoutOrder->status);
        $this->post('/client-area/maintenance/'.$entry->id.'/checkout/pay', ['gateway' => 'sandbox'])->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 2);
        $this->app->forgetInstance(GatewayRegistry::class);
        $this->subscription($entry->user, $price);
        $this->get('/client-area/maintenance/'.$entry->id)->assertInertia(fn (Assert $page) => $page->where('request.checkout_url', null));
    }

    public function test_product_authoring_preserves_price_id_and_retires_removed_agreed_prices(): void
    {
        $price = $this->price();
        $plan = $this->plan($price);
        $entry = $this->entry($plan);
        $this->actingAs($this->admin(AdminRole::Catalog), 'admin');
        $data = ['category_id' => $price->product->category_id, 'name' => 'Edited service product', 'slug' => $price->product->slug, 'type' => 'license', 'status' => true, 'featured' => false, 'seo' => ['robots' => 'index,follow', 'twitter_card' => 'summary'], 'prices' => [['billing_cycle' => 'monthly', 'currency' => 'USD', 'price' => 31, 'setup_fee' => 0, 'enabled' => true]]];
        $this->put('/admin/products/'.$price->product_id, $data)->assertSessionHasNoErrors();
        $this->assertSame($price->id, $plan->fresh()->product_price_id);
        $this->assertSame('31.00', $price->fresh()->price);
        $data['prices'][0]['billing_cycle'] = 'yearly';
        $this->put('/admin/products/'.$price->product_id, $data)->assertSessionHasNoErrors();
        $this->assertFalse($price->fresh()->enabled);
        $this->assertSame('29.00', $entry->fresh()->plan_snapshot['billing']['amount']);
    }

    private function fakeGateway(PaymentResult $result, int $calls = 1): void
    {
        $gateway = Mockery::mock(Gateway::class);
        $gateway->shouldReceive('charge')->times($calls)->andReturn($result);
        $this->instance(GatewayRegistry::class, Mockery::mock(GatewayRegistry::class, function ($mock) use ($gateway, $calls): void {
            $mock->shouldReceive('enabledKeys')->andReturn(['sandbox']);
            $mock->shouldReceive('findEnabled')->times($calls)->with('sandbox')->andReturn($gateway);
        }));
    }

    private function subscription(User $user, ProductPrice $price): Subscription
    {
        app(CheckoutService::class)->manual($user, $price->product, $price, true);

        return $user->subscriptions()->latest('id')->firstOrFail();
    }

    private function price(): ProductPrice
    {
        $category = Category::query()->firstOrCreate(['slug' => 'maintenance'], ['name' => 'Maintenance', 'status' => true]);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Maintenance test product', 'slug' => 'maintenance-'.fake()->unique()->numerify('######'), 'type' => 'license', 'price' => 29, 'status' => true]);

        return $product->prices()->create(['billing_cycle' => 'monthly', 'currency' => 'USD', 'price' => 29, 'setup_fee' => 0, 'enabled' => true]);
    }

    private function plan(?ProductPrice $price = null): MaintenancePlan
    {
        return MaintenancePlan::query()->create([...$this->planData(), 'slug' => 'maintenance-'.fake()->unique()->numerify('######'), 'product_price_id' => $price?->id]);
    }

    /** @return array<string, mixed> */
    private function planData(): array
    {
        return ['name' => 'Test Maintenance', 'slug' => 'test-maintenance', 'platform' => 'wordpress', 'summary' => 'A configured test scope.', 'scope' => 'Update and maintain the agreed website installation.', 'exclusions' => 'Custom development requires a separate agreement.', 'support_arrangements' => null, 'published' => true];
    }

    /** @return array<string, mixed> */
    private function requestData(MaintenancePlan $plan): array
    {
        return ['requirements' => 'Please maintain our WordPress installation and its configured plugins.', 'acknowledge_scope' => true, 'version' => app(MaintenancePlanService::class)->planPayload($plan)['version']];
    }

    private function entry(?MaintenancePlan $plan = null): MaintenanceRequest
    {
        $plan ??= $this->plan();

        return MaintenanceRequest::query()->create(['maintenance_plan_id' => $plan->id, 'user_id' => User::factory()->create()->id, 'plan_snapshot' => app(MaintenancePlanService::class)->planPayload($plan), 'requirements' => 'Maintain our website and review installed extensions.', 'status' => 'requested', 'scope_acknowledged_at' => now()]);
    }

    private function admin(AdminRole $role = AdminRole::SuperAdmin): Admin
    {
        return Admin::query()->create(['name' => 'Maintenance Tester', 'email' => fake()->unique()->safeEmail(), 'password' => 'password', 'role' => $role]);
    }
}
