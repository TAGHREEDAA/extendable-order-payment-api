<?php

namespace App\Enums;

enum PaymentGateway: int
{
    case Paypal = 0;
    case Stripe = 1;
    case PayMob = 2;

    public function label(): string
    {
        return match ($this) {
            self::Paypal => 'PayPal',
            self::Stripe => 'Stripe',
            self::PayMob => 'PayMob',
        };
    }
}