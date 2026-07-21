<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\InvoiceService;

class GenerateInvoiceForOrder
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function handle(OrderPaid $event): void
    {
        $this->invoices->createForOrder($event->order);
    }
}
