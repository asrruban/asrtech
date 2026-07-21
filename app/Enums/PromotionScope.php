<?php

namespace App\Enums;

enum PromotionScope: string
{
    case All = 'all';
    case OneTime = 'one_time';
    case Recurring = 'recurring';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
