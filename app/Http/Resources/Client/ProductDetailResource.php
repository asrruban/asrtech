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
            'changelog' => $this->changelog,
            'addons' => $this->addons,
            'reviews' => $this->reviews,
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
}
