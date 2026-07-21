<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\LicenseStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundAndCreditNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_issue_partial_then_full_refunds_with_tax_credit_notes(): void
    {
        [$invoice, $admin] = $this->paidInvoiceWithTax();
        $order = $invoice->order;

        $this->actingAs($admin, 'admin')->post("/admin/invoices/{$invoice->id}/refund", [
            'amount' => 55,
            'reason' => 'Customer returned half the license quantity.',
            'idempotency_key' => (string) Str::uuid(),
            'record_only' => false,
            'revoke_access' => false,
        ])->assertRedirect("/admin/users/{$order->user_id}/invoice/{$invoice->id}");

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyRefunded, $invoice->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $first = $invoice->refunds()->sole();
        $this->assertSame(RefundStatus::Succeeded, $first->status);
        $this->assertSame('55.00', $first->amount);
        $this->assertSame('50.00', $first->creditNote->net_amount);
        $this->assertSame('5.00', $first->creditNote->tax_amount);
        $this->assertSame('55.00', $first->transaction->amount);
        $this->assertSame(55.0, $order->fresh()->refundableAmount());

        $this->actingAs($admin, 'admin')->post("/admin/invoices/{$invoice->id}/refund", [
            'amount' => 55,
            'reason' => 'Complete the approved refund.',
            'idempotency_key' => (string) Str::uuid(),
            'record_only' => false,
            'revoke_access' => true,
        ]);

        $this->assertSame(InvoiceStatus::Refunded, $invoice->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame(0.0, $order->fresh()->refundableAmount());
        $this->assertSame(2, $invoice->refunds()->count());
        $this->assertSame(2, $invoice->creditNotes()->count());
        $this->assertSame(LicenseStatus::Terminated, $order->licenses()->sole()->status);
    }

    public function test_refund_idempotency_prevents_duplicate_gateway_and_ledger_entries(): void
    {
        [$invoice, $admin] = $this->paidInvoiceWithTax();
        $key = (string) Str::uuid();
        $service = app(RefundService::class);

        $first = $service->issue($invoice, $admin, 20, 'Duplicate request test', $key);
        $second = $service->issue($invoice->fresh(), $admin, 20, 'Duplicate request test', $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $invoice->refunds()->count());
        $this->assertSame(1, $invoice->creditNotes()->count());
        $this->assertSame(1, $invoice->order->transactions()->where('type', 'refund')->count());
    }

    public function test_refund_cannot_exceed_remaining_paid_amount(): void
    {
        [$invoice, $admin] = $this->paidInvoiceWithTax();

        $this->actingAs($admin, 'admin')->post("/admin/invoices/{$invoice->id}/refund", [
            'amount' => 110.01,
            'reason' => 'Too much',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, $invoice->refunds()->count());
    }

    public function test_manual_payment_requires_explicit_record_only_confirmation(): void
    {
        [$invoice, $admin] = $this->paidInvoiceWithTax();
        $invoice->order->update(['payment_method' => 'bank_transfer']);

        $this->actingAs($admin, 'admin')->post("/admin/invoices/{$invoice->id}/refund", [
            'amount' => 10,
            'reason' => 'External bank refund',
            'idempotency_key' => (string) Str::uuid(),
            'record_only' => false,
        ])->assertSessionHasErrors('record_only');

        $this->actingAs($admin, 'admin')->post("/admin/invoices/{$invoice->id}/refund", [
            'amount' => 10,
            'reason' => 'External bank refund',
            'idempotency_key' => (string) Str::uuid(),
            'record_only' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->assertTrue($invoice->refunds()->sole()->record_only);
    }

    public function test_customer_can_view_and_download_own_credit_note_only(): void
    {
        [$invoice, $admin] = $this->paidInvoiceWithTax();
        $creditNote = app(RefundService::class)
            ->issue($invoice, $admin, 20, 'Customer credit note', (string) Str::uuid())
            ->creditNote;
        $customer = $invoice->order->user;

        $this->actingAs($customer)
            ->get("/client-area/credit-notes/{$creditNote->id}")
            ->assertOk();
        $this->actingAs($customer)
            ->get("/client-area/credit-notes/{$creditNote->id}/download")
            ->assertOk()
            ->assertDownload("{$creditNote->credit_note_number}.pdf");

        $this->actingAs(User::factory()->create())
            ->get("/client-area/credit-notes/{$creditNote->id}")
            ->assertNotFound();
    }

    /** @return array{Invoice, Admin} */
    private function paidInvoiceWithTax(): array
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Automation Toolkit',
            'slug' => 'automation-toolkit',
            'type' => 'whmcs_module',
            'price' => 100,
            'status' => true,
            'featured' => false,
        ]);
        $price = $product->prices()->create([
            'billing_cycle' => 'one_time',
            'currency' => 'USD',
            'price' => 100,
            'setup_fee' => 0,
            'enabled' => true,
        ]);
        TaxRate::query()->create([
            'name' => 'Sales tax',
            'country_code' => 'US',
            'rate' => 10,
            'priority' => 1,
            'active' => true,
        ]);
        $user = User::factory()->create(['country' => 'US']);
        $outcome = app(CheckoutService::class)->purchase($user, $product, $price, 'sandbox');
        $invoice = $outcome->order->invoice()->sole();
        $admin = Admin::query()->create([
            'name' => 'Refund Admin',
            'email' => 'refunds@example.com',
            'password' => 'password',
        ]);

        return [$invoice, $admin];
    }
}
