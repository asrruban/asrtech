<?php

namespace App\Services;

use App\Models\GatewayWebhookEvent;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GatewayWebhookService
{
    /**
     * Persist a callback before dispatching it, reject concurrent duplicates,
     * and remember the final gateway response for operational visibility.
     *
     * @param  Closure(): Response  $process
     */
    public function receive(string $gateway, Request $request, Closure $process): Response
    {
        $payload = $this->payload($request);
        $payloadHash = hash('sha256', $request->getContent() ?: $this->encode($payload));
        $externalId = $this->externalId($payload, $payloadHash);

        try {
            $event = GatewayWebhookEvent::query()->firstOrCreate(
                ['gateway' => $gateway, 'external_id' => $externalId],
                [
                    'event_type' => $this->eventType($payload),
                    'status' => GatewayWebhookEvent::STATUS_PENDING,
                    'payload' => $payload,
                    'headers' => $this->headers($request),
                    'payload_hash' => $payloadHash,
                    'last_received_at' => now(),
                ],
            );
        } catch (UniqueConstraintViolationException) {
            // Another request inserted this provider event between the lookup
            // and insert. The unique index is the final concurrency guard.
            $event = GatewayWebhookEvent::query()
                ->where('gateway', $gateway)
                ->where('external_id', $externalId)
                ->firstOrFail();
        }

        if (! $event->wasRecentlyCreated) {
            $event->increment('duplicate_count');
            $event->update(['last_received_at' => now()]);

            if ($event->status === GatewayWebhookEvent::STATUS_PROCESSED) {
                return response('Already processed', 200)
                    ->header('X-Webhook-Duplicate', 'true');
            }

            // Gateways retry failed deliveries. Keep the newest signed headers
            // and payload so a valid redelivery can recover the event.
            $event->update([
                'event_type' => $this->eventType($payload),
                'payload' => $payload,
                'headers' => $this->headers($request),
                'payload_hash' => $payloadHash,
            ]);
        }

        if (! $this->claim($event)) {
            return response('Already processing', 202)
                ->header('X-Webhook-Duplicate', 'true');
        }

        return $this->process($event, $process, $request);
    }

    /** @param Closure(): Response $process */
    public function replay(GatewayWebhookEvent $event, Closure $process): Response
    {
        if (! $this->claim($event, true)) {
            return response('Already processing', 409);
        }

        return $this->process($event, $process);
    }

    /**
     * Remove secrets from data rendered in the administrator console. The
     * original payload remains intact for an authorized server-side replay.
     *
     * @param  array<string|int, mixed>|null  $payload
     * @return array<string|int, mixed>
     */
    public function sanitized(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $sensitive = [
            'authorization',
            'api_key',
            'apikey',
            'access_token',
            'client_secret',
            'secret',
            'password',
            'card_number',
            'cardnumber',
            'cvv',
            'cvc',
            'stripe-signature',
            'x-signature',
        ];

        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, $sensitive, true)) {
                $payload[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitized($value);
            }
        }

        return $payload;
    }

    /**
     * @param  Closure(): Response  $process
     */
    private function process(
        GatewayWebhookEvent $event,
        Closure $process,
        ?Request $request = null,
    ): Response {
        try {
            $response = $process();
            $successful = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;

            $event->update([
                'status' => $successful
                    ? GatewayWebhookEvent::STATUS_PROCESSED
                    : GatewayWebhookEvent::STATUS_FAILED,
                'response_code' => $response->getStatusCode(),
                'last_error' => $successful
                    ? null
                    : Str::limit(trim((string) $response->getContent()), 2000),
                'verified_at' => $successful
                    ? ($event->verified_at ?? now())
                    : $this->verifiedAt($event, $request),
                'processed_at' => $successful ? now() : null,
            ]);

            return $response;
        } catch (Throwable $exception) {
            $event->update([
                'status' => GatewayWebhookEvent::STATUS_FAILED,
                'response_code' => 500,
                'last_error' => Str::limit($exception->getMessage(), 2000),
                'verified_at' => $this->verifiedAt($event, $request),
                'processed_at' => null,
            ]);

            throw $exception;
        }
    }

    /**
     * Atomically claim an event so simultaneous deliveries cannot both run
     * payment side effects. A five-minute stale claim may be recovered.
     */
    private function claim(GatewayWebhookEvent $event, bool $allowProcessed = false): bool
    {
        $query = GatewayWebhookEvent::query()
            ->whereKey($event->id)
            ->where(function ($query) {
                $query->where('status', '!=', GatewayWebhookEvent::STATUS_PROCESSING)
                    ->orWhereNull('processing_started_at')
                    ->orWhere('processing_started_at', '<=', now()->subMinutes(5));
            });

        if (! $allowProcessed) {
            $query->where('status', '!=', GatewayWebhookEvent::STATUS_PROCESSED);
        }

        $claimed = $query->update([
            'status' => GatewayWebhookEvent::STATUS_PROCESSING,
            'attempts' => DB::raw('attempts + 1'),
            'processing_started_at' => now(),
            'last_error' => null,
            'updated_at' => now(),
        ]);

        if ($claimed === 1) {
            $event->refresh();
        }

        return $claimed === 1;
    }

    private function verifiedAt(GatewayWebhookEvent $event, ?Request $request): mixed
    {
        if ($event->verified_at !== null) {
            return $event->verified_at;
        }

        return $request?->attributes->getBoolean('webhook_payload_verified') ? now() : null;
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        $decoded = json_decode($request->getContent(), true);

        return is_array($decoded) ? $decoded : $request->all();
    }

    /** @param array<string, mixed> $payload */
    private function externalId(array $payload, string $payloadHash): string
    {
        foreach ([
            'id',
            'event_id',
            'eventId',
            'webhook_id',
            'resource.id',
            'trxID',
            'paymentID',
            'tran_id',
            'val_id',
        ] as $key) {
            $candidate = data_get($payload, $key);

            if (is_string($candidate) && $candidate !== '') {
                return Str::limit($candidate, 255, '');
            }
        }

        return "sha256:{$payloadHash}";
    }

    /** @param array<string, mixed> $payload */
    private function eventType(array $payload): ?string
    {
        foreach (['type', 'event_type', 'eventType', 'resource_type', 'status'] as $key) {
            $candidate = data_get($payload, $key);

            if (is_string($candidate) && $candidate !== '') {
                return Str::limit($candidate, 255, '');
            }
        }

        return null;
    }

    /** @return array<string, string|null> */
    private function headers(Request $request): array
    {
        return [
            'content-type' => $request->header('Content-Type'),
            'user-agent' => $request->header('User-Agent'),
            'stripe-signature' => $request->header('Stripe-Signature'),
            'paypal-transmission-id' => $request->header('Paypal-Transmission-Id'),
            'x-signature' => $request->header('X-Signature'),
            'source-ip' => $request->ip(),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        $encoded = json_encode($payload);

        return is_string($encoded) ? $encoded : '';
    }
}
