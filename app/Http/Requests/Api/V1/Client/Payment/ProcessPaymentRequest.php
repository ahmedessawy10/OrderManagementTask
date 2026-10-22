<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(array_keys(config('payment.gateways')))],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => __('validation.custom.payment_method.required'),
            'payment_method.string' => __('validation.custom.payment_method.string'),
            'payment_method.in' => __('validation.custom.payment_method.in'),
        ];
    }
}
