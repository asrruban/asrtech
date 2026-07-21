<?php

namespace App\Payments\Sslcommerz;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;

class SslcommerzGateway extends Gateway
{
    public function key(): string
    {
        return 'sslcommerz';
    }

    public function name(): string
    {
        return 'SSLCommerz';
    }

    public function description(): string
    {
        return 'Cards, mobile banking, and internet banking for Bangladesh.';
    }

    public function implemented(): bool
    {
        return false;
    }

    public function configFields(): array
    {
        return [
            ['name' => 'store_id', 'label' => 'Store ID', 'type' => 'text'],
            ['name' => 'store_password', 'label' => 'Store password', 'type' => 'password'],
            ['name' => 'test_mode', 'label' => 'Sandbox mode', 'type' => 'yesno', 'required' => false],
        ];
    }

    public function charge(Order $order): PaymentResult
    {
        return PaymentResult::failure('sslcommerz', 'SSLCommerz charging is not implemented yet.');
    }
}
