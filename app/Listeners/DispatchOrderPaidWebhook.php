<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\WebhookDispatcher;

class DispatchOrderPaidWebhook
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    public function handle(OrderPaid $event): void
    {
        $order = $event->order->loadMissing(['user:id,name,email', 'items']);

        $this->webhooks->dispatch('order.paid', [
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'total' => $order->totalAmount(),
            'currency' => $order->currency,
            'payment_method' => $order->payment_method,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'customer' => [
                'name' => $order->user?->name,
                'email' => $order->user?->email,
            ],
            'items' => $order->items->map(fn ($item): array => [
                'product' => $item->product_name,
                'amount' => (float) $item->amount,
                'billing_cycle' => $item->billing_cycle->value,
            ])->all(),
        ]);
    }
}
