<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'exists:users,phone', 'regex:/^(00965|\+965)[0-9]{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('validation.custom.phone.required'),
            'phone.exists' => __('validation.custom.phone.exists'),
            'phone.regex' => __('validation.custom.phone.regex'),
        ];
    }
}
