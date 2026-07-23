<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $company_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postcode
 * @property string|null $country
 * @property string|null $vat_number
 * @property bool $newsletter
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $social_provider
 * @property string|null $social_provider_id
 * @property string|null $remember_token
 * @property string|null $avatar
 * @property string|null $admin_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'company_name',
    'email',
    'phone',
    'address_1',
    'address_2',
    'city',
    'state',
    'postcode',
    'country',
    'vat_number',
    'newsletter',
    'password',
    'avatar',
    'social_provider',
    'social_provider_id',
    'admin_notes',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'newsletter' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<ProductReview, $this> */
    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /** @return HasMany<License, $this> */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasMany<PromotionRedemption, $this> */
    public function promotionRedemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    /** @return HasMany<RefundRequest, $this> */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    /** @return HasMany<PaymentMethod, $this> */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function hasUsedFreeTrial(int|Product $product): bool
    {
        $productId = $product instanceof Product ? $product->id : $product;

        return $this->orders()
            ->where('product_id', $productId)
            ->where('payment_method', 'free_trial')
            ->where('status', OrderStatus::Paid)
            ->exists();
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
