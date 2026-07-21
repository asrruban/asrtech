<?php

namespace App\Payments\FastSpring;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;

class FastSpringGateway extends Gateway
{
    public function key(): string
    {
        return 'fastspring';
    }

    public function name(): string
    {
        return 'FastSpring';
    }

    public function description(): string
    {
        return 'Hosted storefront checkout for SaaS and software sales.';
    }

    public function ready(): bool
    {
        return $this->isConfigured();
    }

    public function webhookInstructions(): ?string
    {
        return 'Configure webhook in FastSpring Dashboard -> Webhooks. Subscribe to order.completed and subscription.activated, and paste the shared secret key below.';
    }

    public function configFields(): array
    {
        return [
            ['name' => 'storefront_id', 'label' => 'Storefront ID', 'type' => 'text'],
            ['name' => 'api_username', 'label' => 'API Username', 'type' => 'text'],
            ['name' => 'api_password', 'label' => 'API Password', 'type' => 'password'],
            ['name' => 'webhook_secret', 'label' => 'Shared Secret Key', 'type' => 'password', 'required' => false],
            ['name' => 'sandbox_mode', 'label' => 'Sandbox mode', 'type' => 'yesno', 'required' => false],
        ];
    }

    public function charge(Order $order): PaymentResult
    {
        $url = route('gateways.mock-checkout', [
            'gateway' => 'fastspring',
            'order_id' => $order->id,
        ]);

        return PaymentResult::redirect('fastspring', $url, 'fastspring_mock_' . uniqid());
    }
}
