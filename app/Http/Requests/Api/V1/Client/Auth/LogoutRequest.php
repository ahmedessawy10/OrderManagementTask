<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LogoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['nullable', 'string'],
        ];
    }
}
