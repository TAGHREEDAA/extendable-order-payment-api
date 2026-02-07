<?php

namespace Database\Factories;

use App\Models\Order;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'gateway' => PaymentGateway::Stripe,
            'status' => PaymentStatus::Pending,
            'transaction_id' => fake()->uuid(),
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Successful,
        ]);
    }
}
