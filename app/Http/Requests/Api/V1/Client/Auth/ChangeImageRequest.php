<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.required' => __('validation.custom.profile_image.required'),
            'profile_image.image' => __('validation.custom.profile_image.image'),
            'profile_image.mimes' => __('validation.custom.profile_image.mimes'),
            'profile_image.max' => __('validation.custom.profile_image.max'),
        ];
    }
}
