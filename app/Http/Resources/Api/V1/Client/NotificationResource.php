<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Client;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Notification
 */
final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $translation = $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->first();

        return [
            'id' => $this->id,
            'title' => $translation?->title ?? '',
            'body' => $translation?->content ?? '',
            'image' => $this->data['image_url'] ?? '',
            'type' => $this->type,
            'target_id' => $this->data['target_id'] ?? null,
            'is_read' => $this->read(),
            'date' => $this->created_at?->diffForHumans(),
        ];
    }
}
