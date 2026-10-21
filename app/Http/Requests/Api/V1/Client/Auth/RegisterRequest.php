<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => ['required', 'string', 'max:20', 'regex:/^(00965|\+965)[0-9]{8}$/', Rule::unique('users', 'phone')->whereNull('deleted_at')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.custom.name.required'),
            'email.email' => __('validation.custom.email.email'),
            'email.unique' => __('validation.custom.email.unique'),
            'phone.required' => __('validation.custom.phone.required'),
            'phone.regex' => __('validation.custom.phone.regex'),
            'phone.unique' => __('validation.custom.phone.unique'),
            'password.required' => __('validation.custom.password.required'),
            'password.confirmed' => __('validation.custom.password.confirmed'),
            'password.min' => __('validation.custom.password.min'),
        ];
    }
}
