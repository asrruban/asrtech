<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Mail\InvoiceMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminInvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_and_filter_invoices(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->get('/admin/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Invoices/Index')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.invoice_number', $invoice->invoice_number));

        $this->get('/admin/invoices?status=paid')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('invoices.data', 0));

        $this->get('/admin/invoices?search='.$invoice->order->user->email)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('invoices.data', 1));
    }

    public function test_admin_can_download_an_invoice_pdf(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $response = $this->get("/admin/invoices/{$invoice->id}/download");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload("{$invoice->invoice_number}.pdf");
    }

    public function test_marking_an_invoice_paid_settles_the_order_and_provisions_the_license(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();
        $order = $invoice->order;

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(0, $order->license()->count());

        $this->post("/admin/invoices/{$invoice->id}/mark-paid")
            ->assertRedirect("/admin/users/{$order->user_id}/invoice/{$invoice->id}");

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->due_at);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(LicenseStatus::Active, $order->license()->sole()->status);
    }

    public function test_marking_paid_twice_is_idempotent(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->post("/admin/invoices/{$invoice->id}/mark-paid");
        $this->post("/admin/invoices/{$invoice->id}/mark-paid");

        $this->assertSame(1, $invoice->order->license()->count());
    }

    public function test_issued_invoices_can_be_voided_but_paid_ones_cannot(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->post("/admin/invoices/{$invoice->id}/void");
        $this->assertSame(InvoiceStatus::Void, $invoice->fresh()->status);

        // Void invoices cannot be marked paid afterwards.
        $this->post("/admin/invoices/{$invoice->id}/mark-paid")
            ->assertSessionHasErrors('invoice');

        $paid = $this->makePendingInvoice('other-product');
        $this->post("/admin/invoices/{$paid->id}/mark-paid");
        $this->post("/admin/invoices/{$paid->id}/void")
            ->assertSessionHasErrors('invoice');
        $this->assertSame(InvoiceStatus::Paid, $paid->fresh()->status);
    }

    public function test_manage_page_shows_ledger_and_is_user_scoped(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();
        $userId = $invoice->order->user_id;

        $this->post("/admin/invoices/{$invoice->id}/mark-paid");

        $this->get("/admin/users/{$userId}/invoice/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Invoices/Show')
                ->where('invoice.invoice_number', $invoice->invoice_number)
                ->has('transactions', 1)
                ->where('transactions.0.type', 'payment')
                ->where('transactions.0.gateway', 'manual'));

        // Old URL redirects to the canonical nested route.
        $this->get("/admin/invoices/{$invoice->id}")
            ->assertRedirect("/admin/users/{$userId}/invoice/{$invoice->id}");

        // An invoice cannot be opened under a different user.
        $stranger = User::factory()->create();
        $this->get("/admin/users/{$stranger->id}/invoice/{$invoice->id}")
            ->assertNotFound();
    }

    public function test_add_payment_records_a_transaction_with_reference(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->post("/admin/invoices/{$invoice->id}/add-payment", [
            'gateway' => 'bkash',
            'reference' => 'TXN-778899',
        ])->assertRedirect("/admin/users/{$invoice->order->user_id}/invoice/{$invoice->id}");

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $invoice->order->fresh()->status);
        $this->assertSame(1, $invoice->order->license()->count());

        $transaction = $invoice->order->transactions()->sole();
        $this->assertSame('bkash', $transaction->gateway);
        $this->assertSame('TXN-778899', $transaction->reference);
        $this->assertSame('149.00', $transaction->amount);
    }

    public function test_paid_invoices_can_be_refunded(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();
        $this->post("/admin/invoices/{$invoice->id}/mark-paid");

        $this->post("/admin/invoices/{$invoice->id}/refund");

        $this->assertSame(InvoiceStatus::Refunded, $invoice->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $invoice->order->fresh()->status);
        $this->assertSame(2, $invoice->order->transactions()->count());
        $this->assertSame('refund', $invoice->order->transactions()->latest('id')->first()->type);
    }

    public function test_unpaid_invoices_cannot_be_refunded(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->post("/admin/invoices/{$invoice->id}/refund")
            ->assertSessionHasErrors('invoice');
    }

    public function test_admin_can_save_invoice_notes(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->patch("/admin/invoices/{$invoice->id}/notes", [
            'notes' => 'Paid via bKash agent 017XXXXXXXX.',
        ]);

        $this->assertSame('Paid via bKash agent 017XXXXXXXX.', $invoice->fresh()->notes);
    }

    public function test_admin_can_email_the_invoice_with_pdf(): void
    {
        Mail::fake();
        $this->actingAs($this->admin(), 'admin');
        $invoice = $this->makePendingInvoice();

        $this->post("/admin/invoices/{$invoice->id}/send")
            ->assertRedirect("/admin/users/{$invoice->order->user_id}/invoice/{$invoice->id}");

        Mail::assertSent(
            InvoiceMail::class,
            fn (InvoiceMail $mail) => $mail->hasTo($invoice->order->user->email),
        );
    }

    public function test_invoice_pages_require_an_admin_session(): void
    {
        $invoice = $this->makePendingInvoice();

        $this->get('/admin/invoices')->assertRedirect('/admin/login');
        $this->get("/admin/invoices/{$invoice->id}/download")->assertRedirect('/admin/login');
        $this->post("/admin/invoices/{$invoice->id}/mark-paid")->assertRedirect('/admin/login');
    }

    private function makePendingInvoice(string $slug = 'automation-toolkit'): Invoice
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );
        $product = Product::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $category->id,
                'name' => ucwords(str_replace('-', ' ', $slug)),
                'type' => 'whmcs_module',
                'price' => 149,
                'status' => true,
                'featured' => false,
            ],
        );
        $user = User::factory()->create();
        $order = $user->orders()->create([
            'product_id' => $product->id,
            'order_number' => 'ORD-'.strtoupper(substr(md5($slug.$user->id), 0, 12)),
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => OrderStatus::Pending,
        ]);

        return app(InvoiceService::class)->createForOrder($order);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Billing Admin',
            'email' => 'billing@example.com',
            'password' => 'a-secure-password',
        ]);
    }
}
