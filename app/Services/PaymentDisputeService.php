<?php

namespace App\Services;

use App\Enums\LicenseStatus;
use App\Enums\PaymentDisputeReason;
use App\Enums\PaymentDisputeStatus;
use App\Models\Order;
use App\Models\PaymentDispute;
use App\Models\PaymentDisputeLicenseAction;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class PaymentDisputeService
{
    /** @param array<string, mixed> $payload */
    public function syncStripe(array $payload, string $eventId, string $eventType): ?PaymentDispute
    {
        $gatewayId = $this->objectId($payload['id'] ?? null);
        $status = PaymentDisputeStatus::tryFrom((string) ($payload['status'] ?? ''));

        if ($gatewayId === null || $status === null) {
            return null;
        }

        return DB::transaction(function () use ($payload, $eventId, $eventType, $gatewayId, $status): PaymentDispute {
            $dispute = PaymentDispute::query()
                ->where('gateway', 'stripe')
                ->where('gateway_dispute_id', $gatewayId)
                ->lockForUpdate()
                ->first();
            $order = $dispute?->order ?? $this->findOrder($payload);
            $gatewayReason = is_string($payload['reason'] ?? null) ? $payload['reason'] : null;
            $openedAt = $this->timestamp($payload['created'] ?? null) ?? now();

            $attributes = [
                'order_id' => $order?->id,
                'payment_intent_id' => $this->objectId($payload['payment_intent'] ?? null),
                'charge_id' => $this->objectId($payload['charge'] ?? null),
                'currency' => strtoupper((string) ($payload['currency'] ?? config('asrtech.currency', 'USD'))),
                'amount' => round(((float) ($payload['amount'] ?? 0)) / 100, 2),
                'status' => $status,
                'reason' => PaymentDisputeReason::normalize($gatewayReason),
                'gateway_reason' => $gatewayReason,
                'evidence_due_at' => $this->timestamp(data_get($payload, 'evidence_details.due_by')),
                'has_evidence' => (bool) data_get($payload, 'evidence_details.has_evidence', false),
                'evidence_past_due' => (bool) data_get($payload, 'evidence_details.past_due', false),
                'submission_count' => max(0, (int) data_get($payload, 'evidence_details.submission_count', 0)),
                'livemode' => (bool) ($payload['livemode'] ?? false),
                'provider_payload' => $payload,
                'opened_at' => $dispute?->opened_at ?? $openedAt,
                'closed_at' => $status->isClosed() ? now() : null,
            ];

            if ($dispute === null) {
                $dispute = PaymentDispute::query()->create([
                    ...$attributes,
                    'gateway' => 'stripe',
                    'gateway_dispute_id' => $gatewayId,
                ]);
            } else {
                $dispute->update($attributes);
            }

            $dispute->events()->firstOrCreate(
                ['gateway_event_id' => $eventId],
                [
                    'event_type' => $eventType,
                    'status' => $status->value,
                    'payload' => $payload,
                    'processed_at' => now(),
                ],
            );

            $this->applyLicensePolicy($dispute, $status);

            return $dispute->refresh();
        });
    }

    /** @param array<string, mixed> $payload */
    private function findOrder(array $payload): ?Order
    {
        $metadataOrderId = data_get($payload, 'metadata.order_id')
            ?? data_get($payload, 'charge.metadata.order_id');

        if (is_numeric($metadataOrderId)) {
            $order = Order::query()->find((int) $metadataOrderId);
            if ($order !== null) {
                return $order;
            }
        }

        $paymentIntent = $this->objectId($payload['payment_intent'] ?? null)
            ?? $this->objectId(data_get($payload, 'charge.payment_intent'));
        if ($paymentIntent !== null) {
            $order = Order::query()
                ->where('gateway_payment_id', $paymentIntent)
                ->orWhere('payment_reference', $paymentIntent)
                ->first();
            if ($order !== null) {
                return $order;
            }
        }

        $charge = $this->objectId($payload['charge'] ?? null);

        return $charge === null
            ? null
            : Order::query()->where('payment_reference', $charge)->first();
    }

    private function applyLicensePolicy(PaymentDispute $dispute, PaymentDisputeStatus $status): void
    {
        if ($dispute->order_id === null) {
            return;
        }

        if ($status->isFormalOpen()) {
            $dispute->order->licenses()
                ->where('status', LicenseStatus::Active)
                ->get()
                ->each(function ($license) use ($dispute): void {
                    $action = $dispute->licenseActions()->firstOrCreate(
                        ['license_id' => $license->id],
                        [
                            'previous_status' => $license->status->value,
                            'action' => 'suspended',
                            'acted_at' => now(),
                        ],
                    );

                    if ($action->wasRecentlyCreated) {
                        $license->suspend();
                    }
                });

            return;
        }

        if (in_array($status, [PaymentDisputeStatus::Won, PaymentDisputeStatus::Prevented], true)) {
            $this->restoreSuspendedLicenses($dispute);

            return;
        }

        if ($status === PaymentDisputeStatus::Lost) {
            $dispute->licenseActions()
                ->where('action', 'suspended')
                ->with('license')
                ->get()
                ->each(function (PaymentDisputeLicenseAction $action): void {
                    if ($action->license->status === LicenseStatus::Suspended) {
                        $action->license->terminate();
                        $action->update(['action' => 'terminated', 'acted_at' => now()]);
                    }
                });
        }
    }

    private function restoreSuspendedLicenses(PaymentDispute $dispute): void
    {
        $dispute->licenseActions()
            ->where('action', 'suspended')
            ->with('license')
            ->get()
            ->each(function (PaymentDisputeLicenseAction $action): void {
                if ($action->license->status === LicenseStatus::Suspended
                    && $action->previous_status === LicenseStatus::Active->value) {
                    $action->license->unsuspend();
                    $action->update(['action' => 'restored', 'acted_at' => now()]);
                }
            });
    }

    private function timestamp(mixed $value): mixed
    {
        return is_numeric($value) ? Date::createFromTimestamp((int) $value) : null;
    }

    private function objectId(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return is_array($value) && is_string($value['id'] ?? null)
            ? $value['id']
            : null;
    }
}
