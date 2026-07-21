<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_account_card_due_invoices_active_products_and_tickets(): void
    {
        $user = User::factory()->create(['name' => 'Client One']);
        $product = $this->product();

        $expired = $this->paidOrder($user, $product);
        $expired->license()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'license_key' => 'ASR-EXPIR-EDEXP-IRED1',
            'status' => 'expired',
        ]);

        $active = $this->paidOrder($user, $product);
        $active->license()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'license_key' => 'ASR-ACTIV-EACTI-VE111',
            'status' => 'active',
        ]);

        // A pending order produces an ISSUED (unpaid, due) invoice.
        $pending = $this->paidOrder($user, $product, amount: '99.00', setupFee: '1.00');
        $pending->forceFill(['status' => 'pending', 'paid_at' => null])->save();
        app(InvoiceService::class)->createForOrder($pending->refresh());

        $this->openTicket($user);

        $this->actingAs($user)
            ->get('/client-area')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Index')
                ->where('account.name', 'Client One')
                ->has('activeProducts', 1)
                ->where('activeProducts.0.product.slug', 'automation-toolkit')
                ->has('dueInvoices', 1)
                ->where('totalDue', '100.00')
                ->has('tickets', 1)
                ->has('orders', 3));
    }

    public function test_nav_badges_are_shared_with_client_pages(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $order = $this->paidOrder($user, $product);
        $order->license()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'license_key' => 'ASR-AAAAA-BBBBB-CCCCC',
            'status' => 'active',
        ]);
        $this->openTicket($user);

        $this->actingAs($user)
            ->get('/client-area')
            ->assertInertia(fn (Assert $page) => $page
                ->where('clientBadges.products', 1)
                ->where('clientBadges.tickets', 1)
                ->where('clientBadges.unpaidInvoices', 0));
    }

    public function test_products_page_lists_services_active_first(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        foreach ([['ASR-EXPIR-EDEXP-IRED1', 'expired'], ['ASR-ACTIV-EACTI-VE111', 'active']] as [$key, $status]) {
            $order = $this->paidOrder($user, $product);
            $order->license()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'license_key' => $key,
                'status' => $status,
            ]);
        }

        $this->actingAs($user)
            ->get('/client-area/products')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Products')
                ->has('services', 2)
                ->where('services.0.status', 'active')
                ->where('services.1.status', 'expired'));
    }

    public function test_product_detail_page_is_private_to_the_owner(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $product = $this->product();
        $order = $this->paidOrder($user, $product);
        $license = $order->license()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'license_key' => 'ASR-AAAAA-BBBBB-CCCCC',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get("/client-area/product/{$license->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Product')
                ->where('service.license_key', 'ASR-AAAAA-BBBBB-CCCCC')
                ->where('service.product.name', 'Automation Toolkit')
                ->where('service.order.order_number', $order->order_number));

        $this->actingAs($stranger)
            ->get("/client-area/product/{$license->id}")
            ->assertNotFound();
    }

    public function test_invoices_page_lists_only_the_users_invoices_with_totals(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $product = $this->product();

        $invoice = app(InvoiceService::class)->createForOrder($this->paidOrder($user, $product, amount: '149.00', setupFee: '10.00'));
        app(InvoiceService::class)->createForOrder($this->paidOrder($stranger, $product));

        $this->actingAs($user)
            ->get('/client-area/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Invoices')
                ->has('invoices', 1)
                ->where('invoices.0.invoice_number', $invoice->invoice_number)
                ->where('invoices.0.total', '159.00'));
    }

    public function test_invoice_detail_page_is_private_to_the_owner(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $invoice = app(InvoiceService::class)
            ->createForOrder($this->paidOrder($user, $this->product(), amount: '149.00', setupFee: '10.00'));

        $this->actingAs($user)
            ->get("/client-area/invoice/{$invoice->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Account/Invoice')
                ->where('invoice.invoice_number', $invoice->invoice_number)
                ->where('invoice.total', '159.00')
                ->where('billTo.name', $user->name));

        $this->actingAs($stranger)
            ->get("/client-area/invoice/{$invoice->id}")
            ->assertNotFound();
    }

    public function test_invoice_download_is_private_to_the_owner(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $invoice = app(InvoiceService::class)
            ->createForOrder($this->paidOrder($user, $this->product()));

        $this->actingAs($stranger)
            ->get("/client-area/invoice/{$invoice->id}/download")
            ->assertNotFound();

        $this->actingAs($user)
            ->get("/client-area/invoice/{$invoice->id}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_old_account_url_redirects_to_the_client_area(): void
    {
        $this->get('/account')->assertRedirect('/client-area');
    }

    public function test_client_area_pages_require_authentication(): void
    {
        $this->get('/client-area/products')->assertRedirect('/login');
        $this->get('/client-area/invoices')->assertRedirect('/login');
    }

    private function product(): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );

        return Product::query()->firstOrCreate(
            ['slug' => 'automation-toolkit'],
            [
                'category_id' => $category->id,
                'name' => 'Automation Toolkit',
                'type' => 'whmcs_module',
                'price' => 149,
                'status' => true,
                'featured' => false,
            ],
        );
    }

    private function paidOrder(User $user, Product $product, string $amount = '149.00', string $setupFee = '0.00'): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'currency' => 'USD',
            'amount' => $amount,
            'setup_fee' => $setupFee,
            'billing_cycle' => 'one_time',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    private function openTicket(User $user): Ticket
    {
        $department = TicketDepartment::query()->firstOrCreate(['name' => 'Support']);

        $ticket = $user->tickets()->create([
            'ticket_number' => Ticket::newTicketNumber(),
            'ticket_department_id' => $department->id,
            'subject' => 'Help needed',
            'status' => TicketStatus::Open,
            'priority' => 'medium',
            'last_reply_at' => now(),
        ]);

        $ticket->replies()->create(['user_id' => $user->id, 'message' => 'Opening message.']);

        return $ticket;
    }
}
