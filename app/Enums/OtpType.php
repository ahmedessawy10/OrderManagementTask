<?php

declare(strict_types=1);

namespace App\Enums;

enum OtpType: string
{
    case REGISTRATION = 'registration';
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';
    case CHANGE_PHONE = 'change_phone';
    case COMPLETE_REGISTRATION = 'complete_registration';

    public function label(): string
    {
        return match ($this) {
            self::REGISTRATION => __('messages.enum.otptype.registration'),
            self::LOGIN => __('messages.enum.otptype.login'),
            self::PASSWORD_RESET => __('messages.enum.otptype.password_reset'),
            self::CHANGE_PHONE => __('messages.enum.otptype.change_phone'),
            self::COMPLETE_REGISTRATION => __('messages.enum.otptype.complete_registration'),
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
