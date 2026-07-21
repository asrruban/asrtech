<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserOrderRequest;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserOrderController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    public function store(SaveUserOrderRequest $request, User $user): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $priceId = $request->input('product_price_id');
        $price = $priceId === null ? null : ProductPrice::query()->findOrFail((int) $priceId);

        $order = $this->checkout->manual(
            user: $user,
            product: $product,
            price: $price,
            markPaid: $request->boolean('mark_paid'),
            complimentary: $request->boolean('complimentary'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->boolean('complimentary')
                ? __(':product assigned to :name — license created.', ['product' => $product->name, 'name' => $user->name])
                : __('Order :number created for :name.', ['number' => $order->order_number, 'name' => $user->name]),
        ]);

        return redirect()->route('admin.users.show', $user);
    }
}
