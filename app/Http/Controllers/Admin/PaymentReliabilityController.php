<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatewayWebhookEvent;
use App\Models\Transaction;
use App\Payments\GatewayRegistry;
use App\Services\GatewayWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class PaymentReliabilityController extends Controller
{
    public function __construct(
        private readonly GatewayRegistry $gateways,
        private readonly GatewayWebhookService $webhooks,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(GatewayWebhookEvent::statuses())],
            'gateway' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $status = trim((string) ($filters['status'] ?? ''));
        $gateway = trim((string) ($filters['gateway'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $since = now()->subDay();
        $eventsLastDay = GatewayWebhookEvent::query()->where('created_at', '>=', $since);
        $eventCount = (clone $eventsLastDay)->count();
        $processedCount = (clone $eventsLastDay)
            ->where('status', GatewayWebhookEvent::STATUS_PROCESSED)
            ->count();

        return Inertia::render('Admin/Payments/Index', [
            'filters' => compact('status', 'gateway', 'search'),
            'statusOptions' => GatewayWebhookEvent::statuses(),
            'gatewayOptions' => collect($this->gateways->all())
                ->map(fn ($module): array => [
                    'value' => $module->key(),
                    'label' => $module->displayName(),
                ])
                ->values(),
            'stats' => [
                'events_24h' => $eventCount,
                'processed_24h' => $processedCount,
                'failed_24h' => (clone $eventsLastDay)
                    ->where('status', GatewayWebhookEvent::STATUS_FAILED)
                    ->count(),
                'duplicates_24h' => (int) (clone $eventsLastDay)->sum('duplicate_count'),
                'success_rate' => $eventCount > 0
                    ? round(($processedCount / $eventCount) * 100, 1)
                    : 100,
                'payments_30d' => (float) Transaction::query()
                    ->where('type', 'payment')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->sum('amount'),
                'refunds_30d' => (float) Transaction::query()
                    ->where('type', 'refund')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->sum('amount'),
                'currency' => (string) config('asrtech.currency', 'USD'),
            ],
            'events' => GatewayWebhookEvent::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($gateway, fn ($query) => $query->where('gateway', $gateway))
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('external_id', 'like', "%{$search}%")
                        ->orWhere('event_type', 'like', "%{$search}%");
                }))
                ->latest('id')
                ->paginate((int) config('asrtech.records_per_page', 15))
                ->withQueryString()
                ->through(fn (GatewayWebhookEvent $event): array => $this->summary($event)),
            'transactions' => Transaction::query()
                ->with(['order:id,user_id,product_id,order_number,currency', 'order.user:id,name,email', 'order.product:id,name'])
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (Transaction $transaction): array => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'gateway' => $transaction->gateway,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'fees' => $transaction->fees,
                    'created_at' => $transaction->created_at,
                    'order' => [
                        'id' => $transaction->order->id,
                        'order_number' => $transaction->order->order_number,
                        'currency' => $transaction->order->currency,
                        'user' => $transaction->order->user->only(['id', 'name', 'email']),
                        'product' => $transaction->order->product->only(['id', 'name']),
                    ],
                ]),
        ]);
    }

    public function show(GatewayWebhookEvent $webhookEvent): Response
    {
        return Inertia::render('Admin/Payments/Show', [
            'event' => [
                ...$this->summary($webhookEvent),
                'payload_hash' => $webhookEvent->payload_hash,
                'headers' => $this->webhooks->sanitized($webhookEvent->headers),
                'payload' => $this->webhooks->sanitized($webhookEvent->payload),
                'last_error' => $webhookEvent->last_error,
                'processing_started_at' => $webhookEvent->processing_started_at,
                'verified_at' => $webhookEvent->verified_at,
                'processed_at' => $webhookEvent->processed_at,
                'last_received_at' => $webhookEvent->last_received_at,
            ],
        ]);
    }

    public function replay(GatewayWebhookEvent $webhookEvent): RedirectResponse
    {
        $handler = $this->gateways->callback($webhookEvent->gateway);

        if (! $webhookEvent->canReplay() || $handler === null || ! method_exists($handler, 'replay')) {
            throw ValidationException::withMessages([
                'event' => __('This event cannot be replayed because its payload was not verified or the gateway does not support replay.'),
            ]);
        }

        try {
            $response = $this->webhooks->replay(
                $webhookEvent,
                fn (): SymfonyResponse => $handler->replay($webhookEvent->payload ?? []),
            );
            $successful = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;

            Inertia::flash('toast', [
                'type' => $successful ? 'success' : 'error',
                'message' => $successful
                    ? __('Webhook event replayed successfully.')
                    : __('The gateway rejected the replay with HTTP :status.', ['status' => $response->getStatusCode()]),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Replay failed: :message', ['message' => $exception->getMessage()]),
            ]);
        }

        return redirect()->route('admin.payments.webhooks.show', $webhookEvent);
    }

    /** @return array<string, mixed> */
    private function summary(GatewayWebhookEvent $event): array
    {
        return [
            'id' => $event->id,
            'gateway' => $event->gateway,
            'external_id' => $event->external_id,
            'event_type' => $event->event_type,
            'status' => $event->status,
            'attempts' => $event->attempts,
            'duplicate_count' => $event->duplicate_count,
            'response_code' => $event->response_code,
            'can_replay' => $event->canReplay(),
            'created_at' => $event->created_at,
            'updated_at' => $event->updated_at,
        ];
    }
}
