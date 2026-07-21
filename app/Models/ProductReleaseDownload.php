<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_release_id',
    'license_id',
    'user_id',
    'ip_address',
    'user_agent',
    'downloaded_at',
])]
class ProductReleaseDownload extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductRelease, $this> */
    public function release(): BelongsTo
    {
        return $this->belongsTo(ProductRelease::class, 'product_release_id');
    }

    /** @return BelongsTo<License, $this> */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
