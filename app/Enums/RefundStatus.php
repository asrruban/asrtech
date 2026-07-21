<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /** @return list<string> */
    public static function accepted(): array
    {
        return [self::Processing->value, self::Succeeded->value];
    }
}
