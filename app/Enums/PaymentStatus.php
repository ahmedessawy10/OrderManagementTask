<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('messages.enum.paymentstatus.pending'),
            self::Successful => __('messages.enum.paymentstatus.successful'),
            self::Failed => __('messages.enum.paymentstatus.failed'),
        };
    }

    /**
     * pending -> successful|failed. successful and failed are final.
     */
    public function canTransitionTo(self $to): bool
    {
        return $this === self::Pending && $to !== self::Pending;
    }

    /**
     * Statuses that block a new payment for the same order.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending];
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
