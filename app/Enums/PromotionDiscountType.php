<?php

namespace App\Enums;

enum PromotionDiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
