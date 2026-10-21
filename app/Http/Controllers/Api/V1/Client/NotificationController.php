<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Client;

use App\Facades\NotificationFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\DeviceTokenRequest;
use App\Http\Resources\Api\V1\Client\NotificationResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Store device token for push notifications.
     */
    public function storeDeviceToken(DeviceTokenRequest $request): JsonResponse
    {
        NotificationFacade::storeDeviceToken($request->validated());

        return $this->success(__('messages.notification.device_token_stored') ?? 'Device token stored successfully');
    }

    /**
     * Remove device token.
     */
    public function removeDeviceToken(): JsonResponse
    {
        NotificationFacade::removeToken(auth('api')->user());

        return $this->success(__('messages.notification.device_token_removed') ?? 'Device token removed successfully');
    }

    /**
     * Get user notifications list.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $perPage = $request->integer('per_page', 15);
        $notifications = NotificationFacade::getNotifications($user, $perPage);

        return $this->success(
            __('messages.notification.retrieved') ?? 'Notifications retrieved successfully',
            NotificationResource::collection($notifications->getCollection()),
            200,
            $this->getPagination($notifications)
        );
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount(): JsonResponse
    {
        $count = NotificationFacade::getUnreadCount(auth('api')->user());

        return $this->success(
            __('messages.notification.unread_count_retrieved') ?? 'Unread count retrieved successfully',
            ['unread_count' => $count]
        );
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string|int $id): JsonResponse
    {
        $notification = NotificationFacade::markAsRead(auth('api')->user(), $id);

        if (! $notification) {
            return $this->fail(__('messages.notification.not_found') ?? 'Notification not found', 404);
        }

        return $this->success(__('messages.notification.marked_read') ?? 'Notification marked as read');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = NotificationFacade::markAllAsRead(auth('api')->user());

        return $this->success(
            __('messages.notification.all_marked_read') ?? 'All notifications marked as read',
            ['marked_count' => $count]
        );
    }

    /**
     * Delete a notification.
     */
    public function destroy(string|int $id): JsonResponse
    {
        $deleted = NotificationFacade::deleteNotification(auth('api')->user(), $id);

        if (! $deleted) {
            return $this->fail(__('messages.notification.not_found') ?? 'Notification not found', 404);
        }

        return $this->success(__('messages.notification.deleted') ?? 'Notification deleted successfully');
    }

    /**
     * Toggle notifications on/off.
     */
    public function toggleNotification(Request $request): JsonResponse
    {
        $request->validate(['active' => 'required|boolean']);

        $user = NotificationFacade::toggleNotification((bool) $request->input('active'));

        return $this->success(
            __('messages.notification.status_updated') ?? 'Notification setting updated successfully',
            ['notifications_enabled' => $user->notifications_enabled]
        );
    }
}
