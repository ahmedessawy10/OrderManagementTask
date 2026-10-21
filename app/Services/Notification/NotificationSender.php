<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Enums\UserStatus;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Central dispatcher for all notification types
 *
 * This service handles sending notifications via multiple channels (database + FCM).
 *
 * Main Methods:
 * ============
 * 1. send() - Send to specific user(s) with type
 * 2. sendToTopic() - Broadcast to FCM topic (all subscribed users)
 * 3. sendToAllUsers() - Send to all active users in database
 *
 * Features:
 * - Bilingual support (English and Arabic)
 * - FCM push notifications (topic and device token)
 * - Database storage with translations
 * - User notification preferences (notifications_enabled flag)
 * - Bulk operations with chunk processing (500 per chunk)
 * - Comprehensive error handling and logging
 */
final class NotificationSender
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly FcmService $fcmService,
    ) {}

    /**
     * Send notification to user(s)
     *
     * @param  User|Collection|EloquentCollection  $users  Single user or collection of users
     * @param  NotificationType  $type  Notification type
     * @param  string  $titleEn  English title
     * @param  string  $titleAr  Arabic title
     * @param  string  $contentEn  English content
     * @param  string  $contentAr  Arabic content
     * @param  array  $data  Additional payload
     * @param  bool  $isTopic  If true, send as FCM topic broadcast
     * @return array{success: bool, notification_ids?: array, sent_count?: int, skipped_count?: int, warnings?: array}
     */
    public function send(
        User|Collection|EloquentCollection|null $users,
        NotificationType $type,
        string $titleEn,
        string $titleAr,
        string $contentEn,
        string $contentAr,
        array $data = [],
        bool $isTopic = false,
        ?string $topic = null,
    ): array {
        // Handle "All Users" / Broadcast scenario
        if ($users === null) {
            if ($isTopic) {
                return $this->sendToTopic(
                    topic: $topic ?? 'general',
                    titleEn: $titleEn,
                    titleAr: $titleAr,
                    contentEn: $contentEn,
                    contentAr: $contentAr,
                    data: $data,
                );
            }

            return $this->sendToAllUsers(
                titleEn: $titleEn,
                titleAr: $titleAr,
                contentEn: $contentEn,
                contentAr: $contentAr,
                data: $data
            );
        }

        // Normalize input to Collection
        if (! $users instanceof Collection && ! $users instanceof EloquentCollection) {
            $users = collect([$users]);
        } elseif ($users instanceof EloquentCollection) {
            $users = $users->toBase();
        }

        if ($users->isEmpty()) {
            return [
                'success' => false,
                'error' => 'no_users_provided',
            ];
        }

        $notificationIds = [];
        $skippedCount = 0;
        $sentCount = 0;
        $warnings = [];

        try {
            // Process users in chunks
            $users->chunk(self::CHUNK_SIZE)->each(function (Collection $chunk) use (
                $type,
                $titleEn,
                $titleAr,
                $contentEn,
                $contentAr,
                $data,
                $isTopic,
                &$notificationIds,
                &$skippedCount,
                &$sentCount,
                &$warnings
            ) {
                $now = now();
                $batchId = (string) Str::uuid();

                // === 1. Bulk Insert Notifications ===
                $notifiableType = get_class($chunk->first());
                $batchData = array_merge($data, ['_batch_id' => $batchId]);
                $notificationsData = $chunk->map(fn ($user) => [
                    'type' => $type->value,
                    'notifiable_type' => $notifiableType,
                    'notifiable_id' => $user->id,
                    'data' => json_encode($batchData),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                DB::table('notifications')->insert($notificationsData);

                // === 2. Get Inserted IDs (race-condition safe via batch_id) ===
                $insertedNotifications = Notification::where('notifiable_type', $notifiableType)
                    ->whereIn('notifiable_id', $chunk->pluck('id')->toArray())
                    ->where('type', $type->value)
                    ->whereJsonContains('data->_batch_id', $batchId)
                    ->get(['id', 'notifiable_id']);

                $notificationIds = array_merge($notificationIds, $insertedNotifications->pluck('id')->toArray());

                // === 3. Bulk Insert Translations ===
                $translationsData = [];
                foreach ($insertedNotifications as $notification) {
                    $translationsData[] = [
                        'notification_id' => $notification->id,
                        'locale' => 'en',
                        'title' => $titleEn,
                        'content' => $contentEn,
                    ];
                    $translationsData[] = [
                        'notification_id' => $notification->id,
                        'locale' => 'ar',
                        'title' => $titleAr,
                        'content' => $contentAr,
                    ];
                }

                DB::table('notification_translations')->upsert(
                    $translationsData,
                    ['notification_id', 'locale'],
                    ['title', 'content']
                );

                // === 4. Send FCM Notifications (batch, only if NOT topic) ===
                if (! $isTopic && $this->fcmService->isConfigured()) {
                    $fcmChunkData = $this->sanitizeData($data);
                    $eligibleUsers = $chunk->filter(fn ($user) => $user->notifications_enabled);
                    $skippedCount += ($chunk->count() - $eligibleUsers->count());

                    if ($eligibleUsers->isNotEmpty()) {
                        $userIds = $eligibleUsers->pluck('id')->toArray();
                        $userLocales = $eligibleUsers->pluck('preferred_language', 'id')
                            ->map(fn ($lang) => $lang ?? 'en')
                            ->toArray();

                        try {
                            $fcmResult = $this->fcmService->sendToUsers(
                                userIds: $userIds,
                                userLocales: $userLocales,
                                titleEn: $titleEn,
                                titleAr: $titleAr,
                                bodyEn: $contentEn,
                                bodyAr: $contentAr,
                                data: $fcmChunkData,
                                tokenableType: $notifiableType,
                            );
                            $sentCount += $fcmResult['success_count'];

                            if ($fcmResult['failure_count'] > 0) {
                                $warnings[] = "FCM: {$fcmResult['failure_count']} failures in chunk";
                            }
                        } catch (Throwable $e) {
                            Log::error('FCM batch send failed', ['error' => $e->getMessage()]);
                            $warnings[] = 'FCM batch failed: '.$e->getMessage();
                        }
                    }
                }
            });

            // === 5. Send FCM Topic Notification (Once) ===
            if ($isTopic && $this->fcmService->isConfigured()) {
                $fcmData = $this->sanitizeData($data);

                $this->sendFcmToTopic(
                    topic: 'general',
                    titleEn: $titleEn,
                    titleAr: $titleAr,
                    contentEn: $contentEn,
                    contentAr: $contentAr,
                    data: $fcmData,
                    warnings: $warnings
                );

                // Assuming all provided users are considered "sent" to
                $sentCount = $users->count();
            }

            Log::info('NotificationSender: Notifications sent', [
                'type' => $type->value,
                'user_count' => $users->count(),
                'notification_count' => count($notificationIds),
                'sent_count' => $sentCount,
                'skipped_count' => $skippedCount,
                'is_topic' => $isTopic,
            ]);

            $result = [
                'success' => true,
                'notification_ids' => $notificationIds,
                'sent_count' => $sentCount,
                'skipped_count' => $skippedCount,
            ];

            if (! empty($warnings)) {
                $result['warnings'] = $warnings;
            }

            return $result;

        } catch (Throwable $e) {
            Log::error('NotificationSender: Failed to send notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'unexpected_error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
            ];
        }
    }

    /**
     * Send notification to FCM topic (broadcast to all subscribed users)
     *
     * @param  string  $topic  FCM topic name (e.g., 'general', 'announcements')
     * @param  string  $titleEn  English title
     * @param  string  $titleAr  Arabic title
     * @param  string  $contentEn  English content
     * @param  string  $contentAr  Arabic content
     * @param  array  $data  Additional payload
     * @param  bool  $saveToDatabase  Save to database for all users
     * @return array{success: bool, fcm_result?: array, database_count?: int, warnings?: array}
     */
    public function sendToTopic(
        string $topic,
        string $titleEn,
        string $titleAr,
        string $contentEn,
        string $contentAr,
        array $data = [],
        bool $saveToDatabase = true
    ): array {
        $warnings = [];

        try {
            if (! $this->fcmService->isConfigured()) {
                return [
                    'success' => false,
                    'error' => 'fcm_not_configured',
                ];
            }

            $fcmData = $this->sanitizeData($data);
            $fcmData['type'] = NotificationType::General->value;
            $fcmData['title_en'] = $titleEn;
            $fcmData['title_ar'] = $titleAr;
            $fcmData['content_en'] = $contentEn;
            $fcmData['content_ar'] = $contentAr;

            // Send FCM to topic (using sanitized data)
            $this->sendFcmToTopic(
                topic: $topic,
                titleEn: $titleEn,
                titleAr: $titleAr,
                contentEn: $contentEn,
                contentAr: $contentAr,
                data: $fcmData,
                warnings: $warnings
            );

            $databaseCount = 0;

            // Save to database for all users (no FCM — already sent via topic above)
            if ($saveToDatabase) {
                $topicSent = 0;
                $topicSkipped = 0;
                $databaseCount = $this->saveAndSendToAllActiveUsers(
                    titleEn: $titleEn,
                    titleAr: $titleAr,
                    contentEn: $contentEn,
                    contentAr: $contentAr,
                    data: $data,
                    warnings: $warnings,
                    sentCount: $topicSent,
                    skippedCount: $topicSkipped,
                    sendFcm: false
                );
            }

            Log::info('NotificationSender: Topic notification sent', [
                'topic' => $topic,
                'database_count' => $databaseCount,
            ]);

            $result = [
                'success' => true,
                'database_count' => $databaseCount,
                'notification_count' => $databaseCount, // Alias for consistency
                'sent_count' => $databaseCount, // Rough estimate for topic broadcast
            ];

            if (! empty($warnings)) {
                $result['warnings'] = $warnings;
            }

            return $result;

        } catch (Throwable $e) {
            Log::error('NotificationSender: Failed to send topic notification', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'unexpected_error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
            ];
        }
    }

    /**
     * Send notification to all active users in database
     *
     * @param  string  $titleEn  English title
     * @param  string  $titleAr  Arabic title
     * @param  string  $contentEn  English content
     * @param  string  $contentAr  Arabic content
     * @param  array  $data  Additional payload
     * @return array{success: bool, notification_count?: int, warnings?: array}
     */
    public function sendToAllUsers(
        string $titleEn,
        string $titleAr,
        string $contentEn,
        string $contentAr,
        array $data = []
    ): array {
        $warnings = [];

        try {
            $data['type'] = NotificationType::General->value;
            $sentCount = 0;
            $skippedCount = 0;

            $notificationCount = $this->saveAndSendToAllActiveUsers(
                titleEn: $titleEn,
                titleAr: $titleAr,
                contentEn: $contentEn,
                contentAr: $contentAr,
                data: $data,
                warnings: $warnings,
                sentCount: $sentCount,
                skippedCount: $skippedCount
            );

            Log::info('NotificationSender: Sent to all users', [
                'notification_count' => $notificationCount,
                'sent_count' => $sentCount,
                'skipped_count' => $skippedCount,
            ]);

            $result = [
                'success' => true,
                'notification_count' => $notificationCount,
                'sent_count' => $sentCount,
                'skipped_count' => $skippedCount,
            ];

            if (! empty($warnings)) {
                $result['warnings'] = $warnings;
            }

            return $result;

        } catch (Throwable $e) {
            Log::error('NotificationSender: Failed to send to all users', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'unexpected_error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
            ];
        }
    }

    // ==================== Private Helper Methods ====================

    /**
     * Send FCM notification to individual user
     */
    private function sendFcmToUser(
        User $user,
        string $titleEn,
        string $titleAr,
        string $bodyEn,
        string $bodyAr,
        array $data,
        array &$warnings
    ): void {
        if (! $this->fcmService->isConfigured()) {
            return;
        }

        try {
            $locale = $user->preferred_language ?? 'en';
            $title = $locale === 'ar' ? $titleAr : $titleEn;
            $body = $locale === 'ar' ? $bodyAr : $bodyEn;

            $result = $this->fcmService->sendToUser(
                userId: $user->id,
                title: $title,
                body: $body,
                data: $data,
                priority: FcmService::PRIORITY_HIGH,
                tokenableType: get_class($user),
            );

            if ($result['success_count'] > 0) {
                Log::debug('FCM sent to user', ['user_id' => $user->id]);
            }
        } catch (Throwable $e) {
            Log::error('FCM failed for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $warnings[] = "FCM failed for user {$user->id}";
        }
    }

    /**
     * Send FCM notification to topic
     */
    private function sendFcmToTopic(
        string $topic,
        string $titleEn,
        string $titleAr,
        string $contentEn,
        string $contentAr,
        array $data,
        array &$warnings
    ): void {
        if (! $this->fcmService->isConfigured()) {
            $warnings[] = 'FCM not configured';

            return;
        }

        try {
            $result = $this->fcmService->sendToTopic(
                topic: $topic,
                title: $titleEn,
                body: $contentEn,
                data: $data,
                priority: FcmService::PRIORITY_HIGH
            );

            if (! ($result['success'] ?? false)) {
                $warnings[] = 'FCM topic notification failed: '.($result['error'] ?? 'Unknown error');
            }
        } catch (Throwable $e) {
            Log::error('FCM topic failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            $warnings[] = 'FCM topic failed: '.$e->getMessage();
        }
    }

    /**
     * Save notifications to DB and send FCM to all active users
     */
    private function saveAndSendToAllActiveUsers(
        string $titleEn,
        string $titleAr,
        string $contentEn,
        string $contentAr,
        array $data,
        array &$warnings,
        int &$sentCount,
        int &$skippedCount,
        bool $sendFcm = true
    ): int {
        $notificationCount = 0;
        $now = now();

        User::where('user_status', UserStatus::ACTIVE)
            ->whereNot('id', auth('api')->id())
            ->chunk(self::CHUNK_SIZE, function (EloquentCollection $users) use (
                $titleEn,
                $titleAr,
                $contentEn,
                $contentAr,
                $data,
                $now,
                $sendFcm,
                &$notificationCount,
                &$sentCount,
                &$skippedCount,
                &$warnings
            ) {
                try {
                    $batchId = (string) Str::uuid();

                    // === 1. Bulk insert notifications ===
                    $batchData = array_merge($data, ['_batch_id' => $batchId]);
                    $notificationsData = $users->map(fn ($user) => [
                        'type' => NotificationType::General->value,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $user->id,
                        'data' => json_encode($batchData),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->toArray();

                    DB::table('notifications')->insert($notificationsData);

                    // === 2. Get inserted IDs (race-condition safe via batch_id) ===
                    $insertedNotifications = Notification::where('notifiable_type', User::class)
                        ->whereIn('notifiable_id', $users->pluck('id')->toArray())
                        ->where('type', NotificationType::General->value)
                        ->whereJsonContains('data->_batch_id', $batchId)
                        ->get(['id']);

                    if ($insertedNotifications->isEmpty()) {
                        $warnings[] = 'No notifications inserted for chunk';

                        return;
                    }

                    // === 3. Bulk insert translations ===
                    $translationsData = [];
                    foreach ($insertedNotifications as $notification) {
                        $translationsData[] = [
                            'notification_id' => $notification->id,
                            'locale' => 'en',
                            'title' => $titleEn,
                            'content' => $contentEn,
                        ];
                        $translationsData[] = [
                            'notification_id' => $notification->id,
                            'locale' => 'ar',
                            'title' => $titleAr,
                            'content' => $contentAr,
                        ];
                    }

                    DB::table('notification_translations')->upsert(
                        $translationsData,
                        ['notification_id', 'locale'],
                        ['title', 'content']
                    );
                    $notificationCount += $insertedNotifications->count();

                    // === 4. Send FCM to all users in chunk (batch) ===
                    if ($sendFcm && $this->fcmService->isConfigured()) {
                        $fcmChunkData = $this->sanitizeData($data);
                        $eligibleUsers = $users->filter(fn ($user) => $user->notifications_enabled);
                        $skippedCount += ($users->count() - $eligibleUsers->count());

                        if ($eligibleUsers->isNotEmpty()) {
                            $userIds = $eligibleUsers->pluck('id')->toArray();
                            $userLocales = $eligibleUsers->pluck('preferred_language', 'id')
                                ->map(fn ($lang) => $lang ?? 'en')
                                ->toArray();

                            try {
                                $fcmResult = $this->fcmService->sendToUsers(
                                    userIds: $userIds,
                                    userLocales: $userLocales,
                                    titleEn: $titleEn,
                                    titleAr: $titleAr,
                                    bodyEn: $contentEn,
                                    bodyAr: $contentAr,
                                    data: $fcmChunkData,
                                );
                                $sentCount += $fcmResult['success_count'];

                                if ($fcmResult['failure_count'] > 0) {
                                    $warnings[] = "FCM: {$fcmResult['failure_count']} failures in chunk";
                                }
                            } catch (Throwable $e) {
                                Log::error('FCM batch send failed for all-users chunk', ['error' => $e->getMessage()]);
                                $warnings[] = 'FCM batch failed: '.$e->getMessage();
                            }
                        }
                    }

                } catch (Throwable $e) {
                    Log::error('Database chunk insertion failed', [
                        'error' => $e->getMessage(),
                        'user_count' => $users->count(),
                    ]);
                    $warnings[] = 'Database chunk failed: '.$e->getMessage();
                }
            });

        return $notificationCount;
    }

    /**
     * Sanitize data payload for FCM and database
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $sanitized[$key] = '';
            } else {
                $sanitized[$key] = (string) $value;
            }

            // Truncate long values
            if (strlen($sanitized[$key]) > 500) {
                $sanitized[$key] = substr($sanitized[$key], 0, 500);
            }
        }

        return $sanitized;
    }
}
