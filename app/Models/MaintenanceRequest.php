<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<string, mixed> $plan_snapshot
 * @property Carbon $scope_acknowledged_at
 */
#[Fillable(['maintenance_plan_id', 'user_id', 'plan_snapshot', 'website', 'requirements', 'status', 'client_update', 'internal_notes', 'subscription_id', 'quote_id', 'checkout_order_id', 'checkout_redirect_url', 'scope_acknowledged_at'])]
class MaintenanceRequest extends Model
{
    public const STATUSES = ['requested', 'reviewing', 'awaiting_payment', 'active', 'paused', 'completed', 'declined', 'withdrawn'];

    protected function casts(): array
    {
        return ['plan_snapshot' => 'array', 'checkout_redirect_url' => 'encrypted', 'scope_acknowledged_at' => 'datetime'];
    }

    /** @return BelongsTo<MaintenancePlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function checkoutOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'checkout_order_id');
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
