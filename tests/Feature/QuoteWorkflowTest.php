<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Mail\QuoteMail;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_creates_and_sends_a_quote(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $client = User::factory()->create();
        $product = $this->product();

        $this->actingAs($admin, 'admin')
            ->post('/admin/quotes', [
                'user_id' => $client->id,
                'valid_until' => now()->addDays(14)->toDateString(),
                'tax_rate' => 10,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100, 'billing_cycle' => 'one_time'],
                ],
            ])
            ->assertRedirect('/admin/quotes');

        $quote = Quote::query()->sole();
        $this->assertSame('200.00', $quote->subtotal);
        $this->assertSame('20.00', $quote->tax_amount);
        $this->assertSame('220.00', $quote->total);
        $this->assertSame(QuoteStatus::Draft, $quote->status);

        $this->actingAs($admin, 'admin')
            ->post("/admin/quotes/{$quote->id}/send")
            ->assertRedirect('/admin/quotes');

        Mail::assertQueued(QuoteMail::class, fn ($mail) => $mail->hasTo($client->email));
        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_client_accept_converts_quote_to_order_and_invoice(): void
    {
        $client = User::factory()->create();
        $quote = $this->sentQuote($client);

        $this->actingAs($client)
            ->post("/client-area/quotes/{$quote->id}/accept")
            ->assertRedirect();

        $quote->refresh();
        $this->assertSame(QuoteStatus::Converted, $quote->status);
        $this->assertNotNull($quote->order_id);

        $order = $quote->order;
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(InvoiceStatus::Issued, $order->invoice->status);
        $this->assertSame(1, $client->unreadNotifications()->count());
    }

    public function test_client_can_decline_a_quote(): void
    {
        $client = User::factory()->create();
        $quote = $this->sentQuote($client);

        $this->actingAs($client)
            ->post("/client-area/quotes/{$quote->id}/decline")
            ->assertRedirect('/client-area/quotes');

        $this->assertSame(QuoteStatus::Declined, $quote->fresh()->status);
    }

    public function test_clients_cannot_see_other_peoples_quotes(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $quote = $this->sentQuote($owner);

        $this->actingAs($intruder)->get("/client-area/quotes/{$quote->id}")->assertNotFound();
        $this->actingAs($intruder)->post("/client-area/quotes/{$quote->id}/accept")->assertNotFound();
    }

    public function test_draft_quotes_are_private_until_sent(): void
    {
        $client = User::factory()->create();
        $draft = $this->sentQuote($client);
        $draft->update(['status' => QuoteStatus::Draft, 'admin_note' => 'Unapproved preliminary pricing.']);
        $sent = $this->sentQuote($client);
        $this->actingAs($client)->get('/client-area/quotes')->assertInertia(fn (Assert $page) => $page->has('quotes', 1)->where('quotes.0.id', $sent->id));
        $this->get("/client-area/quotes/{$draft->id}")->assertNotFound();
        $this->post("/client-area/quotes/{$draft->id}/accept")->assertNotFound();
        $this->post("/client-area/quotes/{$draft->id}/decline")->assertNotFound();
        $this->assertSame(QuoteStatus::Draft, $draft->fresh()->status);
        $this->assertSame(0, Order::query()->count());
    }

    public function test_repeated_acceptance_and_stale_conversion_reuse_the_original_order_and_notification(): void
    {
        $client = User::factory()->create();
        $quote = $this->sentQuote($client);
        $staleQuote = $quote->fresh();
        $this->actingAs($client)->post("/client-area/quotes/{$quote->id}/accept")->assertSessionHasNoErrors();
        $order = $quote->fresh()->order;
        $this->post("/client-area/quotes/{$quote->id}/accept")->assertRedirect('/client-area/invoice/'.$order->invoice->id);
        $reused = app(QuoteService::class)->convertToOrder($staleQuote);
        $this->assertSame($order->id, $reused->id);
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, $client->unreadNotifications()->count());
        $this->post("/client-area/quotes/{$quote->id}/decline")->assertSessionHasNoErrors();
        $this->assertSame(QuoteStatus::Converted, $quote->fresh()->status);
    }

    public function test_stale_quote_snapshot_cannot_convert_a_newly_declined_quote(): void
    {
        $client = User::factory()->create();
        $quote = $this->sentQuote($client);
        $staleQuote = $quote->fresh();
        $quote->update(['status' => QuoteStatus::Declined]);
        try {
            app(QuoteService::class)->convertToOrder($staleQuote);
            $this->fail('A stale sent snapshot must not bypass the saved decision.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quote', $exception->errors());
        }
        $this->assertSame(0, Order::query()->count());
    }

    private function sentQuote(User $client): Quote
    {
        $product = $this->product();

        $quote = Quote::query()->create([
            'quote_number' => 'Q-2026-'.fake()->unique()->numerify('#####'),
            'user_id' => $client->id,
            'status' => QuoteStatus::Sent,
            'currency' => 'USD',
            'subtotal' => 150,
            'tax_amount' => 0,
            'total' => 150,
            'valid_until' => now()->addDays(7),
            'sent_at' => now(),
        ]);
        $quote->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 150,
            'line_total' => 150,
            'billing_cycle' => 'one_time',
        ]);

        return $quote;
    }

    private function product(): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'modules'],
            ['name' => 'Modules', 'status' => true],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Quote Product '.fake()->unique()->numerify('###'),
            'slug' => 'quote-product-'.fake()->unique()->numerify('###'),
            'type' => 'whmcs_module',
            'price' => 100,
            'status' => true,
            'featured' => false,
        ]);
    }

    private function admin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Site Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'a-secure-password',
        ]);
    }
}
