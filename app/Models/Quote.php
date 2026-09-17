<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $quote_number
 * @property int $user_id
 * @property QuoteStatus $status
 * @property string $currency
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $total
 * @property string|null $admin_note
 * @property string|null $client_note
 * @property Carbon|null $valid_until
 * @property Carbon|null $sent_at
 * @property Carbon|null $responded_at
 * @property int|null $order_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'quote_number', 'user_id', 'status', 'currency', 'subtotal', 'tax_amount', 'total',
    'admin_note', 'client_note', 'valid_until', 'sent_at', 'responded_at', 'order_id',
])]
class Quote extends Model
{
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function isActionable(): bool
    {
        return $this->status->isActionable() && ! $this->isExpired();
    }

    /** Recalculate subtotal/total from items. */
    public function recalculate(float $taxRatePercent = 0.0): void
    {
        $subtotal = $this->items->sum(fn (QuoteItem $item): float => (float) $item->line_total);
        $tax = round($subtotal * ($taxRatePercent / 100), 2);

        $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->tax_amount = number_format($tax, 2, '.', '');
        $this->total = number_format($subtotal + $tax, 2, '.', '');
    }
}
