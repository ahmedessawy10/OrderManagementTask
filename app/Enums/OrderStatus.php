<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('messages.enum.orderstatus.pending'),
            self::Confirmed => __('messages.enum.orderstatus.confirmed'),
            self::Cancelled => __('messages.enum.orderstatus.cancelled'),
        };
    }

    /**
     * pending -> confirmed|cancelled, confirmed -> cancelled, cancelled is final.
     */
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Cancelled],
            self::Cancelled => [],
        }, true);
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
