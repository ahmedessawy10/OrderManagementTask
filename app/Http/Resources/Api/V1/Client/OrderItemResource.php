<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotalCents = (int) round((float) $this->price * 100) * $this->quantity;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->translatedName(),
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
        ];
    }
}
