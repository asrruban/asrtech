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
    'billing_cycle',
    'name',
    'description',
    'currency',
    'price',
    'sale_price',
    'setup_fee',
    'purchase_url',
    'features',
    'featured',
    'enabled',
])]
class ProductPrice extends Model
{
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'enabled' => 'boolean',
            'features' => 'array',
            'featured' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
