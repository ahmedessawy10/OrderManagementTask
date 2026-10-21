<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use Illuminate\Foundation\Http\FormRequest;

final class DeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => 'nullable|string|max:100',
            'token' => 'required_without:device_token|string|max:500',
            'device_token' => 'required_without:token|string|max:500',
        ];
    }
}
