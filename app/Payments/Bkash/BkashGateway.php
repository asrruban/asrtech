<?php

namespace App\Payments\Bkash;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;

class BkashGateway extends Gateway
{
    public function key(): string
    {
        return 'bkash';
    }

    public function name(): string
    {
        return 'bKash';
    }

    public function description(): string
    {
        return 'Mobile payments for Bangladesh through the bKash merchant API.';
    }

    public function implemented(): bool
    {
        return false;
    }

    public function configFields(): array
    {
        return [
            ['name' => 'app_key', 'label' => 'App key', 'type' => 'text'],
            ['name' => 'app_secret', 'label' => 'App secret', 'type' => 'password'],
            ['name' => 'username', 'label' => 'Username', 'type' => 'text'],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password'],
            ['name' => 'test_mode', 'label' => 'Sandbox mode', 'type' => 'yesno', 'required' => false],
        ];
    }

    public function charge(Order $order): PaymentResult
    {
        return PaymentResult::failure('bkash', 'bKash charging is not implemented yet.');
    }
}
