<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_and_open_users(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create(['name' => 'Al Amin']);

        $this->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Al Amin'));

        $this->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('user.name', 'Al Amin')
                ->has('products'));
    }

    public function test_admin_can_create_a_paid_order_which_provisions_license_and_invoice(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        [$product, $price] = $this->makeProduct();

        $this->post("/admin/users/{$user->id}/orders", [
            'product_id' => $product->id,
            'product_price_id' => $price->id,
            'mark_paid' => true,
            'complimentary' => false,
        ])->assertRedirect("/admin/users/{$user->id}");

        $order = $user->orders()->sole();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('manual', $order->payment_method);
        $this->assertSame('149.00', $order->amount);
        $this->assertSame(LicenseStatus::Active, $user->licenses()->sole()->status);
        $this->assertSame(InvoiceStatus::Paid, $order->invoice()->sole()->status);
    }

    public function test_admin_can_create_a_pending_order_without_license(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        [$product, $price] = $this->makeProduct();

        $this->post("/admin/users/{$user->id}/orders", [
            'product_id' => $product->id,
            'product_price_id' => $price->id,
            'mark_paid' => false,
            'complimentary' => false,
        ]);

        $this->assertSame(OrderStatus::Pending, $user->orders()->sole()->status);
        $this->assertSame(0, $user->licenses()->count());
    }

    public function test_admin_can_assign_a_product_as_complimentary_license(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        [$product] = $this->makeProduct();

        $this->post("/admin/users/{$user->id}/orders", [
            'product_id' => $product->id,
            'product_price_id' => null,
            'mark_paid' => true,
            'complimentary' => true,
        ])->assertRedirect("/admin/users/{$user->id}");

        $order = $user->orders()->sole();
        $this->assertSame('0.00', $order->amount);
        $this->assertSame('complimentary', $order->payment_method);

        $license = $user->licenses()->sole();
        $this->assertSame(LicenseStatus::Active, $license->status);
        $this->assertNull($license->expires_at);
    }

    public function test_prices_from_other_products_are_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        [$product] = $this->makeProduct();
        [, $foreignPrice] = $this->makeProduct(slug: 'other-product');

        $this->post("/admin/users/{$user->id}/orders", [
            'product_id' => $product->id,
            'product_price_id' => $foreignPrice->id,
            'mark_paid' => true,
            'complimentary' => false,
        ])->assertSessionHasErrors('product_price_id');
    }

    public function test_admin_can_create_and_view_an_invoice_for_a_pending_order(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $user = User::factory()->create();
        [$product, $price] = $this->makeProduct();

        $this->post("/admin/users/{$user->id}/orders", [
            'product_id' => $product->id,
            'product_price_id' => $price->id,
            'mark_paid' => false,
            'complimentary' => false,
        ]);

        $order = $user->orders()->sole();
        $this->assertNull($order->invoice);

        $this->post("/admin/orders/{$order->id}/invoice")
            ->assertRedirect();

        $invoice = $order->invoice()->sole();
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{5}$/', $invoice->invoice_number);
        $this->assertNotNull($invoice->due_at);

        $this->get("/admin/invoices/{$invoice->id}")
            ->assertRedirect("/admin/users/{$user->id}/invoice/{$invoice->id}");

        $this->get("/admin/users/{$user->id}/invoice/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Invoices/Show')
                ->where('invoice.invoice_number', $invoice->invoice_number));
    }

    public function test_client_checkout_also_generates_an_invoice(): void
    {
        $user = User::factory()->create();
        [$product, $price] = $this->makeProduct();

        $this->actingAs($user)->post("/checkout/{$product->slug}/prices/{$price->id}");

        $this->assertSame(1, Invoice::query()->count());
        $this->assertSame(InvoiceStatus::Paid, Invoice::query()->sole()->status);
    }

    public function test_user_pages_require_an_admin_session(): void
    {
        $user = User::factory()->create();

        $this->get('/admin/users')->assertRedirect('/admin/login');
        $this->get("/admin/users/{$user->id}")->assertRedirect('/admin/login');
        $this->post("/admin/users/{$user->id}/orders", [])->assertRedirect('/admin/login');
    }

    /** @return array{0: Product, 1: ProductPrice} */
    private function makeProduct(string $slug = 'automation-toolkit'): array
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);

        $price = $product->prices()->create([
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => 149,
            'setup_fee' => 0,
            'enabled' => true,
        ]);

        return [$product, $price];
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
