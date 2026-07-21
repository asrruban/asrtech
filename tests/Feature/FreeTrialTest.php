<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeTrialTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_user_can_see_free_trial_gateway_if_enabled(): void
    {
        $product = $this->createTrialProduct();

        // Guest sees it
        $this->get($product->storefrontUrl())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('paymentGateways', fn ($gateways) => collect($gateways)->contains('key', 'free_trial'))
            );

        // Authenticated user who hasn't used trial sees it
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get($product->storefrontUrl())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('paymentGateways', fn ($gateways) => collect($gateways)->contains('key', 'free_trial'))
            );
    }

    public function test_user_cannot_see_free_trial_gateway_if_already_used(): void
    {
        $product = $this->createTrialProduct();
        $user = User::factory()->create();

        // Simulate used trial order
        Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_price_id' => $product->prices->first()->id,
            'order_number' => 'ORD-TRIAL-USED',
            'currency' => 'USD',
            'amount' => 0,
            'setup_fee' => 0,
            'billing_cycle' => 'monthly',
            'status' => OrderStatus::Paid,
            'payment_method' => 'free_trial',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get($product->storefrontUrl())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('paymentGateways', fn ($gateways) => ! collect($gateways)->contains('key', 'free_trial'))
            );
    }

    public function test_checkout_with_free_trial_creates_trialing_subscription(): void
    {
        $product = $this->createTrialProduct();
        $price = $product->prices->first();
        $user = User::factory()->create();

        $now = now();
        \Illuminate\Support\Facades\Date::setTestNow($now);

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", [
                'gateway' => 'free_trial',
            ])
            ->assertRedirect('/client-area');

        $order = Order::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertEquals(0, $order->amount);
        $this->assertSame('free_trial', $order->payment_method);

        $subscription = Subscription::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($subscription);
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->status);
        $this->assertSame('free_trial', $subscription->gateway);
        $this->assertEquals(7, (int) round($now->diffInDays($subscription->current_period_end)));

        $license = $subscription->license;
        $this->assertNotNull($license);
        $this->assertSame(LicenseStatus::Active, $license->status);
        $this->assertEquals(7, (int) round($now->diffInDays($license->expires_at)));

        \Illuminate\Support\Facades\Date::setTestNow(null);
    }

    public function test_checkout_fails_if_trial_already_used(): void
    {
        $product = $this->createTrialProduct();
        $price = $product->prices->first();
        $user = User::factory()->create();

        // Mark as already used
        Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_price_id' => $price->id,
            'order_number' => 'ORD-TRIAL-USED',
            'currency' => 'USD',
            'amount' => 0,
            'setup_fee' => 0,
            'billing_cycle' => 'monthly',
            'status' => OrderStatus::Paid,
            'payment_method' => 'free_trial',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", [
                'gateway' => 'free_trial',
            ])
            ->assertSessionHasErrors(['gateway']);
    }

    public function test_subscription_extension_converts_to_active_subscription(): void
    {
        $product = $this->createTrialProduct();
        $price = $product->prices->first();
        $user = User::factory()->create();

        $now = now();
        \Illuminate\Support\Facades\Date::setTestNow($now);

        // 1. Create a trialing subscription
        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", [
                'gateway' => 'free_trial',
            ]);

        $subscription = Subscription::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);

        // 2. Extend subscription using sandbox gateway
        $this->actingAs($user)
            ->post(route('account.subscriptions.extend.store', $subscription), [
                'gateway' => 'sandbox',
            ])
            ->assertRedirect(route('account.subscriptions.show', $subscription));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('sandbox', $subscription->gateway);
        $expectedDays = (int) round($now->diffInDays($now->copy()->addMonth()));
        $this->assertEquals($expectedDays, (int) round($now->diffInDays($subscription->current_period_end))); // Extended by monthly cycle

        $this->assertSame(LicenseStatus::Active, $subscription->license->status);
        $this->assertEquals($expectedDays, (int) round($now->diffInDays($subscription->license->expires_at)));

        \Illuminate\Support\Facades\Date::setTestNow(null);
    }

    private function createTrialProduct(): Product
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Trial Module',
            'slug' => 'trial-module',
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
            'has_free_trial' => true,
        ]);

        $product->prices()->create([
            'billing_cycle' => BillingCycle::Monthly,
            'name' => 'Monthly Price',
            'currency' => 'USD',
            'price' => 29.00,
            'setup_fee' => 0.00,
            'enabled' => true,
        ]);

        return $product;
    }
}
