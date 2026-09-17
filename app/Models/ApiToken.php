<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $admin_id
 * @property string $name
 * @property string $token_hash
 * @property Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['admin_id', 'name', 'token_hash', 'last_used_at', 'last_used_ip', 'revoked_at'])]
class ApiToken extends Model
{
    public const PREFIX = 'asr_';

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * Create a token and return the plain-text value exactly once.
     *
     * @return array{0: ApiToken, 1: string}
     */
    public static function issue(Admin $admin, string $name): array
    {
        $plain = self::PREFIX.Str::random(48);

        $token = self::query()->create([
            'admin_id' => $admin->id,
            'name' => $name,
            'token_hash' => self::hash($plain),
        ]);

        return [$token, $plain];
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
