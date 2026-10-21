<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\DeviceToken storeDeviceToken(array $data)
 * @method static mixed getNotifications(\App\Models\User $user, int $perPage = 15)
 * @method static int getUnreadCount(\App\Models\User $user)
 * @method static \App\Models\Notification|null markAsRead(\App\Models\User $user, string|int $notificationId)
 * @method static int markAllAsRead(\App\Models\User $user)
 * @method static bool deleteNotification(\App\Models\User $user, string|int $notificationId)
 * @method static bool removeToken(\App\Models\User $user)
 * @method static \App\Models\User toggleNotification(bool $active)
 *
 * @see NotificationService
 */
final class NotificationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationService::class;
    }
}
