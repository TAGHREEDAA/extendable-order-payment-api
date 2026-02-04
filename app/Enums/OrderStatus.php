<?php

namespace App\Enums;

enum OrderStatus: int
{
    case Pending = 0;
    case Confirmed = 1;
    case Cancelled = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }
}
