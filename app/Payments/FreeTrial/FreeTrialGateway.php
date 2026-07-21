<?php

namespace App\Payments\FreeTrial;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;

class FreeTrialGateway extends Gateway
{
    public function key(): string
    {
        return 'free_trial';
    }

    public function name(): string
    {
        return '7 Days Free Trial';
    }

    public function description(): string
    {
        return 'Get 7 days of free access to the product.';
    }

    public function ready(): bool
    {
        return true;
    }

    public function implemented(): bool
    {
        return true;
    }

    public function configFields(): array
    {
        return [];
    }

    public function charge(Order $order): PaymentResult
    {
        return PaymentResult::success('free_trial', 'free_trial_ref_' . uniqid());
    }
}
