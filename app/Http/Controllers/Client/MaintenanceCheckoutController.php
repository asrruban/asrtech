<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\Order;
use App\Models\User;
use App\Payments\Gateway;
use App\Payments\GatewayRegistry;
use App\Services\CheckoutService;
use App\Services\CommercePricingService;
use App\Services\MaintenancePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MaintenanceCheckoutController extends Controller
{
    public function __construct(
        private readonly MaintenancePlanService $plans,
        private readonly CheckoutService $checkout,
        private readonly CommercePricingService $pricing,
        private readonly GatewayRegistry $gateways,
    ) {}

    public function start(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $this->authorizeOwner($request, $maintenanceRequest);
        $this->plans->checkoutPrice($maintenanceRequest);

        return redirect()->route('account.maintenance.checkout.review', $maintenanceRequest);
    }

    public function review(Request $request, MaintenanceRequest $maintenanceRequest): Response|RedirectResponse
    {
        $user = $this->authorizeOwner($request, $maintenanceRequest);
        try {
            $price = $this->plans->checkoutPrice($maintenanceRequest);
        } catch (ValidationException $exception) {
            return redirect()->route('account.maintenance.show', $maintenanceRequest)->withErrors($exception->errors());
        }
        $quote = $this->pricing->quote(collect([$price]), $user);
        $money = fn (float $amount): string => number_format($amount, 2, '.', '');

        return Inertia::render('Client/Checkout/Create', [
            'checkoutUrl' => route('account.maintenance.checkout.pay', $maintenanceRequest),
            'backUrl' => route('account.maintenance.show', $maintenanceRequest),
            'backLabel' => 'Maintenance request',
            'paymentGateways' => collect($this->gateways->enabled())->map(fn (Gateway $gateway): array => [
                'key' => $gateway->key(), 'name' => $gateway->displayName(), 'description' => $gateway->description(),
            ])->values()->all(),
            'cart' => [
                'items' => [[
                    'id' => $price->id, 'billing_cycle' => $price->billing_cycle->value,
                    'name' => $price->name, 'currency' => $price->currency,
                    'amount' => $price->sale_price ?? $price->price, 'setup_fee' => $price->setup_fee,
                    'product' => ['name' => data_get($maintenanceRequest->plan_snapshot, 'name'), 'slug' => $price->product->slug, 'url' => route('account.maintenance.show', $maintenanceRequest)],
                ]],
                'currency' => $price->currency, 'subtotal' => $money($quote->subtotal),
                'setup_fee' => $money($quote->setupFee), 'discount_amount' => $money($quote->discountAmount),
                'tax_amount' => $money($quote->taxAmount), 'total' => $money($quote->total),
                'tax' => $quote->taxRate ? ['name' => $quote->taxRate->name, 'rate' => $quote->taxRate->rate] : null,
                'tax_pending' => $quote->taxPending,
            ],
        ]);
    }

    public function pay(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $user = $this->authorizeOwner($request, $maintenanceRequest);
        $data = $request->validate(['gateway' => ['required', 'string', Rule::in($this->gateways->enabledKeys())]]);
        try {
            $price = $this->plans->checkoutPrice($maintenanceRequest);
            $outcome = $this->checkout->purchase($user, $price->product, $price, $data['gateway'], function (Order $order) use ($maintenanceRequest, $user): void {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $entry = MaintenanceRequest::query()->lockForUpdate()->findOrFail($maintenanceRequest->id);
                $currentPrice = $this->plans->checkoutPrice($entry, true);
                if ($order->product_price_id !== $currentPrice->id
                    || (float) $order->amount !== (float) ($currentPrice->sale_price ?? $currentPrice->price)
                    || (float) $order->setup_fee !== (float) $currentPrice->setup_fee
                    || $order->currency !== $currentPrice->currency) {
                    throw ValidationException::withMessages(['checkout' => 'Billing changed during checkout. Review your maintenance request before paying.']);
                }
                $entry->update(['checkout_order_id' => $order->id, 'checkout_redirect_url' => null]);
            });
        } catch (ValidationException $exception) {
            return redirect()->route('account.maintenance.show', $maintenanceRequest)->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('account.maintenance.show', $maintenanceRequest)->withErrors(['checkout' => 'Payment could not be confirmed. Check the order shown on this request and contact support before trying another payment.']);
        }

        if ($outcome->result->needsRedirect()) {
            $maintenanceRequest->update(['checkout_redirect_url' => $outcome->result->redirectUrl]);

            return Inertia::location((string) $outcome->result->redirectUrl);
        }
        if ($outcome->order->status !== OrderStatus::Paid) {
            return redirect()->route('account.maintenance.show', $maintenanceRequest)->withErrors(['checkout' => 'Payment was unsuccessful. Review this request before trying again.']);
        }
        $subscription = $outcome->order->initialSubscriptions()->where('product_price_id', $price->id)->first();
        if ($subscription !== null) {
            $maintenanceRequest->update(['subscription_id' => $subscription->id]);
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment received. Your subscription is linked and ASR Tech will confirm service activation.']);

        return redirect()->route('account.maintenance.show', $maintenanceRequest);
    }

    private function authorizeOwner(Request $request, MaintenanceRequest $entry): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && (int) $entry->user_id === (int) $user->id, 404);

        return $user;
    }
}
