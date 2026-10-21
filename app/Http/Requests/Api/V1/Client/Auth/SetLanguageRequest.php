<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class SetLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'in:en,ar'],
        ];
    }

    public function messages(): array
    {
        return [
            'language.required' => __('validation.custom.language.required'),
            'language.in' => __('validation.custom.language.in'),
        ];
    }
}
