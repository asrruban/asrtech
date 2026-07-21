<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $subscription_id
 * @property string $gateway
 * @property string $gateway_event_id
 * @property string $event_type
 * @property array<string, mixed>|null $payload
 * @property CarbonInterface $processed_at
 */
#[Fillable([
    'subscription_id',
    'gateway',
    'gateway_event_id',
    'event_type',
    'payload',
    'processed_at',
])]
class SubscriptionEvent extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
