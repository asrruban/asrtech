<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartPromotionController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $this->cart->applyPromotion($data['code']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Promotion code applied.')]);

        return redirect()->route('cart.index');
    }

    public function destroy(): RedirectResponse
    {
        $this->cart->removePromotion();

        return redirect()->route('cart.index');
    }
}
