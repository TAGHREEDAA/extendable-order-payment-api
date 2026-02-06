<?php

namespace App\Services;

use App\Models\Order;
use App\Enums\OrderStatus;

class OrderService
{
    public function createOrder($user, $data)
    {
        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        $order = Order::create([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'total_amount' => $total,
        ]);

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'order_id' => $order->id,
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $order->items()->insert($items);

        return $order->load('items');
    }

}
