<?php

namespace App\Http\Controllers\API;


use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->listOrders($request->input('status'), $request->input('per_page'));

        return OrderResource::collection($orders)->response();
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(auth()->user(), $request->validated());

        return $order->toResource()->response()->setStatusCode(201);
    }

    public function show(Order $order)
    {
        $order->load('items');

        return $order->toResource();
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order = $this->orderService->updateOrder($order, $request->validated());

        return $order->toResource()->response();
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
