<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case CustomerReply = 'customer_reply';
    case OnHold = 'on_hold';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }

    /**
     * Statuses waiting on a staff response — drives the sidebar badge.
     *
     * @return list<self>
     */
    public static function awaitingReply(): array
    {
        return [self::Open, self::CustomerReply];
    }

    /** @return array<string, string> Value => label, for select options. */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Answered => 'Answered',
            self::CustomerReply => 'Customer Reply',
            self::OnHold => 'On Hold',
            self::InProgress => 'In Progress',
            self::Closed => 'Closed',
        };
    }
}
