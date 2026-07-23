<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

#[Fillable([
    'category_id',
    'group_id',
    'name',
    'slug',
    'sku',
    'type',
    'badge',
    'version',
    'release_date',
    'compatibility',
    'php_compatibility',
    'description',
    'short_description',
    'featured_image',
    'demo_url',
    'documentation_url',
    'documentation_title',
    'purchase_url',
    'trial_url',
    'documentation_content',
    'documentation_meta_title',
    'documentation_meta_description',
    'documentation_keywords',
    'documentation_robots',
    'documentation_open_graph_image',
    'gallery',
    'feature_groups',
    'requirements',
    'changelog',
    'addons',
    'reviews',
    'price',
    'status',
    'featured',
    'has_free_trial',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => 'boolean',
            'featured' => 'boolean',
            'has_free_trial' => 'boolean',
            'release_date' => 'date:Y-m-d',
            'gallery' => 'array',
            'feature_groups' => 'array',
            'requirements' => 'array',
            'changelog' => 'array',
            'addons' => 'array',
            'reviews' => 'array',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<ProductType, $this> */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'type', 'key');
    }

    /** @return array{productType: string, product: string} */
    public function storefrontRouteParameters(): array
    {
        $typeSlug = $this->relationLoaded('productType')
            ? $this->productType?->slug
            : $this->productType()->value('slug');

        return [
            'productType' => $typeSlug ?: Str::slug((string) $this->type),
            'product' => $this->slug,
        ];
    }

    public function storefrontUrl(): string
    {
        return route('products.show', $this->storefrontRouteParameters());
    }

    public function documentationUrl(): string
    {
        return route('products.documentation', $this->storefrontRouteParameters());
    }

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /** @return HasMany<ProductRelease, $this> */
    public function releases(): HasMany
    {
        return $this->hasMany(ProductRelease::class);
    }

    /** @return HasMany<ProductReview, $this> */
    public function customerReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return BelongsToMany<PromotionCode, $this> */
    public function promotionCodes(): BelongsToMany
    {
        return $this->belongsToMany(PromotionCode::class);
    }

    /**
     * Prices shown on the storefront: enabled only, featured plan first, then cheapest.
     *
     * @return HasMany<ProductPrice, $this>
     */
    public function visiblePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('enabled', true)
            ->orderByDesc('featured')
            ->orderBy('price');
    }

    /** @return MorphOne<SeoMetadata, $this> */
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }
}
