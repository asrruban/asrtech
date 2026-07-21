<?php

namespace App\Enums;

enum ProductType: string
{
    case WhmcsModule = 'whmcs_module';
    case Template = 'template';
    case WebDevelopment = 'web_development';
    case License = 'license';
    case OtherDigital = 'other_digital';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
