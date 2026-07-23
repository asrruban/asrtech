<?php

namespace App\Models;

use App\Enums\ProductReviewStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $user_id
 * @property int $rating
 * @property string|null $title
 * @property string $content
 * @property ProductReviewStatus $status
 * @property string|null $moderation_note
 * @property int|null $moderated_by
 * @property CarbonInterface|null $moderated_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'product_id',
    'user_id',
    'rating',
    'title',
    'content',
    'status',
    'moderation_note',
    'moderated_by',
    'moderated_at',
])]
class ProductReview extends Model
{
    protected $attributes = [
        'status' => ProductReviewStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ProductReviewStatus::class,
            'moderated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'moderated_by');
    }

    /** @param Builder<ProductReview> $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ProductReviewStatus::Approved);
    }
}
