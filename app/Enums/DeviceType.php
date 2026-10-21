<?php

declare(strict_types=1);

namespace App\Enums;

enum DeviceType: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case WEB = 'web';

    public function label(): string
    {
        return match ($this) {
            self::ANDROID => __('messages.enum.devicetype.android'),
            self::IOS => __('messages.enum.devicetype.ios'),
            self::WEB => __('messages.enum.devicetype.web'),
        };
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isMobile(): bool
    {
        return in_array($this, [self::ANDROID, self::IOS]);
    }
}
