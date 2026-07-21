<?php

namespace App\Http\Resources\Client;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'setup_fee' => $this->setup_fee,
            'billing_cycle' => $this->billing_cycle,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'product' => [
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'url' => $this->product->storefrontUrl(),
            ],
            'license_key' => $this->license?->license_key,
        ];
    }
}
