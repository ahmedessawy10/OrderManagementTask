<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SendSms;
use App\Http\Traits\SMS;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendSmsListener implements ShouldQueue
{
    use SMS;

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(SendSms $event): void
    {
        if (config('services.sms.driver') === 'log') {
            Log::info('SMS (dry-run)', ['phone' => $event->phone, 'message' => $event->message]);

            return;
        }

        $result = self::sendSMS($event->phone, $event->message);

        if (! $result['success']) {
            Log::error('SMS failed', [
                'phone' => $event->phone,
                'error' => $result['error'] ?? $result['body'] ?? 'Unknown error',
                'status' => $result['status'] ?? null,
            ]);

            throw new \RuntimeException(__('messages.sms.sending_failed'));
        }

        Log::info('SMS sent successfully', ['phone' => $event->phone]);
    }

    public function failed(SendSms $event, \Throwable $e): void
    {
        Log::critical('SMS failed after all retries', [
            'phone' => $event->phone,
            'error' => $e->getMessage(),
        ]);
    }
}
