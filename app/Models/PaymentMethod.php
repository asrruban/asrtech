<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $gateway
 * @property string $type
 * @property string|null $card_brand
 * @property string|null $card_last_four
 * @property int|null $card_expiry_month
 * @property int|null $card_expiry_year
 * @property string|null $token
 * @property string|null $name_on_card
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'user_id',
    'gateway',
    'type',
    'card_brand',
    'card_last_four',
    'card_expiry_month',
    'card_expiry_year',
    'token',
    'name_on_card',
])]
#[Hidden(['token'])]
class PaymentMethod extends Model
{
    protected function casts(): array
    {
        return [
            'card_expiry_month' => 'integer',
            'card_expiry_year' => 'integer',
            'token' => 'encrypted',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
