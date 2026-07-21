<?php

namespace App\Payments;

use App\Models\Order;

interface RefundableGateway
{
    public function refund(Order $order, float $amount, string $idempotencyKey): RefundResult;
}
