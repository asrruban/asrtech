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
 * @property int $user_id
 * @property string $code
 * @property string $commission_rate
 * @property bool $active
 * @property string $pending_balance
 * @property string $approved_balance
 * @property string $paid_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'code', 'commission_rate', 'active', 'pending_balance', 'approved_balance', 'paid_balance'])]
class Affiliate extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'commission_rate' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'approved_balance' => 'decimal:2',
            'paid_balance' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Referral, $this> */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function referralUrl(): string
    {
        return url('/?ref='.$this->code);
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
