<?php

namespace App\Http\Resources\Client;

use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductPrice */
class ProductPriceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'billing_cycle' => $this->billing_cycle,
            'name' => $this->name,
            'description' => $this->description,
            'currency' => $this->currency,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'setup_fee' => $this->setup_fee,
            'purchase_url' => $this->purchase_url,
            'features' => $this->features,
            'featured' => $this->featured,
            'enabled' => $this->enabled,
        ];
    }
}
