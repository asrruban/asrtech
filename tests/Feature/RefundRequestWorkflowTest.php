<?php

namespace Tests\Feature;

use App\Enums\RefundRequestStatus;
use App\Enums\RefundStatus;
use App\Mail\RefundRequestDecisionMail;
use App\Mail\RefundRequestReceivedMail;
use App\Mail\RefundRequestSubmittedAdminMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_an_idempotent_refund_request_and_billing_is_notified(): void
    {
        Mail::fake();
        [$invoice, $customer, $admin] = $this->paidInvoice();
        $key = (string) Str::uuid();
        $payload = [
            'amount' => 40,
            'reason' => 'The module does not fit the workflow we purchased it for.',
            'idempotency_key' => $key,
        ];

        $this->actingAs($customer)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", $payload)
            ->assertRedirect();
        $this->actingAs($customer)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", $payload)
            ->assertSessionDoesntHaveErrors();

        $refundRequest = RefundRequest::query()->sole();
        $this->assertSame(RefundRequestStatus::Pending, $refundRequest->status);
        $this->assertSame('40.00', $refundRequest->amount);
        $this->assertStringStartsWith('RR-', $refundRequest->request_number);
        Mail::assertSent(RefundRequestReceivedMail::class, 1);
        Mail::assertSent(RefundRequestSubmittedAdminMail::class, fn ($mail) => $mail->hasTo($admin->email));

        $this->actingAs($customer)
            ->get("/client-area/invoice/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('refundPolicy.can_request', false)
                ->where('invoice.refund_requests.0.request_number', $refundRequest->request_number));
    }

    public function test_request_policy_enforces_ownership_window_balance_and_one_pending_request(): void
    {
        [$invoice, $customer] = $this->paidInvoice();
        $other = User::factory()->create();
        $payload = [
            'amount' => 20,
            'reason' => 'A sufficiently detailed customer refund reason.',
            'idempotency_key' => (string) Str::uuid(),
        ];

        $this->actingAs($other)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", $payload)
            ->assertNotFound();

        $invoice->order->update(['paid_at' => now()->subDays(31)]);
        $this->actingAs($customer)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", $payload)
            ->assertSessionHasErrors('refund_request');

        $invoice->order->update(['paid_at' => now()]);
        $this->actingAs($customer)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", $payload)
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($customer)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", [
                ...$payload,
                'idempotency_key' => (string) Str::uuid(),
            ])->assertSessionHasErrors('refund_request');
        $this->actingAs($customer)
            ->post("/client-area/invoice/{$invoice->id}/refund-requests", [
                ...$payload,
                'amount' => 1000,
                'idempotency_key' => (string) Str::uuid(),
            ])->assertSessionHasErrors('refund_request');
    }

    public function test_admin_approval_processes_refund_and_links_credit_note_to_request(): void
    {
        Mail::fake();
        [$invoice, $customer, $admin] = $this->paidInvoice();
        $this->submit($invoice, $customer, 60);
        $refundRequest = RefundRequest::query()->sole();

        $this->actingAs($admin, 'admin')
            ->post("/admin/refund-requests/{$refundRequest->id}/approve", [
                'record_only' => false,
                'revoke_access' => false,
                'admin_note' => 'Approved under the 30-day refund policy.',
            ])->assertRedirect();

        $refundRequest->refresh();
        $this->assertSame(RefundRequestStatus::Approved, $refundRequest->status);
        $this->assertSame($admin->id, $refundRequest->decided_by);
        $this->assertSame(RefundStatus::Succeeded, $refundRequest->refund->status);
        $this->assertNotNull($refundRequest->refund->creditNote);
        $this->assertSame('60.00', $refundRequest->refund->amount);
        Mail::assertSent(RefundRequestDecisionMail::class, fn ($mail) => $mail->hasTo($customer->email));

        $this->actingAs($admin, 'admin')
            ->get("/admin/refund-requests/{$refundRequest->id}")
            ->assertOk();
    }

    public function test_customer_can_cancel_and_admin_can_reject_pending_requests(): void
    {
        Mail::fake();
        [$invoice, $customer, $admin] = $this->paidInvoice();
        $this->submit($invoice, $customer, 25);
        $first = RefundRequest::query()->sole();

        $this->actingAs($customer)
            ->delete("/client-area/refund-requests/{$first->id}")
            ->assertRedirect();
        $this->assertSame(RefundRequestStatus::Cancelled, $first->fresh()->status);

        $this->submit($invoice, $customer, 25);
        $second = RefundRequest::query()->latest('id')->firstOrFail();
        $this->actingAs($admin, 'admin')
            ->post("/admin/refund-requests/{$second->id}/reject", [
                'admin_note' => 'The request is outside the product refund exclusions.',
            ])->assertRedirect();

        $this->assertSame(RefundRequestStatus::Rejected, $second->fresh()->status);
        $this->assertSame(0, $invoice->refunds()->count());
        Mail::assertSent(RefundRequestDecisionMail::class, 2);
    }

    private function submit(Invoice $invoice, User $customer, float $amount): void
    {
        $this->actingAs($customer)->post("/client-area/invoice/{$invoice->id}/refund-requests", [
            'amount' => $amount,
            'reason' => 'The customer provided a complete refund request explanation.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionDoesntHaveErrors();
    }

    /** @return array{Invoice, User, Admin} */
    private function paidInvoice(): array
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'refund-request-modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Refund Request Product',
            'slug' => 'refund-request-product',
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
        $customer = User::factory()->create();
        $invoice = app(CheckoutService::class)->purchase($customer, $product, $price, 'sandbox')->order->invoice()->sole();
        $admin = Admin::query()->create([
            'name' => 'Billing Admin',
            'email' => 'refund-requests@example.com',
            'password' => 'password',
        ]);

        return [$invoice, $customer, $admin];
    }
}
