<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'slug', 'platform', 'summary', 'scope', 'exclusions', 'support_arrangements', 'product_price_id', 'published'])]
class MaintenancePlan extends Model
{
    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    /** @return BelongsTo<ProductPrice, $this> */
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }
}
