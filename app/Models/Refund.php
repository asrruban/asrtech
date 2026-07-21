<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property RefundStatus $status
 * @property string $amount
 * @property CarbonInterface|null $processed_at
 */
#[Fillable([
    'order_id',
    'invoice_id',
    'original_transaction_id',
    'transaction_id',
    'admin_id',
    'refund_number',
    'idempotency_key',
    'gateway',
    'gateway_reference',
    'currency',
    'amount',
    'status',
    'reason',
    'record_only',
    'revoke_access',
    'failure_message',
    'processed_at',
])]
class Refund extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
            'record_only' => 'boolean',
            'revoke_access' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Transaction, $this> */
    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'original_transaction_id');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** @return HasOne<CreditNote, $this> */
    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    /** @return HasOne<RefundRequest, $this> */
    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class);
    }
}
