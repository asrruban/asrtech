<?php

namespace App\Models;

use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $affiliate_id
 * @property int $referred_user_id
 * @property int $order_id
 * @property string $order_total
 * @property string $commission_amount
 * @property ReferralStatus $status
 * @property Carbon|null $approved_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['affiliate_id', 'referred_user_id', 'order_id', 'order_total', 'commission_amount', 'status', 'approved_at', 'paid_at'])]
class Referral extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'order_total' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Affiliate, $this> */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
