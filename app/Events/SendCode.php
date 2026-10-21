<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\OtpType;
use App\Enums\SmsProvider;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SendCode
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $phone,
        public readonly OtpType $type = OtpType::LOGIN,
        public readonly ?SmsProvider $provider = SmsProvider::SMS,
    ) {}
}
