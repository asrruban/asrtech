<?php

namespace App\Payments;

use App\Models\Order;

final readonly class PurchaseOutcome
{
    public function __construct(
        public Order $order,
        public PaymentResult $result,
    ) {}
}
