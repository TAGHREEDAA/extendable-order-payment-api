<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', new Enum(OrderStatus::class)],
            'items' => 'sometimes|array|min:1',
            'items.*.product_name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'status' => 'Invalid order status',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.*.product_name.required_with' => 'Product name is required',
            'items.*.quantity.required_with' => 'Quantity is required',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.unit_price.required_with' => 'Unit price is required',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater',
        ];
    }
}
