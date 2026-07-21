<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int|null $product_price_id
 * @property int $order_id
 * @property int $license_id
 * @property string $gateway
 * @property string|null $gateway_customer_id
 * @property string|null $gateway_subscription_id
 * @property SubscriptionStatus $status
 * @property BillingCycle $billing_cycle
 * @property string $currency
 * @property string $amount
 * @property CarbonInterface|null $current_period_start
 * @property CarbonInterface|null $current_period_end
 * @property bool $cancel_at_period_end
 * @property CarbonInterface|null $canceled_at
 * @property CarbonInterface|null $ended_at
 * @property CarbonInterface|null $last_payment_at
 * @property array<string, mixed>|null $metadata
 * @property int $failed_payments_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'user_id',
    'product_id',
    'product_price_id',
    'order_id',
    'license_id',
    'gateway',
    'gateway_customer_id',
    'gateway_subscription_id',
    'status',
    'billing_cycle',
    'currency',
    'amount',
    'current_period_start',
    'current_period_end',
    'cancel_at_period_end',
    'canceled_at',
    'ended_at',
    'last_payment_at',
    'metadata',
])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'amount' => 'decimal:2',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'canceled_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_payment_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductPrice, $this> */
    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<License, $this> */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /** @return HasMany<Order, $this> */
    public function renewalOrders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<SubscriptionEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SubscriptionEvent::class);
    }

    /** @return HasOne<SubscriptionEvent, $this> */
    public function latestPaymentFailure(): HasOne
    {
        return $this->hasOne(SubscriptionEvent::class)
            ->ofMany(
                ['processed_at' => 'max', 'id' => 'max'],
                fn ($query) => $query->where('event_type', 'invoice.payment_failed'),
            );
    }
}
