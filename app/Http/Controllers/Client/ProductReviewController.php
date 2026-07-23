<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Enums\ProductReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\SaveProductReviewRequest;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ProductReviewController extends Controller
{
    public function __invoke(
        SaveProductReviewRequest $request,
        ProductType $productType,
        Product $product,
    ): RedirectResponse {
        abort_unless($product->status && $product->type === $productType->key, 404);

        /** @var User $user */
        $user = $request->user();

        abort_unless(
            $user->orders()
                ->where('product_id', $product->id)
                ->where('status', OrderStatus::Paid)
                ->exists(),
            403,
            'Only verified customers who purchased this product can review it.',
        );

        $product->customerReviews()->updateOrCreate(
            ['user_id' => $user->id],
            [
                ...$request->validated(),
                'status' => ProductReviewStatus::Pending,
                'moderation_note' => null,
                'moderated_by' => null,
                'moderated_at' => null,
            ],
        );

        return redirect($product->storefrontUrl().'#reviews');
    }
}
