<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverWebhookDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $deliveryId) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::query()->with('endpoint')->find($this->deliveryId);

        if ($delivery === null || $delivery->status === 'success') {
            return;
        }

        $endpoint = $delivery->endpoint;

        if ($endpoint === null || ! $endpoint->enabled) {
            $delivery->update(['status' => 'failed', 'error' => 'Endpoint disabled or removed.']);

            return;
        }

        $body = json_encode([
            'id' => $delivery->id,
            'event' => $delivery->event,
            'created_at' => $delivery->created_at?->toIso8601String(),
            'data' => $delivery->payload,
        ], JSON_THROW_ON_ERROR);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-ASRTech-Event' => $delivery->event,
                    'X-ASRTech-Signature' => 'sha256='.hash_hmac('sha256', $body, $endpoint->secret),
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $delivery->update([
                'status' => $response->successful() ? 'success' : 'failed',
                'response_code' => $response->status(),
                'attempts' => $delivery->attempts + 1,
                'error' => $response->successful() ? null : mb_substr($response->body(), 0, 500),
                'delivered_at' => $response->successful() ? now() : null,
            ]);

            if (! $response->successful()) {
                $this->release($this->backoff[min($delivery->attempts, count($this->backoff) - 1)]);
            }
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'attempts' => $delivery->attempts + 1,
                'error' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            throw $exception; // Let the queue retry.
        }
    }
}
