<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property string $platform
 * @property string $minimum_version
 * @property string $maximum_version
 * @property int $minimum_version_number
 * @property int $maximum_version_number
 * @property bool $published
 */
#[Fillable(['platform', 'minimum_version', 'maximum_version', 'published'])]
class ProductCompatibility extends Model
{
    public const PLATFORMS = ['whmcs' => 'WHMCS', 'wordpress' => 'WordPress', 'php' => 'PHP'];

    // Stable versions only. Every component is 0–999 with no leading zeroes.
    public const VERSION_PATTERN = '/^(?:0|[1-9][0-9]{0,2})\.(?:0|[1-9][0-9]{0,2})(?:\.(?:0|[1-9][0-9]{0,2}))?$/D';

    protected static function booted(): void
    {
        static::saving(function (self $compatibility): void {
            $compatibility->minimum_version = self::normalizeVersion($compatibility->minimum_version);
            $compatibility->maximum_version = self::normalizeVersion($compatibility->maximum_version);
            $compatibility->minimum_version_number = self::versionNumber($compatibility->minimum_version);
            $compatibility->maximum_version_number = self::versionNumber($compatibility->maximum_version);
        });
    }

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'minimum_version_number' => 'integer',
            'maximum_version_number' => 'integer',
        ];
    }

    public static function normalizeVersion(string $version): string
    {
        if (preg_match(self::VERSION_PATTERN, $version) !== 1) {
            throw new InvalidArgumentException('Enter a stable version as major.minor or major.minor.patch.');
        }

        return count(explode('.', $version)) === 2 ? $version.'.0' : $version;
    }

    public static function versionNumber(string $version): int
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', self::normalizeVersion($version)));

        return $major * 1000000 + $minor * 1000 + $patch;
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
