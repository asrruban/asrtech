<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WebhookEndpointController extends Controller
{
    public function index(): Response
    {
        $deliveryCounts = WebhookDelivery::query()
            ->selectRaw('webhook_endpoint_id, status, COUNT(*) as total')
            ->groupBy('webhook_endpoint_id', 'status')
            ->toBase()
            ->get()
            ->groupBy('webhook_endpoint_id');

        return Inertia::render('Admin/Configuration/Webhooks', [
            'endpoints' => WebhookEndpoint::query()
                ->orderByDesc('created_at')
                ->get()
                ->map(function (WebhookEndpoint $endpoint) use ($deliveryCounts): array {
                    $rows = collect($deliveryCounts->get($endpoint->id, []));
                    $countFor = fn (string $status): int => (int) $rows
                        ->where('status', $status)
                        ->sum('total');

                    return [
                        'id' => $endpoint->id,
                        'name' => $endpoint->name,
                        'url' => $endpoint->url,
                        'secret' => $endpoint->secret,
                        'events' => $endpoint->events,
                        'enabled' => $endpoint->enabled,
                        'successful_deliveries' => $countFor('success'),
                        'failed_deliveries' => $countFor('failed'),
                        'created_at' => $endpoint->created_at?->toIso8601String(),
                    ];
                }),
            'availableEvents' => WebhookEndpoint::AVAILABLE_EVENTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(WebhookEndpoint::AVAILABLE_EVENTS)],
        ]);
        /** @var Admin $admin */
        $admin = $request->user('admin');

        WebhookEndpoint::query()->create([
            'admin_id' => $admin->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => WebhookEndpoint::generateSecret(),
            'events' => array_values($data['events']),
            'enabled' => true,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Webhook endpoint created.')]);

        return redirect()->route('admin.webhooks.index');
    }

    public function update(Request $request, WebhookEndpoint $webhook): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $webhook->update(['enabled' => $data['enabled']]);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Webhook endpoint updated.')]);

        return redirect()->route('admin.webhooks.index');
    }

    public function destroy(WebhookEndpoint $webhook): RedirectResponse
    {
        $webhook->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Webhook endpoint deleted.')]);

        return redirect()->route('admin.webhooks.index');
    }
}
