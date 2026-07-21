<?php

namespace App\Models;

use App\Enums\PaymentDisputeReason;
use App\Enums\PaymentDisputeStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $order_id
 * @property string $gateway
 * @property string $gateway_dispute_id
 * @property string|null $payment_intent_id
 * @property string|null $charge_id
 * @property string $currency
 * @property string $amount
 * @property PaymentDisputeStatus $status
 * @property PaymentDisputeReason|null $reason
 * @property string|null $gateway_reason
 * @property CarbonInterface|null $evidence_due_at
 * @property bool $has_evidence
 * @property bool $evidence_past_due
 * @property int $submission_count
 * @property bool $livemode
 * @property string|null $admin_note
 * @property array<string, mixed>|null $provider_payload
 * @property CarbonInterface $opened_at
 * @property CarbonInterface|null $closed_at
 */
#[Fillable([
    'order_id', 'gateway', 'gateway_dispute_id', 'payment_intent_id', 'charge_id',
    'currency', 'amount', 'status', 'reason', 'gateway_reason', 'evidence_due_at',
    'has_evidence', 'evidence_past_due', 'submission_count', 'livemode',
    'admin_note', 'provider_payload', 'opened_at', 'closed_at',
])]
class PaymentDispute extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentDisputeStatus::class,
            'reason' => PaymentDisputeReason::class,
            'evidence_due_at' => 'datetime',
            'has_evidence' => 'boolean',
            'evidence_past_due' => 'boolean',
            'submission_count' => 'integer',
            'livemode' => 'boolean',
            'provider_payload' => 'array',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<PaymentDisputeEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentDisputeEvent::class);
    }

    /** @return HasMany<PaymentDisputeLicenseAction, $this> */
    public function licenseActions(): HasMany
    {
        return $this->hasMany(PaymentDisputeLicenseAction::class);
    }
}
