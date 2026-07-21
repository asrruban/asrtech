<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\Order;

class InvoiceService
{
    /**
     * Create an invoice for the order, or return the existing one.
     */
    public function createForOrder(Order $order): Invoice
    {
        $existing = $order->invoice;

        if ($existing !== null) {
            return $existing;
        }

        $dueDays = (int) config('asrtech.invoice.due_days', 14);

        return Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'status' => $order->status === OrderStatus::Paid ? InvoiceStatus::Paid : InvoiceStatus::Issued,
            'issued_at' => now(),
            'due_at' => $order->status === OrderStatus::Paid ? null : now()->addDays($dueDays),
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = (string) config('asrtech.invoice.number_prefix', 'INV');
        $year = now()->format('Y');
        $sequence = Invoice::query()->where('invoice_number', 'like', "{$prefix}-{$year}-%")->count() + 1;

        do {
            $number = sprintf('%s-%s-%05d', $prefix, $year, $sequence);
            $sequence++;
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
