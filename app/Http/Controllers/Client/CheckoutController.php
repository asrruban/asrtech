<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Payments\GatewayRegistry;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly GatewayRegistry $gateways,
        private readonly CartService $cart,
    ) {}

    public function __invoke(Request $request, Product $product, ProductPrice $price): RedirectResponse|Response
    {
        abort_unless($product->status && $price->enabled, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $allowedGateways = $this->gateways->enabledKeys();
        if ($product->has_free_trial && ! $user->hasUsedFreeTrial($product)) {
            $allowedGateways[] = 'free_trial';
        }

        $validated = $request->validate([
            'gateway' => ['nullable', 'string', Rule::in($allowedGateways)],
        ]);

        $outcome = $this->checkout->purchase($user, $product, $price, $validated['gateway'] ?? null);
        $order = $outcome->order;

        if ($outcome->result->needsRedirect()) {
            $this->cart->remove($price->id);

            return Inertia::location((string) $outcome->result->redirectUrl);
        }

        if ($order->status !== OrderStatus::Paid) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Payment failed. You have not been charged — please try again.'),
            ]);

            return redirect()->route('products.show', $product->storefrontRouteParameters());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Order :number confirmed. Your license key is ready.', ['number' => $order->order_number]),
        ]);

        $this->cart->remove($price->id);

        return redirect()->route('account.index');
    }
}
