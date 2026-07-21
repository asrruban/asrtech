<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_a_product_plan_and_review_the_cart(): void
    {
        [$product, $price] = $this->makeProduct('automation-toolkit', 149);

        $this->post("/cart/{$product->slug}/prices/{$price->id}")
            ->assertRedirect('/cart')
            ->assertSessionHas('storefront.cart.price_ids', [$price->id]);

        $this->get('/cart')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Cart/Index')
                ->has('cart.items', 1)
                ->where('cart.items.0.product.slug', $product->slug)
                ->where('cart.total', '149.00'));
    }

    public function test_add_to_cart_can_return_to_the_product_and_update_the_session(): void
    {
        [$product, $price] = $this->makeProduct('automation-toolkit', 149);

        $this->post("/cart/{$product->slug}/prices/{$price->id}", [
            'stay_on_product' => true,
        ])
            ->assertRedirect("/products/whmcs/{$product->slug}")
            ->assertSessionHas('storefront.cart.price_ids', [$price->id]);
    }

    public function test_adding_another_plan_for_the_same_product_replaces_the_old_plan(): void
    {
        [$product, $price] = $this->makeProduct('automation-toolkit', 149);
        $annual = $product->prices()->create([
            'billing_cycle' => 'yearly',
            'currency' => 'USD',
            'price' => 99,
            'sale_price' => 79,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        $this->post("/cart/{$product->slug}/prices/{$price->id}");
        $this->post("/cart/{$product->slug}/prices/{$annual->id}")
            ->assertSessionHas('storefront.cart.price_ids', [$annual->id]);
    }

    public function test_cart_rejects_a_price_from_another_product(): void
    {
        [$product] = $this->makeProduct('automation-toolkit', 149);
        [, $foreignPrice] = $this->makeProduct('backup-manager', 79);

        $this->post("/cart/{$product->slug}/prices/{$foreignPrice->id}")
            ->assertNotFound();
    }

    public function test_guest_checkout_redirects_to_login_and_preserves_the_cart(): void
    {
        [$product, $price] = $this->makeProduct('automation-toolkit', 149);
        $this->post("/cart/{$product->slug}/prices/{$price->id}");

        $this->get('/checkout')
            ->assertRedirect('/login')
            ->assertSessionHas('storefront.cart.price_ids', [$price->id]);
    }

    public function test_authenticated_customer_can_checkout_multiple_cart_items(): void
    {
        [$automation, $automationPrice] = $this->makeProduct('automation-toolkit', 50, sale: 40, setupFee: 5);
        [$backup, $backupPrice] = $this->makeProduct('backup-manager', 80);
        $user = User::factory()->create();

        $this->post("/cart/{$automation->slug}/prices/{$automationPrice->id}");
        $this->post("/cart/{$backup->slug}/prices/{$backupPrice->id}");

        $this->actingAs($user)
            ->get('/checkout')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Checkout/Create')
                ->has('cart.items', 2)
                ->where('cart.subtotal', '120.00')
                ->where('cart.setup_fee', '5.00')
                ->where('cart.total', '125.00')
                ->where('paymentGateways.0.key', 'sandbox'));

        $this->actingAs($user)
            ->post('/checkout', ['gateway' => 'sandbox'])
            ->assertRedirect('/client-area')
            ->assertSessionMissing('storefront.cart.price_ids');

        $order = $user->orders()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('120.00', $order->amount);
        $this->assertSame('5.00', $order->setup_fee);
        $this->assertCount(2, $order->items);
        $this->assertSame(2, $user->licenses()->count());
        $this->assertEqualsCanonicalizing(
            [$automation->id, $backup->id],
            $user->licenses()->pluck('product_id')->all(),
        );
    }

    public function test_cart_items_can_be_removed_and_cleared(): void
    {
        [$automation, $automationPrice] = $this->makeProduct('automation-toolkit', 50);
        [$backup, $backupPrice] = $this->makeProduct('backup-manager', 80);
        $this->post("/cart/{$automation->slug}/prices/{$automationPrice->id}");
        $this->post("/cart/{$backup->slug}/prices/{$backupPrice->id}");

        $this->delete("/cart/items/{$automationPrice->id}")
            ->assertSessionHas('storefront.cart.price_ids', [$backupPrice->id]);
        $this->delete('/cart')
            ->assertSessionMissing('storefront.cart.price_ids');
    }

    /** @return array{0: Product, 1: ProductPrice} */
    private function makeProduct(
        string $slug,
        float $amount,
        ?float $sale = null,
        float $setupFee = 0,
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
            'price' => $sale ?? $amount,
            'status' => true,
            'featured' => false,
        ]);
        $price = $product->prices()->create([
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => $amount,
            'sale_price' => $sale,
            'setup_fee' => $setupFee,
            'enabled' => true,
        ]);

        return [$product, $price];
    }
}
