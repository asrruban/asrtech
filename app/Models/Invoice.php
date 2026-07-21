<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property string $invoice_number
 * @property InvoiceStatus $status
 * @property CarbonInterface $issued_at
 * @property CarbonInterface|null $due_at
 * @property CarbonInterface|null $last_reminder_at
 * @property string|null $notes
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'order_id',
    'invoice_number',
    'status',
    'issued_at',
    'due_at',
    'last_reminder_at',
    'notes',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'last_reminder_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /** @return HasMany<CreditNote, $this> */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /** @return HasMany<RefundRequest, $this> */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }
}
