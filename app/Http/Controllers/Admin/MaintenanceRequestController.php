<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\MaintenanceRequest;
use App\Models\Quote;
use App\Models\Subscription;
use App\Services\MaintenancePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceRequestController extends Controller
{
    public function __construct(private readonly MaintenancePlanService $plans) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Maintenance/Requests', [
            'requests' => MaintenanceRequest::query()->with(['subscription', 'quote', 'user'])->latest()->paginate(25)->through(fn ($item) => $this->plans->requestPayload($item, true)),
        ]);
    }

    public function show(MaintenanceRequest $maintenanceRequest): Response
    {
        return Inertia::render('Admin/Maintenance/Request', [
            'request' => $this->plans->requestPayload($maintenanceRequest, true),
            'subscriptions' => Subscription::query()->where('user_id', $maintenanceRequest->user_id)->where('product_price_id', data_get($maintenanceRequest->plan_snapshot, 'billing.price_id'))->latest()->get()->map(fn ($subscription) => ['id' => $subscription->id, 'label' => '#'.$subscription->id.' — '.$subscription->status->value.' / '.$subscription->currency.' '.$subscription->amount]),
            'quotes' => data_get($maintenanceRequest->plan_snapshot, 'billing') === null ? Quote::query()->where('user_id', $maintenanceRequest->user_id)->latest()->get()->map(fn ($quote) => ['id' => $quote->id, 'label' => $quote->quote_number.' — '.$quote->status->value]) : [],
            'statuses' => MaintenanceRequest::STATUSES,
        ]);
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(MaintenanceRequest::STATUSES)],
            'client_update' => ['nullable', 'string', 'max:10000'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
        ]);
        DB::transaction(function () use ($request, $maintenanceRequest, $data): void {
            $entry = MaintenanceRequest::query()->lockForUpdate()->findOrFail($maintenanceRequest->id);
            $subscriptionId = $data['subscription_id'] ?? null;
            $quoteId = $data['quote_id'] ?? null;
            $billingChanged = (int) $subscriptionId !== (int) $entry->subscription_id || (int) $quoteId !== (int) $entry->quote_id;
            $admin = $request->user('admin');
            abort_if($billingChanged && (! $admin instanceof Admin || ! $admin->hasPermission('billing.manage')), 403);
            if (in_array($entry->status, ['withdrawn', 'completed', 'declined'], true) && $data['status'] !== $entry->status) {
                throw ValidationException::withMessages(['status' => 'This request is closed. A new request is needed to agree a new scope.']);
            }
            $subscription = $subscriptionId ? Subscription::query()->lockForUpdate()->whereKey($subscriptionId)->firstOrFail() : null;
            $quote = $quoteId ? Quote::query()->lockForUpdate()->with(['order' => fn ($query) => $query->lockForUpdate()])->whereKey($quoteId)->firstOrFail() : null;
            $this->plans->validateBilling($entry, $subscription, $quote, $data['status'] === 'active');
            $entry->update([...$data, 'subscription_id' => $subscriptionId, 'quote_id' => $quoteId]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Maintenance request updated.']);

        return back();
    }
}
