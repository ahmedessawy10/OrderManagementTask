<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'exists:users,phone', 'regex:/^(00965|\+965)[0-9]{8}$/'],
            'code' => ['required', 'string', 'size:4'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('validation.custom.phone.required'),
            'phone.exists' => __('validation.custom.phone.exists'),
            'phone.regex' => __('validation.custom.phone.regex'),
            'code.required' => __('validation.custom.code.required'),
            'code.size' => __('validation.custom.code.size'),
            'password.required' => __('validation.custom.password.required'),
            'password.confirmed' => __('validation.custom.password.confirmed'),
        ];
    }
}
