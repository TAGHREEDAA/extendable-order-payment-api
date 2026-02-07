<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\PaymentService;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\Payment\ProcessPaymentRequest;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->listPayments(
            $request->input('order_id'),
            $request->input('per_page')
        );

        return PaymentResource::collection($payments)->response();
    }

    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->processPayment($request->validated());

        return $payment->toResource()->response()->setStatusCode(201);
    }
}
