<?php

namespace App\Services;

use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    public function listOrders($status = null, $perPage = 15): LengthAwarePaginator
    {
        return Order::with('items')
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

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

    public function updateOrder(Order $order, $data): Order
    {
        if (isset($data['status'])) {
            $order->update(['status' => $data['status']]);
        }

        if (isset($data['items'])) {
            $order->items()->delete();

            $total = 0;
            $items = [];
            foreach ($data['items'] as $item) {
                $total += $item['quantity'] * $item['unit_price'];

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

            $order->update(['total_amount' => $total]);
        }

        return $order->load('items');
    }

    public function deleteOrder(Order $order): bool
    {
        if ($order->payments()->exists()) {
            return false;
        }

        $order->delete();
        return true;
    }
}
