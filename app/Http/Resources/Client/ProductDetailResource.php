<?php

namespace App\Http\Resources\Client;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->storefrontUrl(),
            'documentation_path' => $this->documentationUrl(),
            'type' => $this->type,
            'type_name' => $this->productType?->name,
            'type_slug' => $this->productType?->slug,
            'badge' => $this->badge,
            'version' => $this->version,
            'release_date' => $this->release_date,
            'compatibility' => $this->compatibility,
            'php_compatibility' => $this->php_compatibility,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'featured_image' => $this->featured_image,
            'demo_url' => $this->demo_url,
            'documentation_url' => $this->documentation_url,
            'purchase_url' => $this->purchase_url,
            'trial_url' => $this->trial_url,
            'has_free_trial' => $this->has_free_trial,
            'documentation_content' => $this->documentation_content,
            'gallery' => $this->gallery,
            'feature_groups' => $this->feature_groups,
            'requirements' => $this->requirements,
            'changelog' => $this->changelogEntries(),
            'addons' => $this->addons,
            'reviews' => $this->reviewEntries(),
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'prices' => ProductPriceResource::collection($this->whenLoaded('visiblePrices')),
            'seo' => $this->whenLoaded('seo', fn () => $this->seo?->only([
                'meta_title',
                'meta_description',
                'keywords',
                'canonical_url',
                'robots',
                'open_graph_title',
                'open_graph_description',
                'open_graph_image',
                'twitter_card',
                'schema_json',
            ])),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function changelogEntries(): array
    {
        $legacy = is_array($this->changelog) ? $this->changelog : [];

        if (! $this->relationLoaded('releases')) {
            return array_values($legacy);
        }

        $releases = $this->releases->map(fn ($release): array => [
            'version' => $release->version,
            'released_at' => $release->released_at?->toDateString(),
            'notes' => array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', (string) $release->release_notes) ?: [],
            ))),
        ])->all();
        $releaseVersions = array_column($releases, 'version');

        return [
            ...$releases,
            ...array_values(array_filter(
                $legacy,
                fn (mixed $entry): bool => is_array($entry)
                    && ! in_array($entry['version'] ?? null, $releaseVersions, true),
            )),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function reviewEntries(): array
    {
        $legacy = is_array($this->reviews) ? $this->reviews : [];

        if (! $this->relationLoaded('customerReviews')) {
            return array_values($legacy);
        }

        return [
            ...$this->customerReviews->map(fn ($review): array => [
                'id' => $review->id,
                'name' => $review->user?->name ?? 'Customer',
                'title' => $review->title,
                'rating' => $review->rating,
                'content' => $review->content,
                'reviewed_at' => $review->created_at?->toDateString(),
                'verified_purchase' => true,
            ])->all(),
            ...array_values($legacy),
        ];
    }
}
