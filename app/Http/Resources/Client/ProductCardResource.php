<?php

namespace App\Http\Resources\Client;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductCardResource extends JsonResource
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
            'type' => $this->type,
            'type_name' => $this->productType?->name,
            'type_slug' => $this->productType?->slug,
            'badge' => $this->badge,
            'short_description' => $this->short_description,
            'featured_image' => $this->featured_image,
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'prices' => ProductPriceResource::collection($this->whenLoaded('visiblePrices')),
        ];
    }
}
