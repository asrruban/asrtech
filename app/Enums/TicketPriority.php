<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(
            static fn (self $priority): string => $priority->value,
            self::cases(),
        );
    }

    /** @return array<string, string> Value => label, for select options. */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $priority) {
            $options[$priority->value] = $priority->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }
}
