<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Mail\InvoiceMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_removes_users_without_orders_and_skips_buyers(): void
    {
        $admin = $this->admin();
        $withoutOrders = User::factory()->count(2)->create();
        $buyer = User::factory()->create();
        $this->orderFor($buyer);

        $ids = [...$withoutOrders->pluck('id'), $buyer->id];

        $this->actingAs($admin, 'admin')
            ->post('/admin/users/bulk-delete', ['ids' => $ids])
            ->assertRedirect('/admin/users');

        foreach ($withoutOrders as $user) {
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }

        $this->assertDatabaseHas('users', ['id' => $buyer->id]);
    }

    public function test_bulk_remind_emails_issued_invoices_only(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $buyer = User::factory()->create();
        $order = $this->orderFor($buyer);

        $issued = Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-BULK-1',
            'status' => InvoiceStatus::Issued,
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);
        $paid = Invoice::query()->create([
            'order_id' => $this->orderFor($buyer)->id,
            'invoice_number' => 'INV-BULK-2',
            'status' => InvoiceStatus::Paid,
            'issued_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/invoices/bulk-remind', ['ids' => [$issued->id, $paid->id]])
            ->assertRedirect('/admin/invoices');

        Mail::assertQueued(InvoiceMail::class, 1);
        $this->assertNotNull($issued->fresh()->last_reminder_at);
        $this->assertNull($paid->fresh()->last_reminder_at);
    }

    private function orderFor(User $user): Order
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Bulk Product '.fake()->unique()->numerify('###'),
            'slug' => 'bulk-product-'.fake()->unique()->numerify('###'),
            'type' => 'whmcs_module',
            'price' => 10,
            'status' => true,
            'featured' => false,
        ]);

        return $user->orders()->create([
            'product_id' => $product->id,
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'currency' => 'USD',
            'subtotal' => 10,
            'amount' => 10,
            'discount_amount' => 0,
            'setup_fee' => 0,
            'tax_amount' => 0,
            'billing_cycle' => 'one_time',
            'status' => OrderStatus::Paid,
            'payment_method' => 'sandbox',
            'paid_at' => now(),
        ]);
    }

    private function admin(array $attributes = []): Admin
    {
        return Admin::query()->create(array_merge([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ], $attributes));
    }
}
