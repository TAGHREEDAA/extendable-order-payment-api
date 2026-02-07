<?php

namespace App\PaymentGateways;

use Illuminate\Support\Str;

class PayMobGateway implements PaymentGatewayInterface
{
    private string $apiKey;

    private string $apiSecret;

    public function __construct()
    {
        $this->apiKey = env('PAYMOB_API_KEY', '');

        $this->apiSecret = env('PAYMOB_API_SECRET', '');
    }

    public function processPayment(float $amount): array
    {
        $success = (bool) rand(0, 1);

        return [
            'success' => $success,
            'transaction_id' => $success ? $this->getName() . '-' . Str::uuid() : null,
            'message' => $success ? 'Payment processed' : 'Payment declined',
        ];
    }

    public function getName(): string
    {
        return 'PayMob';
    }
}
