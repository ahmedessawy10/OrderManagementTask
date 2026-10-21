<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OtpType;
use App\Enums\SmsProvider;
use App\Events\SendCode;
use App\Http\Traits\SMS;
use App\Models\Otp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendCodeListener implements ShouldQueue
{
    use SMS;

    public int $tries = 3;

    public int $backoff = 10;

    public ?string $app_key = null;

    public function __construct()
    {
        $this->app_key = (string) (config('services.sms.app_key') ?? '');
    }

    public function handle(SendCode $event): void
    {
        $provider = $event->provider ?? SmsProvider::SMS;

        // env() returns null once config is cached (production), so the
        // environment must come from the container, not the .env file.
        $isProduction = app()->isProduction();
        if ($isProduction) {
            $code = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        } else {
            $code = '1234';
        }

        Otp::create([
            'phone' => $event->phone,
            'code' => $code,
            'type' => $event->type,
            'provider' => $provider,
            'expires_at' => now()->addMinutes(10),
        ]);

        $message = $this->generateMessage($code, $provider, $event->type);

        if (! $isProduction || config('services.sms.driver') === 'log') {
            Log::info('[otp] skipped (non-production or log driver)', [
                'phone' => $event->phone,
                'type' => $event->type->value,
                'provider' => $provider->value,
                'code' => $code,
            ]);

            return;
        }

        $result = $this->send($event->phone, $message, $provider);

        if (! $result['success']) {
            Log::error('[otp] send failed', [
                'phone' => $event->phone,
                'type' => $event->type->value,
                'provider' => $provider->value,
                'error' => $result['error'] ?? $result['body'] ?? 'Unknown error',
                'status' => $result['status'] ?? null,
            ]);

            throw new \RuntimeException(__('messages.sms.sending_failed'));
        }

        Log::info('[otp] sent', [
            'phone' => $event->phone,
            'type' => $event->type->value,
            'provider' => $provider->value,
        ]);
    }

    public function failed(SendCode $event, \Throwable $e): void
    {
        $provider = $event->provider ?? SmsProvider::SMS;

        Log::critical('[otp] failed after all retries', [
            'phone' => $event->phone,
            'type' => $event->type->value,
            'provider' => $provider->value,
            'error' => $e->getMessage(),
        ]);
    }

    private function generateMessage(string $code, SmsProvider $provider, OtpType $type): string
    {
        return match ($provider) {
            SmsProvider::SMS => __('messages.otp.message', ['code' => $code, 'app_key' => $this->app_key ?? '']),
            default => __('messages.otp.message', ['code' => $code, 'app_key' => $this->app_key ?? '']),
        };
    }

    private function send(string $phone, string $message, SmsProvider $provider): array
    {
        return match ($provider) {
            SmsProvider::SMS => $this->sendSMS($phone, $message),
            default => $this->sendSMS($phone, $message),
        };
    }
}
