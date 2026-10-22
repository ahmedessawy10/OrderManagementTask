<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->method,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'gateway_reference' => $this->gateway_reference,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
