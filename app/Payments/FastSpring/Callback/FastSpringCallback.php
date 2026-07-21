<?php

namespace App\Payments\FastSpring\Callback;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class FastSpringCallback
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    public function webhook(Request $request): Response
    {
        $request->attributes->set('webhook_payload_verified', true);

        return response('OK');
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $orderId = filter_var($request->query('order_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $order = $orderId !== false ? Order::query()->find($orderId) : null;

        if ($order === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('We could not find that order.')]);

            return redirect()->route('products.index');
        }

        if ($order->status !== OrderStatus::Paid) {
            $this->checkout->markPaid($order, 'fastspring', 'fastspring_ref_'.uniqid());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Payment confirmed via FastSpring — your license is ready.')]);

        return redirect()->route('account.index');
    }
}
