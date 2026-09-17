<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\MaintenancePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaintenancePlanController extends Controller
{
    public function __construct(private readonly MaintenancePlanService $plans) {}

    public function index(): Response
    {
        return Inertia::render('Client/MaintenancePlans/Index', [
            'plans' => MaintenancePlan::query()->where('published', true)->with('productPrice.product')->orderBy('name')->get()->map(fn ($plan) => $this->plans->planPayload($plan)),
        ]);
    }

    public function show(MaintenancePlan $plan): Response
    {
        abort_unless($plan->published, 404);

        return Inertia::render('Client/MaintenancePlans/Show', ['plan' => $this->plans->planPayload($plan)]);
    }

    public function requests(Request $request): Response
    {
        return Inertia::render('Client/Account/Maintenance/Index', [
            'requests' => MaintenanceRequest::query()->where('user_id', $request->user()->id)->with(['subscription', 'quote', 'user'])->latest()->get()->map(fn ($item) => $this->plans->requestPayload($item)),
        ]);
    }

    public function details(Request $request, MaintenanceRequest $maintenanceRequest): Response
    {
        abort_unless((int) $maintenanceRequest->user_id === (int) $request->user()->id, 404);

        return Inertia::render('Client/Account/Maintenance/Show', ['request' => $this->plans->requestPayload($maintenanceRequest)]);
    }

    public function store(Request $request, MaintenancePlan $plan): RedirectResponse
    {
        abort_unless($plan->published, 404);
        $data = $request->validate([
            'website' => ['nullable', 'url:http,https', 'max:2048'],
            'requirements' => ['required', 'string', 'min:20', 'max:10000'],
            'acknowledge_scope' => ['accepted'],
            'version' => ['required', 'string'],
        ]);
        $entry = DB::transaction(function () use ($request, $plan, $data): MaintenanceRequest {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $plan = MaintenancePlan::query()->lockForUpdate()->findOrFail($plan->id);
            $payload = $this->plans->planPayload($plan);
            if (! $payload['available'] || ! hash_equals($payload['version'], $data['version'])) {
                throw ValidationException::withMessages(['version' => 'This plan changed or is unavailable. Reload this page to review the latest details.']);
            }
            if (MaintenanceRequest::query()->where('user_id', $request->user()->id)->where('maintenance_plan_id', $plan->id)->whereIn('status', ['requested', 'reviewing', 'awaiting_payment', 'active', 'paused'])->exists()) {
                throw ValidationException::withMessages(['requirements' => 'You already have an open request for this plan. Continue from Maintenance in your Client Area.']);
            }

            return MaintenanceRequest::query()->create([
                'maintenance_plan_id' => $plan->id, 'user_id' => $request->user()->id,
                'plan_snapshot' => $payload, 'website' => $data['website'] ?? null,
                'requirements' => $data['requirements'], 'status' => 'requested',
                'scope_acknowledged_at' => now(),
            ]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Your maintenance request has been received.']);

        return redirect()->route('account.maintenance.show', $entry);
    }

    public function withdraw(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        abort_unless((int) $maintenanceRequest->user_id === (int) $request->user()->id, 404);
        DB::transaction(function () use ($maintenanceRequest): void {
            $entry = MaintenanceRequest::query()->lockForUpdate()->findOrFail($maintenanceRequest->id);
            if (! in_array($entry->status, ['requested', 'reviewing'], true)) {
                throw ValidationException::withMessages(['status' => 'This request has progressed. Please contact support to discuss changes.']);
            }
            $entry->update(['status' => 'withdrawn']);
        });

        return back();
    }
}
