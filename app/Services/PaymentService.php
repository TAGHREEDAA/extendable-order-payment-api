<?php

namespace App\Services;

use App\Models\Payment;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use App\PaymentGateways\PaymentGatewayFactory;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function __construct(private PaymentGatewayFactory $gatewayFactory) {}

    public function listPayments($orderId = null, $perPage = 15): LengthAwarePaginator
    {
        return Payment::with('order')
            ->when($orderId, fn ($query) => $query->where('order_id', $orderId))
            ->latest()
            ->paginate($perPage);
    }

    public function processPayment(array $data): Payment
    {
        $gateway = PaymentGateway::from($data['gateway']);

        $gatewayHandler = $this->gatewayFactory->make($gateway);

        try {

            $response = $gatewayHandler->processPayment($data['amount']);

        } catch (\Exception $e) {

            $response = [
                'success' => false,
                'transaction_id' => null,
                'message' => $e->getMessage(),
            ];
        }

        return Payment::create([
            'order_id' => $data['order_id'],
            'amount' => $data['amount'],
            'gateway' => $gateway,
            'status' => $response['success'] ? PaymentStatus::Successful : PaymentStatus::Failed,
            'transaction_id' => $response['transaction_id'],
            'gateway_response' => $response,
        ]);
    }
}
