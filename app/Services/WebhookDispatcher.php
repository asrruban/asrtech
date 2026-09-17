<?php

namespace App\Services;

use App\Jobs\DeliverWebhookDelivery;
use App\Models\WebhookEndpoint;

/**
 * Fan out domain events to subscribed outbound webhook endpoints.
 */
class WebhookDispatcher
{
    /** @param array<string, mixed> $payload */
    public function dispatch(string $event, array $payload): int
    {
        $endpoints = WebhookEndpoint::query()
            ->where('enabled', true)
            ->get()
            ->filter(fn (WebhookEndpoint $endpoint): bool => $endpoint->subscribesTo($event));

        foreach ($endpoints as $endpoint) {
            $delivery = $endpoint->deliveries()->create([
                'event' => $event,
                'payload' => $payload,
                'status' => 'pending',
            ]);

            DeliverWebhookDelivery::dispatch($delivery->id);
        }

        return $endpoints->count();
    }
}
