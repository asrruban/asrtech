<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property BillingCycle $billing_cycle
 */
#[Fillable([
    'order_id',
    'product_id',
    'product_price_id',
    'product_name',
    'price_name',
    'currency',
    'amount',
    'setup_fee',
    'billing_cycle',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'billing_cycle' => BillingCycle::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductPrice, $this> */
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }
}
