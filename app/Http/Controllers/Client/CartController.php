<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): Response
    {
        return Inertia::render('Client/Cart/Index', [
            'cart' => $this->cart->summary(),
        ]);
    }

    public function store(Request $request, Product $product, ProductPrice $price): RedirectResponse
    {
        abort_unless($product->status && $price->enabled, 404);

        $validated = $request->validate([
            'stay_on_product' => ['sometimes', 'boolean'],
        ]);

        $this->cart->add($product, $price);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':product was added to your cart.', ['product' => $product->name]),
        ]);

        if ($validated['stay_on_product'] ?? false) {
            return redirect()->route('products.show', $product->storefrontRouteParameters());
        }

        return redirect()->route('cart.index');
    }

    public function destroy(int $price): RedirectResponse
    {
        $this->cart->remove($price);

        return redirect()->route('cart.index');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('cart.index');
    }
}
