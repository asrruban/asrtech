<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Payments\Gateway;
use App\Payments\GatewayRegistry;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CartCheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutService $checkout,
        private readonly GatewayRegistry $gateways,
    ) {}

    public function create(): Response|RedirectResponse
    {
        if ($this->cart->count() === 0) {
            return redirect()->route('cart.index');
        }

        $user = auth()->user();
        $hasTrialable = false;
        if ($user instanceof User) {
            foreach ($this->cart->items() as $price) {
                $product = $price->product;
                if ($product->has_free_trial && ! $user->hasUsedFreeTrial($product)) {
                    $hasTrialable = true;
                    break;
                }
            }
        }

        $gateways = collect($this->gateways->enabled())
            ->map(fn (Gateway $gateway): array => [
                'key' => $gateway->key(),
                'name' => $gateway->displayName(),
                'description' => $gateway->description(),
            ]);

        if ($hasTrialable) {
            $gateways->push([
                'key' => 'free_trial',
                'name' => '7 Days Free Trial',
                'description' => 'Get 7 days of free access to the product.',
            ]);
        }

        return Inertia::render('Client/Checkout/Create', [
            'cart' => $this->cart->summary(),
            'paymentGateways' => $gateways->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $items = $this->cart->items();
        $allowedGateways = $this->gateways->enabledKeys();

        $hasTrialable = false;
        foreach ($items as $price) {
            $product = $price->product;
            if ($product->has_free_trial && ! $user->hasUsedFreeTrial($product)) {
                $hasTrialable = true;
                break;
            }
        }

        if ($hasTrialable) {
            $allowedGateways[] = 'free_trial';
        }

        $validated = $request->validate([
            'gateway' => ['nullable', 'string', Rule::in($allowedGateways)],
        ]);
        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        try {
            $outcome = $this->checkout->purchaseCart(
                $user,
                $items,
                $validated['gateway'] ?? null,
                $this->cart->promotionCode(),
            );
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('checkout.create');
        }
        $order = $outcome->order;

        if ($outcome->result->needsRedirect()) {
            $this->cart->clear();

            return Inertia::location((string) $outcome->result->redirectUrl);
        }

        if ($order->status !== OrderStatus::Paid) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Payment failed. Your cart is still available — please try again.'),
            ]);

            return redirect()->route('checkout.create');
        }

        $this->cart->clear();
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Order :number confirmed. Your licenses are ready.', ['number' => $order->order_number]),
        ]);

        return redirect()->route('account.index');
    }
}
