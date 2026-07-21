<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $net_amount
 * @property string $tax_amount
 * @property string $total_amount
 * @property CarbonInterface $issued_at
 */
#[Fillable([
    'refund_id',
    'invoice_id',
    'credit_note_number',
    'currency',
    'net_amount',
    'tax_amount',
    'total_amount',
    'tax_name',
    'tax_rate',
    'reason',
    'issued_at',
])]
class CreditNote extends Model
{
    protected function casts(): array
    {
        return [
            'net_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Refund, $this> */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
