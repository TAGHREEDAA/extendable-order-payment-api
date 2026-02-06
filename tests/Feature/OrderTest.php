<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer $token"];
    }

    public function test_unauthenticated_user_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401)->assertJson(['error' => 'Unauthenticated']);
    }

    public function test_unauthorized_access(): void
    {
        $firstUser = User::factory()->create();

        $secondUser = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $secondUser->id]);

        $this->getJson("/api/orders/{$order->id}", $this->authHeaders($firstUser))->assertStatus(404);

        $this->putJson("/api/orders/{$order->id}", ['status' => OrderStatus::Confirmed->value], $this->authHeaders($firstUser))->assertStatus(404);

        $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders($firstUser))->assertStatus(404);
    }

    public function test_create_order(): void
    {
        $user = User::factory()->create();

        $orderData = [
            'items' => [
                [
                    'product_name' => 'Product 1',
                    'quantity' => 2,
                    'unit_price' => 100.50,
                ],
                [
                    'product_name' => 'Product 2',
                    'quantity' => 1,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $orderData, $this->authHeaders($user));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'status_label',
                    'total_amount',
                    'items',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'status' => OrderStatus::Pending->value,
                    'total_amount' => '251.00',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => OrderStatus::Pending->value,
            'total_amount' => 251.00,
        ]);

        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_user_cannot_create_order_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [], $this->authHeaders($user));

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);

        $response = $this->postJson('/api/orders', [
            'items' => [['product_name' => '', 'quantity' => 0, 'unit_price' => -10]]
        ], $this->authHeaders($user));

        $response->assertStatus(422);
    }

    public function test_list_orders(): void
    {
        $firstUser = User::factory()->create();

        $secondUser = User::factory()->create();

        Order::factory()->count(3)->create(['user_id' => $firstUser->id]);

        Order::factory()->count(2)->create(['user_id' => $secondUser->id]);

        $this->getJson('/api/orders', $this->authHeaders($firstUser))
            ->assertStatus(200)->assertJsonCount(3, 'data');

        Order::factory()->count(2)->create(['user_id' => $firstUser->id, 'status' => OrderStatus::Pending]);

        Order::factory()->create(['user_id' => $firstUser->id, 'status' => OrderStatus::Confirmed]);

        $this->getJson('/api/orders?status=' . OrderStatus::Pending->value, $this->authHeaders($firstUser))
            ->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_view_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $order->id,
                ],
            ]);
    }

    public function test_update_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);

        $this->putJson("/api/orders/{$order->id}", ['status' => OrderStatus::Confirmed->value], $this->authHeaders($user))
            ->assertStatus(200)
            ->assertJson(['data' => ['status' => OrderStatus::Confirmed->value]]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Confirmed->value]);

        $order2 = Order::factory()->hasItems(2)->create(['user_id' => $user->id]);

        $this->putJson("/api/orders/{$order2->id}", ['items' => [['product_name' => 'New Product', 'quantity' => 3, 'unit_price' => 75.00]]], $this->authHeaders($user))
            ->assertStatus(200);

        $this->assertDatabaseCount('order_items', 1);

        $this->assertDatabaseHas('order_items', ['order_id' => $order2->id, 'product_name' => 'New Product']);

        $this->assertDatabaseHas('orders', ['id' => $order2->id, 'total_amount' => 225.00]);
    }

    public function test_user_cannot_update_order_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'status' => 999,
        ], $this->authHeaders($user));

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_delete_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders($user));

        $response->assertStatus(200)->assertJson(['message' => 'Order deleted successfully']);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}
