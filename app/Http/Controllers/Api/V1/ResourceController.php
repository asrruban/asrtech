<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public REST API v1 (read-only) for partners and integrations.
 * Authenticated with Bearer API tokens issued in the admin panel.
 */
class ResourceController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('status', true)
            ->with('prices')
            ->paginate(min(50, max(1, (int) $request->integer('per_page', 15))));

        return response()->json($products);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['user:id,name,email', 'items'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('email'), fn ($query, $email) => $query
                ->whereHas('user', fn ($q) => $q->where('email', $email)))
            ->orderByDesc('created_at')
            ->paginate(min(50, max(1, (int) $request->integer('per_page', 15))));

        return response()->json($orders);
    }

    public function order(string $orderNumber): JsonResponse
    {
        $order = Order::query()
            ->with(['user:id,name,email', 'items', 'transactions'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }

    public function license(string $key): JsonResponse
    {
        $license = License::query()
            ->with(['product:id,name,slug', 'user:id,name,email'])
            ->where('license_key', $key)
            ->firstOrFail();

        return response()->json(['data' => $license]);
    }

    public function invoice(string $number): JsonResponse
    {
        $invoice = Invoice::query()
            ->with(['order.items', 'order.user:id,name,email'])
            ->where('invoice_number', $number)
            ->firstOrFail();

        return response()->json(['data' => $invoice]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = Subscription::query()
            ->with(['user:id,name,email', 'product:id,name'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('email'), fn ($query, $email) => $query
                ->whereHas('user', fn ($q) => $q->where('email', $email)))
            ->orderByDesc('created_at')
            ->paginate(min(50, max(1, (int) $request->integer('per_page', 15))));

        return response()->json($subscriptions);
    }
}
