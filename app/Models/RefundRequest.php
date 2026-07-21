<?php

namespace App\Models;

use App\Enums\RefundRequestStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $user_id
 * @property int|null $refund_id
 * @property int|null $decided_by
 * @property string $request_number
 * @property string $idempotency_key
 * @property string $currency
 * @property string $amount
 * @property RefundRequestStatus $status
 * @property string $reason
 * @property string|null $admin_note
 * @property CarbonInterface $submitted_at
 * @property CarbonInterface|null $decided_at
 */
#[Fillable([
    'invoice_id', 'user_id', 'refund_id', 'decided_by', 'request_number',
    'idempotency_key', 'currency', 'amount', 'status', 'reason', 'admin_note',
    'submitted_at', 'decided_at',
])]
class RefundRequest extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundRequestStatus::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Refund, $this> */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'decided_by');
    }
}
