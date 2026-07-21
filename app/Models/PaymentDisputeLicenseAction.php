<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property string $action @property CarbonInterface $acted_at */
#[Fillable(['payment_dispute_id', 'license_id', 'previous_status', 'action', 'acted_at'])]
class PaymentDisputeLicenseAction extends Model
{
    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    /** @return BelongsTo<PaymentDispute, $this> */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(PaymentDispute::class, 'payment_dispute_id');
    }

    /** @return BelongsTo<License, $this> */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
