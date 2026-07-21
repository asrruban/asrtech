<?php

namespace App\Payments\Paddle;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;

class PaddleGateway extends Gateway
{
    public function key(): string
    {
        return 'paddle';
    }

    public function name(): string
    {
        return 'Paddle';
    }

    public function description(): string
    {
        return 'One-time and subscription payments through Paddle Billing checkout.';
    }

    public function ready(): bool
    {
        return $this->isConfigured();
    }

    public function webhookInstructions(): ?string
    {
        return 'Add the webhook URL in Paddle Dashboard -> Developer tools -> Webhooks, subscribe to transaction.completed and subscription.updated events, and paste the signing secret key below.';
    }

    public function configFields(): array
    {
        return [
            ['name' => 'vendor_id', 'label' => 'Vendor ID / Client ID', 'type' => 'text'],
            ['name' => 'api_key', 'label' => 'API Key', 'type' => 'password'],
            ['name' => 'webhook_secret', 'label' => 'Webhook Secret / Public Key', 'type' => 'password', 'required' => false],
            ['name' => 'sandbox_mode', 'label' => 'Sandbox mode', 'type' => 'yesno', 'required' => false],
        ];
    }

    public function charge(Order $order): PaymentResult
    {
        $url = route('gateways.mock-checkout', [
            'gateway' => 'paddle',
            'order_id' => $order->id,
        ]);

        return PaymentResult::redirect('paddle', $url, 'paddle_mock_' . uniqid());
    }
}
