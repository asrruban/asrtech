<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
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
 * @property int|null $subscription_id
 * @property string $order_number
 * @property string $currency
 * @property string $subtotal
 * @property string $amount
 * @property string $discount_amount
 * @property string $setup_fee
 * @property string $tax_amount
 * @property string|null $tax_rate
 * @property string|null $tax_name
 * @property BillingCycle $billing_cycle
 * @property OrderStatus $status
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property string|null $gateway_payment_id
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'user_id',
    'product_id',
    'product_price_id',
    'subscription_id',
    'promotion_code_id',
    'promotion_code',
    'order_number',
    'currency',
    'subtotal',
    'amount',
    'discount_amount',
    'setup_fee',
    'tax_amount',
    'tax_rate',
    'tax_name',
    'billing_cycle',
    'status',
    'payment_method',
    'payment_reference',
    'gateway_payment_id',
    'paid_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'billing_cycle' => BillingCycle::class,
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
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

    /**
     * The subscription renewed by this order, when this is a renewal.
     *
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<PromotionCode, $this> */
    public function promotionCode(): BelongsTo
    {
        return $this->belongsTo(PromotionCode::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function initialSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasOne<License, $this> */
    public function license(): HasOne
    {
        return $this->hasOne(License::class);
    }

    /** @return HasMany<License, $this> */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasOne<Invoice, $this> */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /** @return HasMany<PaymentDispute, $this> */
    public function paymentDisputes(): HasMany
    {
        return $this->hasMany(PaymentDispute::class);
    }

    public function refundedAmount(): float
    {
        return round((float) $this->refunds()
            ->whereIn('status', RefundStatus::accepted())
            ->sum('amount'), 2);
    }

    public function refundableAmount(): float
    {
        return max(0, round($this->totalAmount() - $this->refundedAmount(), 2));
    }

    /** @return HasOne<PromotionRedemption, $this> */
    public function promotionRedemption(): HasOne
    {
        return $this->hasOne(PromotionRedemption::class);
    }

    public function totalAmount(): float
    {
        return round((float) $this->amount + (float) $this->setup_fee + (float) $this->tax_amount, 2);
    }
}
