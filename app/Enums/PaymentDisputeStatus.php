<?php

namespace App\Enums;

enum PaymentDisputeStatus: string
{
    case WarningNeedsResponse = 'warning_needs_response';
    case WarningUnderReview = 'warning_under_review';
    case WarningClosed = 'warning_closed';
    case NeedsResponse = 'needs_response';
    case UnderReview = 'under_review';
    case Won = 'won';
    case Lost = 'lost';
    case Prevented = 'prevented';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [
            self::WarningNeedsResponse->value,
            self::WarningUnderReview->value,
            self::NeedsResponse->value,
            self::UnderReview->value,
        ];
    }

    public function isFormalOpen(): bool
    {
        return in_array($this, [self::NeedsResponse, self::UnderReview], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::WarningClosed, self::Won, self::Lost, self::Prevented], true);
    }
}
