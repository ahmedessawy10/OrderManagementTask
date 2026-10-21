<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use App\Enums\OtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'regex:/^(00965|\+965)[0-9]{8}$/'],
            'code' => ['required', 'string', 'size:4'],
            'type' => ['required', 'string', Rule::enum(OtpType::class)],
            'user_id' => [
                Rule::when(
                    in_array($this->input('type'), [OtpType::CHANGE_PHONE->value]),
                    ['required', 'integer', 'exists:users,id'],
                    ['nullable', 'integer', 'exists:users,id']
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('validation.custom.phone.required'),
            'phone.regex' => __('validation.custom.phone.regex'),
            'code.required' => __('validation.custom.code.required'),
            'code.size' => __('validation.custom.code.size'),
            'type.required' => __('validation.custom.type.required'),
            'type.enum' => __('validation.custom.type.enum'),
            'user_id.required' => __('validation.custom.user_id.required'),
            'user_id.integer' => __('validation.custom.user_id.integer'),
            'user_id.exists' => __('validation.custom.user_id.exists'),
        ];
    }
}
