<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Order;

use App\Http\Requests\Api\V1\Client\Order\Concerns\OrderValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    use OrderValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            ...$this->itemRules(),
        ];
    }

    public function messages(): array
    {
        return $this->orderMessages();
    }
}
