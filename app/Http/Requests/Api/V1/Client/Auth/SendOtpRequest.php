<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use App\Enums\OtpType;
use App\Enums\SmsProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'regex:/^(00965|\+965)[0-9]{8}$/'],
            'type' => ['required', 'string', Rule::enum(OtpType::class)],
            'provider' => ['nullable', 'string', Rule::enum(SmsProvider::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('validation.custom.phone.required'),
            'phone.regex' => __('validation.custom.phone.regex'),
            'type.required' => __('validation.custom.type.required'),
            'type.enum' => __('validation.custom.type.enum'),
        ];
    }
}
