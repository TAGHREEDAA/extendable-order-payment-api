<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case Pending = 0;
    case Successful = 1;
    case Failed = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Successful => 'Successful',
            self::Failed => 'Failed',
        };
    }
}
