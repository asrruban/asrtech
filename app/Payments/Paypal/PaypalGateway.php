<?php

namespace App\Payments\Paypal;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;

class PaypalGateway extends Gateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function name(): string
    {
        return 'PayPal';
    }

    public function description(): string
    {
        return 'PayPal wallet and card payments through the PayPal REST API.';
    }

    public function implemented(): bool
    {
        return false;
    }

    public function configFields(): array
    {
        return [
            ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text'],
            ['name' => 'client_secret', 'label' => 'Client secret', 'type' => 'password'],
            ['name' => 'mode', 'label' => 'Mode', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox', 'live' => 'Live'], 'required' => false],
        ];
    }

    public function charge(Order $order): PaymentResult
    {
        return PaymentResult::failure('paypal', 'PayPal charging is not implemented yet.');
    }
}
