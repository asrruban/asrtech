<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Incomplete = 'incomplete';
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Paused = 'paused';
    case Canceled = 'canceled';

    public function providesAccess(): bool
    {
        return in_array($this, [self::Trialing, self::Active], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }
}
