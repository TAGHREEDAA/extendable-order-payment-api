<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer $token"];
    }

    public function test_unauthenticated_user_cannot_access_payments(): void
    {
        $this->getJson('/api/payments')->assertStatus(401);

        $this->postJson('/api/payments')->assertStatus(401);
    }

    public function test_unauthorized_access(): void
    {
        $firstUser = User::factory()->create();

        $secondUser = User::factory()->create();

        $order = Order::factory()->confirmed()->create(['user_id' => $secondUser->id]);

        $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => PaymentGateway::Stripe->value,
            'amount' => $order->total_amount,
        ], $this->authHeaders($firstUser))->assertStatus(422);
    }

    public function test_process_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->confirmed()->create(['user_id' => $user->id, 'total_amount' => 250.00]);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => PaymentGateway::Stripe->value,
            'amount' => 250.00,
        ], $this->authHeaders($user));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'amount',
                    'gateway',
                    'gateway_label',
                    'status',
                    'status_label',
                    'transaction_id',
                    'gateway_response',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 250.00,
            'gateway' => PaymentGateway::Stripe->value,
        ]);
    }

    public function test_cannot_pay_with_invalid_data(): void
    {
        $user = User::factory()->create();

        // missing fields
        $this->postJson('/api/payments', [], $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order_id', 'gateway', 'amount']);

        // non-confirmed order
        $pendingOrder = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 100.00]);

        $this->postJson('/api/payments', [
            'order_id' => $pendingOrder->id,
            'gateway' => PaymentGateway::Stripe->value,
            'amount' => 100.00,
        ], $this->authHeaders($user))->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);

        // wrong amount
        $confirmedOrder = Order::factory()->confirmed()->create(['user_id' => $user->id, 'total_amount' => 200.00]);

        $this->postJson('/api/payments', [
            'order_id' => $confirmedOrder->id,
            'gateway' => PaymentGateway::Stripe->value,
            'amount' => 999.00,
        ], $this->authHeaders($user))->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_list_payments(): void
    {
        $user = User::factory()->create();

        $order1 = Order::factory()->confirmed()->create(['user_id' => $user->id]);

        $order2 = Order::factory()->confirmed()->create(['user_id' => $user->id]);

        Payment::factory()->count(2)->create(['order_id' => $order1->id]);

        Payment::factory()->count(3)->create(['order_id' => $order2->id]);

        $this->getJson('/api/payments', $this->authHeaders($user))
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');

        $this->getJson('/api/payments?order_id=' . $order1->id, $this->authHeaders($user))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_cannot_delete_order_with_payments(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->confirmed()->create(['user_id' => $user->id]);

        Payment::factory()->create(['order_id' => $order->id]);

        $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders($user))
            ->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete the order because it has associated payments']);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
