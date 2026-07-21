<?php

namespace App\Models;

use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property PromotionDiscountType $discount_type
 * @property PromotionScope $scope
 * @property string $value
 * @property string|null $currency
 * @property string|null $minimum_subtotal
 * @property string|null $maximum_discount
 * @property int|null $usage_limit
 * @property int|null $per_customer_limit
 * @property bool $active
 * @property CarbonInterface|null $starts_at
 * @property CarbonInterface|null $ends_at
 */
#[Fillable([
    'code',
    'name',
    'discount_type',
    'value',
    'currency',
    'minimum_subtotal',
    'maximum_discount',
    'usage_limit',
    'per_customer_limit',
    'scope',
    'active',
    'starts_at',
    'ends_at',
])]
class PromotionCode extends Model
{
    protected function casts(): array
    {
        return [
            'discount_type' => PromotionDiscountType::class,
            'scope' => PromotionScope::class,
            'value' => 'decimal:2',
            'minimum_subtotal' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** @return HasMany<PromotionRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }
}
