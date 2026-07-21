<?php

namespace Tests\Feature;

use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_buying(): void
    {
        [$product, $price] = $this->makeProduct();

        $this->post("/checkout/{$product->slug}/prices/{$price->id}")
            ->assertRedirect('/login');
    }

    public function test_paid_purchase_automatically_creates_an_active_license(): void
    {
        [$product, $price] = $this->makeProduct(cycle: 'yearly', amount: 99, sale: 79);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}")
            ->assertRedirect('/client-area');

        $order = $user->orders()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('79.00', $order->amount);
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->payment_reference);

        $license = $user->licenses()->sole();
        $this->assertSame(LicenseStatus::Active, $license->status);
        $this->assertSame($product->id, $license->product_id);
        $this->assertSame($order->id, $license->order_id);
        $this->assertMatchesRegularExpression('/^ASR-[0-9A-Za-z]{5}-[0-9A-Za-z]{5}-[0-9A-Za-z]{5}$/', $license->license_key);
        $this->assertTrue($license->expires_at->isSameDay($order->paid_at->addYear()));
    }

    public function test_one_time_purchase_creates_a_lifetime_license(): void
    {
        [$product, $price] = $this->makeProduct(cycle: 'one_time', amount: 149);
        $user = User::factory()->create();

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}");

        $this->assertNull($user->licenses()->sole()->expires_at);
    }

    public function test_checkout_rejects_gateways_that_are_not_enabled(): void
    {
        [$product, $price] = $this->makeProduct();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'paypal'])
            ->assertSessionHasErrors('gateway');

        $this->assertSame(0, $user->orders()->count());
    }

    public function test_checkout_uses_the_selected_enabled_gateway(): void
    {
        [$product, $price] = $this->makeProduct();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}", ['gateway' => 'sandbox'])
            ->assertRedirect('/client-area');

        $this->assertSame('sandbox', $user->orders()->sole()->payment_method);
    }

    public function test_disabled_prices_cannot_be_purchased(): void
    {
        [$product, $price] = $this->makeProduct(enabled: false);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$price->id}")
            ->assertNotFound();

        $this->assertSame(0, $user->orders()->count());
    }

    public function test_prices_from_other_products_cannot_be_purchased(): void
    {
        [$product] = $this->makeProduct();
        [, $foreignPrice] = $this->makeProduct(slug: 'other-product');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/checkout/{$product->slug}/prices/{$foreignPrice->id}")
            ->assertNotFound();
    }

    public function test_account_page_lists_services_and_orders(): void
    {
        [$product, $price] = $this->makeProduct();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}");

        $this->actingAs($user)
            ->get('/client-area')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Index')
                ->has('activeProducts', 1)
                ->where('activeProducts.0.product.slug', $product->slug)
                ->has('orders', 1)
                ->where('orders.0.status', 'paid')
                ->where('orders.0.license_key', fn ($key) => is_string($key) && str_starts_with($key, 'ASR-')));
    }

    public function test_account_page_requires_authentication(): void
    {
        $this->get('/client-area')->assertRedirect('/login');
    }

    /** @return array{0: Product, 1: ProductPrice} */
    private function makeProduct(
        string $cycle = 'one_time',
        float $amount = 149,
        ?float $sale = null,
        bool $enabled = true,
        string $slug = 'automation-toolkit',
    ): array {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );

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
            'sale_price' => $sale,
            'setup_fee' => 0,
            'enabled' => $enabled,
        ]);

        return [$product, $price];
    }
}
