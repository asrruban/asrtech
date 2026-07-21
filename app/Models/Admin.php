<?php

namespace App\Models;

use App\Enums\AdminRole;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property AdminRole $role
 * @property string|null $two_factor_secret
 * @property list<string>|null $two_factor_recovery_codes
 * @property CarbonInterface|null $two_factor_confirmed_at
 * @property int|null $two_factor_last_counter
 * @property CarbonInterface|null $last_login_at
 * @property string|null $last_login_ip
 */
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
    'two_factor_last_counter',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class Admin extends Authenticatable
{
    use Notifiable;

    protected $attributes = [
        'role' => AdminRole::SuperAdmin->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => AdminRole::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_last_counter' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->allows($permission);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }
}
