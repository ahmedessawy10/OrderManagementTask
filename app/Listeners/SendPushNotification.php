<?php

declare(strict_types=1);

namespace App\Listeners;

use App\DataTransferObjects\NotificationData;
use App\Events\SendNotification;
use App\Services\Notification\NotificationSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Listener for SendNotification event
 *
 * Handles sending push notifications to users via NotificationSender service
 * Uses unified send() method that automatically handles single/bulk processing
 * Implements ShouldQueue for async processing with retry mechanism
 *
 * Features:
 * - Unified processing for single and multiple users
 * - Exponential backoff retry strategy
 * - Performance metrics collection
 * - Graceful error handling
 * - Cache-based deduplication prevents duplicate notifications
 */
class SendPushNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Uses exponential backoff: 10s, 20s, 40s
     */
    public int $backoff = 10;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Determine the queue connection.
     * Uses 'sync' in local environment for immediate execution.
     */
    public function viaConnection(): string
    {
        return app()->environment('local') ? 'sync' : config('queue.default');
    }

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly NotificationSender $notificationSender
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SendNotification $event): void
    {
        $startTime = microtime(true);
        $notificationData = $event->notificationData;

        Log::info('SendPushNotification: Processing notification event', [
            'type' => $notificationData->type->value,
            'is_all_users' => $notificationData->isAllUsers,
            'is_topic' => $notificationData->isTopic,
            'attempt' => $this->attempts(),
        ]);

        try {
            $result = $this->processNotification($notificationData);

            if (! ($result['success'] ?? false)) {
                if (($result['error'] ?? '') === 'no_users_provided') {
                    Log::warning('SendPushNotification: No users provided for notification', [
                        'type' => $notificationData->type->value,
                    ]);

                    return;
                }

                throw new \Exception('Notification sending failed: '.($result['error'] ?? 'Unknown error'));
            }

            // Log success with details
            $this->logSuccess($notificationData, $result, $startTime);

            // Log warnings if any
            if (! empty($result['warnings'])) {
                Log::warning('SendPushNotification: Warnings occurred', [
                    'type' => $notificationData->type->value,
                    'warnings' => $result['warnings'],
                ]);
            }

        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            Log::error('SendPushNotification: Critical error during processing', [
                'type' => $notificationData->type->value,
                'is_all_users' => $notificationData->isAllUsers,
                'duration_seconds' => round($duration, 2),
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Process notification based on type (specific users, all users, or topic)
     */
    private function processNotification(NotificationData $notificationData): array
    {
        return $this->notificationSender->send(
            users: $notificationData->isAllUsers ? null : collect($notificationData->getUsers()),
            type: $notificationData->type,
            titleEn: $notificationData->titleEn,
            titleAr: $notificationData->titleAr,
            contentEn: $notificationData->bodyEn,
            contentAr: $notificationData->bodyAr,
            data: $notificationData->payload,
            isTopic: $notificationData->isTopic,
            topic: $notificationData->topic
        );
    }

    /**
     * Log successful notification sending
     */
    private function logSuccess(NotificationData $notificationData, array $result, float $startTime): void
    {
        $duration = microtime(true) - $startTime;

        $logData = [
            'type' => $notificationData->type->value,
            'is_all_users' => $notificationData->isAllUsers,
            'sent_count' => $result['sent_count'] ?? 0,
            'skipped_count' => $result['skipped_count'] ?? 0,
        ];

        if ($notificationData->isAllUsers) {
            $logData['notification_count'] = $result['notification_count'] ?? 0;
        } else {
            $logData['user_count'] = count($notificationData->getUsers());
            $logData['notification_ids_count'] = count($result['notification_ids'] ?? []);
        }

        Log::info('SendPushNotification: Notifications sent successfully', $logData);

        // Log performance metrics
        $totalUsers = $notificationData->isAllUsers
            ? ($result['notification_count'] ?? 0)
            : count($notificationData->getUsers());

        $this->logPerformanceMetrics($notificationData, $totalUsers, $duration);
    }

    /**
     * Log performance metrics for monitoring and optimization
     */
    private function logPerformanceMetrics(NotificationData $notificationData, int $totalUsers, float $duration): void
    {
        $throughput = $totalUsers > 0 ? round($totalUsers / max($duration, 0.001), 2) : 0;

        Log::info('SendPushNotification: Performance metrics', [
            'type' => $notificationData->type->value,
            'total_users' => $totalUsers,
            'duration_seconds' => round($duration, 3),
            'throughput_users_per_second' => $throughput,
        ]);

        // Cache metrics for monitoring dashboard (optional)
        $metricsKey = 'notifications:metrics:'.$notificationData->type->value;
        $metrics = Cache::get($metricsKey, [
            'total_sent' => 0,
            'total_duration' => 0,
            'count' => 0,
        ]);

        $metrics['total_sent'] += $totalUsers;
        $metrics['total_duration'] += $duration;
        $metrics['count']++;

        Cache::put($metricsKey, $metrics, now()->addDay());
    }

    /**
     * Handle a job failure after all retries exhausted
     */
    public function failed(SendNotification $event, \Throwable $exception): void
    {
        $users = $event->notificationData->getUsers();
        $recipientIds = $users ? $users->pluck('id')->toArray() : [];

        Log::critical('SendPushNotification: Job permanently failed after all retries', [
            'type' => $event->notificationData->type->value,
            'is_all_users' => $event->notificationData->isAllUsers,
            'total_users' => $users ? $users->count() : 0,
            'recipient_ids' => count($recipientIds) > 20 ? array_slice($recipientIds, 0, 20) : $recipientIds,
            'recipient_count' => count($recipientIds),
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Determine the delay (in seconds) before retrying the job
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        // Exponential backoff: 10s, 30s, 90s
        return [10, 30, 90];
    }
}
