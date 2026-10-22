<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Order;

use App\Enums\OrderStatus;
use App\Http\Requests\Api\V1\Client\Order\Concerns\OrderValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrderRequest extends FormRequest
{
    use OrderValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['sometimes', 'required', 'array', 'min:1', 'max:100'],
            ...$this->itemRules(),
            // العميل يقدر يلغي بس، التأكيد بييجي من الدفع الناجح
            'status' => ['sometimes', 'required', Rule::in([OrderStatus::Cancelled->value])],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->orderMessages(),
            'status.required' => __('validation.custom.status.required'),
            'status.in' => __('validation.custom.status.in'),
        ];
    }
}
