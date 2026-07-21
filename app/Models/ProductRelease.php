<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $version
 * @property string|null $title
 * @property string|null $release_notes
 * @property string $disk
 * @property string $file_path
 * @property string $original_filename
 * @property string|null $mime_type
 * @property int $file_size
 * @property string $checksum_sha256
 * @property CarbonInterface $released_at
 * @property CarbonInterface|null $available_until
 * @property int|null $download_limit
 * @property bool $status
 * @property int $downloads_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'product_id',
    'version',
    'title',
    'release_notes',
    'disk',
    'file_path',
    'original_filename',
    'mime_type',
    'file_size',
    'checksum_sha256',
    'released_at',
    'available_until',
    'download_limit',
    'status',
])]
class ProductRelease extends Model
{
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'download_limit' => 'integer',
            'released_at' => 'datetime',
            'available_until' => 'datetime',
            'status' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<ProductReleaseDownload, $this> */
    public function downloads(): HasMany
    {
        return $this->hasMany(ProductReleaseDownload::class);
    }

    /** @param Builder<ProductRelease> $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', true)
            ->where('released_at', '<=', now())
            ->where(function (Builder $query) {
                $query->whereNull('available_until')
                    ->orWhere('available_until', '>', now());
            });
    }

    public function isAvailable(): bool
    {
        return $this->status
            && $this->released_at->isPast()
            && ($this->available_until === null || $this->available_until->isFuture());
    }
}
