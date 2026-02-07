<?php

namespace App\PaymentGateways;

interface PaymentGatewayInterface
{
    public function processPayment(float $amount): array;

    public function getName(): string;
}
