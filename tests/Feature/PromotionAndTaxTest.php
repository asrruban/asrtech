<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PromotionCode;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\CommercePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PromotionAndTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_applies_and_removes_a_valid_promotion_code(): void
    {
        [$product, $price] = $this->makeProduct(100);
        $this->promotion('SAVE20', 'percentage', 20);

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->post('/cart/promotion', ['code' => 'save20'])
            ->assertRedirect('/cart')
            ->assertSessionHas('storefront.cart.promotion_code', 'SAVE20');

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page
            ->where('cart.subtotal', '100.00')
            ->where('cart.discount_amount', '20.00')
            ->where('cart.total', '80.00')
            ->where('cart.promotion.code', 'SAVE20'));

        $this->delete('/cart/promotion')
            ->assertSessionMissing('storefront.cart.promotion_code');
    }

    public function test_checkout_snapshots_discount_then_calculates_tax_and_redeems_code(): void
    {
        [$product, $price] = $this->makeProduct(100, setupFee: 10);
        $this->promotion('SAVE20', 'percentage', 20);
        TaxRate::query()->create([
            'name' => 'California sales tax',
            'country_code' => 'US',
            'state' => 'CA',
            'rate' => 10,
            'priority' => 10,
            'active' => true,
        ]);
        $user = User::factory()->create(['country' => 'US', 'state' => 'CA']);

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->actingAs($user)->post('/cart/promotion', ['code' => 'SAVE20']);
        $this->actingAs($user)->post('/checkout', ['gateway' => 'sandbox'])->assertRedirect('/client-area');

        $order = $user->orders()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('100.00', $order->subtotal);
        $this->assertSame('20.00', $order->discount_amount);
        $this->assertSame('80.00', $order->amount);
        $this->assertSame('10.00', $order->setup_fee);
        $this->assertSame('9.00', $order->tax_amount);
        $this->assertSame(99.0, $order->totalAmount());
        $this->assertSame('99.00', $order->transactions()->sole()->amount);
        $this->assertSame('redeemed', $order->promotionRedemption()->sole()->status);
    }

    public function test_most_specific_tax_rate_wins_and_inactive_rates_are_ignored(): void
    {
        [, $price] = $this->makeProduct(100);
        TaxRate::query()->create(['name' => 'Global', 'rate' => 2, 'priority' => 100, 'active' => true]);
        TaxRate::query()->create(['name' => 'US', 'country_code' => 'US', 'rate' => 5, 'priority' => 1, 'active' => true]);
        TaxRate::query()->create(['name' => 'California', 'country_code' => 'US', 'state' => 'CA', 'rate' => 8.25, 'priority' => 0, 'active' => true]);
        TaxRate::query()->create(['name' => 'Disabled', 'country_code' => 'US', 'state' => 'CA', 'rate' => 99, 'priority' => 999, 'active' => false]);
        $user = User::factory()->create(['country' => 'US', 'state' => 'CA']);

        $quote = app(CommercePricingService::class)->quote(collect([$price]), $user);

        $this->assertSame('California', $quote->taxRate?->name);
        $this->assertSame(8.25, $quote->taxAmount);
        $this->assertSame(108.25, $quote->total);
    }

    public function test_product_scope_minimum_and_usage_limits_are_enforced(): void
    {
        [$eligible, $eligiblePrice] = $this->makeProduct(100, slug: 'eligible');
        [$other, $otherPrice] = $this->makeProduct(50, slug: 'other');
        $promotion = $this->promotion('ONLYONE', 'fixed', 25, [
            'currency' => 'USD',
            'minimum_subtotal' => 75,
            'usage_limit' => 1,
        ]);
        $promotion->products()->attach($eligible);

        $pricing = app(CommercePricingService::class);
        $this->assertSame(25.0, $pricing->quote(collect([$eligiblePrice]), null, 'ONLYONE')->discountAmount);

        $this->expectException(\InvalidArgumentException::class);
        $pricing->quote(collect([$otherPrice]), null, 'ONLYONE');
    }

    public function test_per_customer_limit_is_rechecked_at_checkout(): void
    {
        [$product, $price] = $this->makeProduct(100);
        $this->promotion('ONCE', 'percentage', 10, ['per_customer_limit' => 1]);
        $user = User::factory()->create();

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->actingAs($user)->post('/cart/promotion', ['code' => 'ONCE']);
        $this->actingAs($user)->post('/checkout', ['gateway' => 'sandbox']);

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->actingAs($user)->post('/cart/promotion', ['code' => 'ONCE'])
            ->assertSessionHasErrors('code');
    }

    public function test_full_discount_order_is_paid_without_calling_an_external_gateway(): void
    {
        [$product, $price] = $this->makeProduct(100);
        $this->promotion('FREE100', 'percentage', 100);
        $user = User::factory()->create();

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->actingAs($user)->post('/cart/promotion', ['code' => 'FREE100']);
        $this->actingAs($user)->post('/checkout', ['gateway' => 'sandbox'])->assertRedirect('/client-area');

        $order = $user->orders()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('promotion', $order->payment_method);
        $this->assertSame(0.0, $order->totalAmount());
    }

    public function test_recurring_promotion_changes_initial_order_but_not_renewal_price(): void
    {
        [$product, $price] = $this->makeProduct(100, cycle: 'monthly');
        $this->promotion('HALFOFF', 'percentage', 50, ['scope' => 'recurring']);
        $user = User::factory()->create();

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->actingAs($user)->post('/cart/promotion', ['code' => 'HALFOFF']);
        $this->actingAs($user)->post('/checkout', ['gateway' => 'sandbox']);

        $this->assertSame('50.00', $user->orders()->sole()->amount);
        $this->assertSame('100.00', $user->subscriptions()->sole()->amount);
    }

    public function test_billing_admin_can_manage_promotions_and_tax_rates(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Billing Admin',
            'email' => 'billing-promotions@example.com',
            'password' => 'password',
            'role' => AdminRole::Billing,
        ]);
        $this->actingAs($admin, 'admin');

        $this->get('/admin/promotions')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Commerce/Promotions/Index'));
        $this->post('/admin/promotions', [
            'code' => 'launch25',
            'name' => 'Launch offer',
            'discount_type' => 'percentage',
            'value' => 25,
            'currency' => null,
            'minimum_subtotal' => null,
            'maximum_discount' => null,
            'usage_limit' => 100,
            'per_customer_limit' => 1,
            'scope' => 'all',
            'active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'product_ids' => [],
        ])->assertRedirect('/admin/promotions');
        $this->assertDatabaseHas('promotion_codes', ['code' => 'LAUNCH25']);

        $this->post('/admin/tax-rates', [
            'name' => 'Bangladesh VAT',
            'country_code' => 'bd',
            'state' => null,
            'rate' => 15,
            'priority' => 10,
            'active' => true,
        ])->assertRedirect('/admin/tax-rates');
        $this->assertDatabaseHas('tax_rates', ['country_code' => 'BD', 'rate' => 15]);
    }

    public function test_catalog_admin_cannot_manage_promotions_or_tax_rates(): void
    {
        $admin = Admin::query()->create([
            'name' => 'Catalog Admin',
            'email' => 'catalog-promotions@example.com',
            'password' => 'password',
            'role' => AdminRole::Catalog,
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/promotions')->assertForbidden();
        $this->actingAs($admin, 'admin')->get('/admin/tax-rates')->assertForbidden();
    }

    /** @return array{0: Product, 1: ProductPrice} */
    private function makeProduct(float $amount, float $setupFee = 0, string $slug = 'automation-toolkit', string $cycle = 'one_time'): array
    {
        $category = Category::query()->firstOrCreate(['slug' => 'modules'], ['name' => 'Modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => ucwords(str_replace('-', ' ', $slug)),
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
            'setup_fee' => $setupFee,
            'enabled' => true,
        ]);

        return [$product, $price];
    }

    /** @param array<string, mixed> $overrides */
    private function promotion(string $code, string $type, float $value, array $overrides = []): PromotionCode
    {
        return PromotionCode::query()->create([
            'code' => $code,
            'name' => $code,
            'discount_type' => $type,
            'value' => $value,
            'currency' => $type === 'fixed' ? 'USD' : null,
            'scope' => 'all',
            'active' => true,
            'per_customer_limit' => 1,
            ...$overrides,
        ]);
    }
}
