<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\Paginatable;
use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class NotificationService
{
    use Paginatable;

    public function storeDeviceToken(array $data): DeviceToken
    {
        $user = auth('api')->user();
        $device = request()->header('X-Device', 'web');

        $deviceToken = DeviceToken::updateOrCreate(
            [
                'tokenable_id' => $user->id,
                'tokenable_type' => $user->getMorphClass(),
                'device_id' => $data['device_id'] ?? null,
            ],
            [
                'device_token' => $data['token'] ?? $data['device_token'],
                'device' => $device,
            ]
        );

        Log::info('Device token registered', [
            'user_id' => $user->id,
            'device' => $device,
            'device_id' => $data['device_id'] ?? null,
        ]);

        return $deviceToken;
    }

    public function getNotifications(User $user, int $perPage = 15)
    {
        return $user->appNotifications()
            ->with('translations')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getUnreadCount(User $user): int
    {
        return $user->appNotifications()
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(User $user, string|int $notificationId): ?Notification
    {
        $notification = $user->appNotifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
        }

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return $user->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteNotification(User $user, string|int $notificationId): bool
    {
        return $user->appNotifications()->where('id', $notificationId)->delete() > 0;
    }

    public function removeToken(User $user): bool
    {
        return $user->deviceTokens()->delete() > 0;
    }

    public function toggleNotification(bool $active): User
    {
        $user = auth('api')->user();
        $user->update(['notifications_enabled' => $active]);

        return $user->fresh();
    }
}
