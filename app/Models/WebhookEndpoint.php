<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $admin_id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property list<string> $events
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['admin_id', 'name', 'url', 'secret', 'events', 'enabled'])]
class WebhookEndpoint extends Model
{
    public const AVAILABLE_EVENTS = [
        'order.paid',
        'subscription.payment_failed',
        'subscription.cancellation_scheduled',
        'refund.decided',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true);
    }

    public static function generateSecret(): string
    {
        return 'whsec_'.Str::random(32);
    }
}
