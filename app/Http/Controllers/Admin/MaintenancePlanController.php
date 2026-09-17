<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\MaintenancePlan;
use App\Models\ProductPrice;
use App\Services\MaintenancePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaintenancePlanController extends Controller
{
    public function __construct(private readonly MaintenancePlanService $plans) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Maintenance/Plans', [
            'plans' => MaintenancePlan::query()->with('productPrice.product')->latest()->get()->map(fn ($plan) => $this->plans->planPayload($plan)),
            'prices' => ProductPrice::query()->with('product')->where('enabled', true)->whereIn('billing_cycle', [BillingCycle::Monthly->value, BillingCycle::Yearly->value])->whereHas('product', fn ($query) => $query->where('status', true))->get()->filter(fn ($price) => $this->plans->availablePrice($price))->map(fn ($price) => [
                'id' => $price->id, 'label' => $price->product->name.' / '.$price->name.' — '.$price->currency.' '.($price->sale_price ?? $price->price).' '.$price->billing_cycle->value,
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MaintenancePlan::query()->create($this->validated($request));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance plan created.']);

        return back();
    }

    public function update(Request $request, MaintenancePlan $plan): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance plan updated. Existing requests keep their agreed scope.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?MaintenancePlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('maintenance_plans', 'slug')->ignore($plan)],
            'platform' => ['required', Rule::in(['wordpress', 'whmcs', 'server'])],
            'summary' => ['required', 'string', 'max:1000'],
            'scope' => ['required', 'string', 'min:20', 'max:10000'],
            'exclusions' => ['nullable', 'string', 'max:10000'],
            'support_arrangements' => ['nullable', 'string', 'max:5000'],
            'product_price_id' => ['nullable', 'integer', 'exists:product_prices,id'],
            'published' => ['required', 'boolean'],
        ]);
        if (! empty($data['product_price_id']) && ! $this->plans->availablePrice(ProductPrice::query()->whereKey($data['product_price_id'])->first())) {
            throw ValidationException::withMessages(['product_price_id' => 'Choose an enabled monthly or yearly price from an available product using the existing checkout.']);
        }

        return $data;
    }
}
