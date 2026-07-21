<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\GatewayRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewGatewaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_gateways_are_discovered(): void
    {
        $registry = app(GatewayRegistry::class);

        $this->assertArrayHasKey('paddle', $registry->all());
        $this->assertArrayHasKey('fastspring', $registry->all());
    }

    public function test_paddle_charge_redirects_to_mock_checkout(): void
    {
        $order = $this->makePendingOrder('paddle');
        $registry = app(GatewayRegistry::class);
        $paddle = $registry->find('paddle');

        $result = $paddle->charge($order);

        $this->assertTrue($result->needsRedirect());
        $this->assertStringContainsString('gateways/mock-checkout/paddle', $result->redirectUrl);
    }

    public function test_fastspring_charge_redirects_to_mock_checkout(): void
    {
        $order = $this->makePendingOrder('fastspring');
        $registry = app(GatewayRegistry::class);
        $fastspring = $registry->find('fastspring');

        $result = $fastspring->charge($order);

        $this->assertTrue($result->needsRedirect());
        $this->assertStringContainsString('gateways/mock-checkout/fastspring', $result->redirectUrl);
    }

    public function test_paddle_return_marks_order_paid(): void
    {
        $order = $this->makePendingOrder('paddle');

        $this->get(route('gateways.return', 'paddle') . '?order_id=' . $order->id)
            ->assertRedirect(route('account.index'));

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame('paddle', $order->fresh()->payment_method);
    }

    public function test_fastspring_return_marks_order_paid(): void
    {
        $order = $this->makePendingOrder('fastspring');

        $this->get(route('gateways.return', 'fastspring') . '?order_id=' . $order->id)
            ->assertRedirect(route('account.index'));

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame('fastspring', $order->fresh()->payment_method);
    }

    private function makePendingOrder(string $gateway): Order
    {
        $category = Category::query()->create(['name' => 'Modules', 'slug' => 'modules', 'status' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Automation Toolkit',
            'slug' => 'automation-toolkit',
            'type' => 'whmcs_module',
            'price' => 149,
            'status' => true,
            'featured' => false,
        ]);
        $user = User::factory()->create();

        return Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-20260721-MOCK-' . uniqid(),
            'currency' => 'USD',
            'amount' => 149,
            'setup_fee' => 0,
            'billing_cycle' => 'one_time',
            'status' => OrderStatus::Pending,
            'payment_method' => $gateway,
            'payment_reference' => 'mock_ref_' . uniqid(),
        ]);
    }
}
