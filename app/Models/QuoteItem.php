<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quote_id
 * @property int $product_id
 * @property string $product_name
 * @property int $quantity
 * @property string $unit_price
 * @property string $line_total
 * @property BillingCycle $billing_cycle
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['quote_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'line_total', 'billing_cycle'])]
class QuoteItem extends Model
{
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
