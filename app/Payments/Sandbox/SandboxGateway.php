<?php

namespace App\Payments\Sandbox;

use App\Models\Order;
use App\Payments\Gateway;
use App\Payments\PaymentResult;
use App\Payments\RefundableGateway;
use App\Payments\RefundResult;
use Illuminate\Support\Str;

class SandboxGateway extends Gateway implements RefundableGateway
{
    public function key(): string
    {
        return 'sandbox';
    }

    public function name(): string
    {
        return 'Sandbox';
    }

    public function description(): string
    {
        return 'Approves every payment instantly without charging anyone. For development and testing only.';
    }

    public function ready(): bool
    {
        return true;
    }

    public function charge(Order $order): PaymentResult
    {
        return PaymentResult::success('sandbox', 'sandbox_'.Str::uuid()->toString());
    }

    public function refund(Order $order, float $amount, string $idempotencyKey): RefundResult
    {
        return RefundResult::succeeded('sandbox_refund_'.$idempotencyKey);
    }
}
