<?php

declare(strict_types=1);

namespace App\Events;

use App\DataTransferObjects\NotificationData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * General notification event
 *
 * This event can be fired to send notifications using NotificationData DTO
 * with support for bilingual content (English/Arabic)
 *
 * Supports both single and multi-user notifications:
 * - Single user: Pass a User instance to NotificationData
 * - Multi user: Pass an array of User instances to NotificationData
 */
class SendNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  NotificationData  $notificationData  Notification data transfer object (supports single or multiple recipients)
     * @param  string  $titleEn  English title
     * @param  string  $titleAr  Arabic title
     * @param  string  $bodyEn  English body/content
     * @param  string  $bodyAr  Arabic body/content
     */
    public function __construct(
        public readonly NotificationData $notificationData
    ) {}
}
