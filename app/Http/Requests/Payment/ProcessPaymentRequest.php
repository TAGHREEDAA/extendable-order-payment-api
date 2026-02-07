<?php

namespace App\Http\Requests\Payment;

use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'order_id' => ['required', 'uuid', Rule::exists('orders', 'id')->where('user_id', auth()->id())],
            'gateway' => ['required', new Enum(PaymentGateway::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            if ($validator->errors()->any()) {
                return;
            }

            $order = Order::find($this->order_id);

            if (!$order) {
                return;
            }

            if ($order->status !== OrderStatus::Confirmed) {
                $validator->errors()->add('order_id', 'Payments can only be processed for confirmed orders');
            }

            if ((float) $order->total_amount !== (float) $this->amount) {
                $validator->errors()->add('amount', 'Amount must match the order total of ' . $order->total_amount);
            }
        });
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order ID is required',
            'order_id.exists' => 'Order not found',
            'gateway.required' => 'Payment gateway is required',
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Amount must be greater than 0',
        ];
    }
}
