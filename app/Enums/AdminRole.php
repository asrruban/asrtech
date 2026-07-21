<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case Billing = 'billing';
    case Support = 'support';
    case Catalog = 'catalog';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrator',
            self::Billing => 'Billing administrator',
            self::Support => 'Support administrator',
            self::Catalog => 'Catalog administrator',
        };
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => ['*'],
            self::Billing => [
                'billing.manage',
                'users.view',
                'users.manage',
                'licenses.view',
                'licenses.manage',
            ],
            self::Support => [
                'support.manage',
                'users.view',
                'licenses.view',
            ],
            self::Catalog => [
                'catalog.manage',
                'content.manage',
            ],
        };
    }

    public function allows(string $permission): bool
    {
        $permissions = $this->permissions();

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);
    }
}
