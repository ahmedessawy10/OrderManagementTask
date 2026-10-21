<?php

declare(strict_types=1);

namespace App\Enums;

enum SmsProvider: string
{
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::SMS => __('messages.enum.smsprovider.sms'),
            self::WHATSAPP => __('messages.enum.smsprovider.whatsapp'),
        };
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
