<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_export_their_personal_data(): void
    {
        $user = User::factory()->create(['email' => 'gdpr@example.com']);

        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Export Product',
            'slug' => 'export-product',
            'type' => 'whmcs_module',
            'price' => 10,
            'status' => true,
            'featured' => false,
        ]);
        $user->orders()->create([
            'product_id' => $product->id,
            'order_number' => 'ORD-GDPR-1',
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

        $response = $this->actingAs($user)->get('/client-area/data-export');

        $response->assertOk();
        $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));

        $data = json_decode($response->streamedContent(), true);
        $this->assertSame('gdpr@example.com', $data['profile']['email']);
        $this->assertSame('ORD-GDPR-1', $data['orders'][0]['order_number']);
        $this->assertArrayHasKey('licenses', $data);
        $this->assertArrayHasKey('tickets', $data);
        $this->assertArrayHasKey('notifications', $data);
    }

    public function test_guests_cannot_export(): void
    {
        $this->get('/client-area/data-export')->assertRedirect('/login');
    }
}
