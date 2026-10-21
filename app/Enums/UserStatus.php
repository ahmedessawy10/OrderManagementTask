<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: int
{
    case ACTIVE = 1;
    case BLOCKED = 2;
    case INCOMPLETE = 3;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('messages.enum.userstatus.active'),
            self::BLOCKED => __('messages.enum.userstatus.blocked'),
            self::INCOMPLETE => __('messages.enum.userstatus.incomplete'),
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
