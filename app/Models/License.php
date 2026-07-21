<?php

namespace App\Models;

use App\Enums\LicenseStatus;
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
 * @property int $order_id
 * @property string $license_key
 * @property LicenseStatus $status
 * @property CarbonInterface|null $expires_at
 * @property string|null $domain
 * @property string|null $path
 * @property string|null $ip_address
 * @property int $reissue_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'user_id',
    'product_id',
    'order_id',
    'license_key',
    'status',
    'expires_at',
    'domain',
    'path',
    'ip_address',
    'reissue_count',
])]
class License extends Model
{
    protected function casts(): array
    {
        return [
            'status' => LicenseStatus::class,
            'expires_at' => 'datetime',
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

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<LicenseAccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(LicenseAccessLog::class);
    }

    /** @return HasMany<ProductReleaseDownload, $this> */
    public function releaseDownloads(): HasMany
    {
        return $this->hasMany(ProductReleaseDownload::class);
    }

    /** @return HasOne<Subscription, $this> */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Valid domains, comma-separated in storage (WHMCS style).
     *
     * @return list<string>
     */
    public function validDomains(): array
    {
        return $this->splitList($this->domain);
    }

    /** @return list<string> */
    public function validIps(): array
    {
        return $this->splitList($this->ip_address);
    }

    /** @return list<string> */
    public function validDirectories(): array
    {
        return $this->splitList($this->path);
    }

    /** @return list<string> */
    private function splitList(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $item): string => strtolower(trim($item)),
            explode(',', (string) $value),
        )));
    }

    public function suspend(): void
    {
        $this->update(['status' => LicenseStatus::Suspended]);
    }

    public function unsuspend(): void
    {
        $this->update(['status' => LicenseStatus::Active]);
    }

    /**
     * Terminate permanently. Terminated licenses cannot be reinstated.
     */
    public function terminate(): void
    {
        $this->update(['status' => LicenseStatus::Terminated]);
    }

    /**
     * Clear the recorded installation so the license can activate on a
     * new domain, path, and IP — WHMCS "reissue" semantics.
     */
    public function reissue(): void
    {
        $this->update([
            'domain' => null,
            'path' => null,
            'ip_address' => null,
            'reissue_count' => $this->reissue_count + 1,
        ]);
    }

    public function resetReissues(): void
    {
        $this->update(['reissue_count' => 0]);
    }
}
