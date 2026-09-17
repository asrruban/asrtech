<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function generateQuoteNumber(): string
    {
        $year = now()->format('Y');
        $sequence = Quote::query()->where('quote_number', 'like', "Q-{$year}-%")->count() + 1;

        do {
            $number = sprintf('Q-%s-%05d', $year, $sequence);
            $sequence++;
        } while (Quote::query()->where('quote_number', $number)->exists());

        return $number;
    }

    /**
     * Convert an accepted quote into a payable order + issued invoice.
     */
    public function convertToOrder(Quote $quote): Order
    {
        return DB::transaction(function () use ($quote): Order {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            if ($quote->status === QuoteStatus::Converted && $quote->order_id !== null) {
                return $quote->order()->where('user_id', $quote->user_id)->firstOrFail();
            }
            if (! $quote->isActionable() && $quote->status !== QuoteStatus::Accepted) {
                throw ValidationException::withMessages([
                    'quote' => __('This quote can no longer be accepted.'),
                ]);
            }
            $quote->load(['items', 'user']);
            if ($quote->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'quote' => __('This quote has no line items.'),
                ]);
            }

            $firstItem = $quote->items->first();

            $order = Order::query()->create([
                'user_id' => $quote->user_id,
                'product_id' => $firstItem->product_id,
                'order_number' => $this->generateOrderNumber(),
                'currency' => $quote->currency,
                'subtotal' => $quote->subtotal,
                'amount' => $quote->subtotal,
                'discount_amount' => 0,
                'setup_fee' => 0,
                'tax_amount' => $quote->tax_amount,
                'billing_cycle' => $firstItem->billing_cycle,
                'status' => OrderStatus::Pending,
            ]);

            foreach ($quote->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->quantity > 1
                        ? "{$item->product_name} × {$item->quantity}"
                        : $item->product_name,
                    'currency' => $quote->currency,
                    'amount' => $item->line_total,
                    'setup_fee' => 0,
                    'billing_cycle' => $item->billing_cycle,
                ]);
            }

            $this->invoices->createForOrder($order);

            $quote->update([
                'status' => QuoteStatus::Converted,
                'order_id' => $order->id,
                'responded_at' => $quote->responded_at ?? now(),
            ]);

            return $order;
        });
    }

    /**
     * @param  list<array{product_id: int, product_name: string, quantity: int, unit_price: float, billing_cycle: string}>  $items
     */
    public function syncItems(Quote $quote, array $items, float $taxRatePercent = 0.0): void
    {
        $quote->items()->delete();

        foreach ($items as $item) {
            $lineTotal = round($item['quantity'] * $item['unit_price'], 2);
            $quote->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => number_format($item['unit_price'], 2, '.', ''),
                'line_total' => number_format($lineTotal, 2, '.', ''),
                'billing_cycle' => $item['billing_cycle'],
            ]);
        }

        $quote->load('items');
        $quote->recalculate($taxRatePercent);
        $quote->save();
    }

    private function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        $sequence = Order::query()->where('order_number', 'like', "ORD-{$year}-%")->count() + 1;

        do {
            $number = sprintf('ORD-%s-%05d', $year, $sequence);
            $sequence++;
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
