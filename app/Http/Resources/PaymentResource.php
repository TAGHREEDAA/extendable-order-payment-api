<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'gateway' => $this->gateway->value,
            'gateway_label' => $this->gateway->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'transaction_id' => $this->transaction_id,
            'gateway_response' => $this->gateway_response,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
