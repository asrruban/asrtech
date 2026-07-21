<?php

namespace App\Http\Resources\Client;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin License */
class LicenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'license_key' => $this->license_key,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'domain' => $this->domain,
            'path' => $this->path,
            'ip_address' => $this->ip_address,
            'product' => [
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'url' => $this->product->storefrontUrl(),
                'type' => $this->product->type,
                'featured_image' => $this->product->featured_image,
            ],
            'order_number' => $this->order->order_number,
        ];
    }
}
