<?php

namespace App\Enums;

enum BillingCycle: string
{
    case OneTime = 'one_time';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $cycle): string => $cycle->value,
            self::cases(),
        );
    }
}
