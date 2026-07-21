<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property CarbonInterface $processed_at */
#[Fillable(['payment_dispute_id', 'gateway_event_id', 'event_type', 'status', 'payload', 'processed_at'])]
class PaymentDisputeEvent extends Model
{
    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }

    /** @return BelongsTo<PaymentDispute, $this> */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(PaymentDispute::class, 'payment_dispute_id');
    }
}
