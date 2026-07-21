<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PromotionCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PromotionCodeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Commerce/Promotions/Index', [
            'promotions' => PromotionCode::query()
                ->with('products:id,name')
                ->withCount(['redemptions as redemption_count' => fn ($query) => $query->where('status', 'redeemed')])
                ->latest('id')
                ->get(),
            'products' => Product::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'discountTypes' => PromotionDiscountType::values(),
            'scopes' => PromotionScope::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePromotion($request);
        $productIds = $data['product_ids'];
        unset($data['product_ids']);

        $promotion = PromotionCode::query()->create($data);
        $promotion->products()->sync($productIds);

        return redirect()->route('admin.promotions.index');
    }

    public function update(Request $request, PromotionCode $promotion): RedirectResponse
    {
        $data = $this->validatePromotion($request, $promotion);
        $productIds = $data['product_ids'];
        unset($data['product_ids']);

        $promotion->update($data);
        $promotion->products()->sync($productIds);

        return redirect()->route('admin.promotions.index');
    }

    public function destroy(PromotionCode $promotion): RedirectResponse
    {
        if ($promotion->redemptions()->exists()) {
            throw ValidationException::withMessages([
                'promotion' => __('Used promotion codes must be deactivated instead of deleted.'),
            ]);
        }

        $promotion->delete();

        return redirect()->route('admin.promotions.index');
    }

    /** @return array<string, mixed> */
    private function validatePromotion(Request $request, ?PromotionCode $promotion = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'currency' => filled($request->input('currency'))
                ? strtoupper(trim((string) $request->input('currency')))
                : null,
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('promotion_codes')->ignore($promotion)],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::enum(PromotionDiscountType::class)],
            'value' => ['required', 'numeric', 'gt:0', $request->input('discount_type') === 'percentage' ? 'max:100' : 'max:9999999999'],
            'currency' => ['nullable', 'string', 'size:3', Rule::requiredIf($request->input('discount_type') === 'fixed')],
            'minimum_subtotal' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'scope' => ['required', Rule::enum(PromotionScope::class)],
            'active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', ...(filled($request->input('starts_at')) ? ['after:starts_at'] : [])],
            'product_ids' => ['present', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        return $data;
    }
}
