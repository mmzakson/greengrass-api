<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_reference' => $this->transaction_reference,
            'gateway_reference' => $this->gateway_reference,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'type' => $this->type,
            'status' => $this->status,
            'is_successful' => $this->is_successful,
            'payment_method' => $this->payment_method,
            'card_details' => [
                'type' => $this->card_type,
                'last4' => $this->card_last4,
                'bank' => $this->bank_name,
            ],
            'paid_at' => $this->paid_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}