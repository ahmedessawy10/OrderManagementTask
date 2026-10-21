<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Client;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_verified' => $this->phone_verified_at !== null,
            'user_status' => $this->user_status instanceof UserStatus ? $this->user_status->value : $this->user_status,
            'user_status_label' => $this->user_status instanceof UserStatus ? $this->user_status->label() : null,
            'preferred_language' => $this->preferred_language,
            'profile_image' => method_exists($this->resource, 'getFirstMediaUrl') ? $this->getFirstMediaUrl('profile_image') : null,
        ];
    }
}
