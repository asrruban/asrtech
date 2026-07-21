<?php

namespace App\Payments;

use App\Models\Order;

interface PaymentGateway
{
    /**
     * Charge the customer for the given pending order.
     */
    public function charge(Order $order): PaymentResult;
}
