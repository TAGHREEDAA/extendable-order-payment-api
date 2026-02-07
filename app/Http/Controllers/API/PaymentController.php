<?php

namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\Payment\ProcessPaymentRequest;

class PaymentController extends Controller
{

    public function __construct(protected PaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->listPayments($request->input('order_id'), $request->input('per_page'));

        return PaymentResource::collection($payments)->response();
    }

    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->processPayment($request->validated());

        return $payment->toResource()->response()->setStatusCode(201);
    }
}
