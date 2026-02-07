<?php

namespace App\PaymentGateways;

use App\Enums\PaymentGateway;

class PaymentGatewayFactory
{
    public function make(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return match ($gateway) {
            PaymentGateway::Paypal => app(PaypalGateway::class),
            PaymentGateway::Stripe => app(StripeGateway::class),
            PaymentGateway::PayMob => app(PayMobGateway::class),
        };
    }
}
