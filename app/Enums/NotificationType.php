<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case OrderPlaced = 'order_placed';
    case OrderConfirmed = 'order_confirmed';
    case OrderPreparing = 'order_preparing';
    case OrderReady = 'order_ready';
    case OrderOutFOrDelivery = 'order_out_for_delivery';
    case OrderDelivered = 'order_delivered';
    case OrderCancelled = 'order_cancelled';
    case OrderEdited = 'order_edited';
    case LoyaltyPointsEarned = 'loyalty_points_earned';
    case LoyaltyPointsExpiring = 'loyalty_points_expiring';
    case ReviewRequest = 'review_request';
    case DriverAssigned = 'driver_assigned';
    case AddressUpdateRequest = 'address_update_request';
    case BranchAvailable = 'branch_available';
    case General = 'general';

    public function title(): string
    {
        return match ($this) {
            self::OrderPlaced => __('messages.enum.notificationtype.order_placed'),
            self::OrderConfirmed => __('messages.enum.notificationtype.order_confirmed'),
            self::OrderPreparing => __('messages.enum.notificationtype.order_preparing'),
            self::OrderReady => __('messages.enum.notificationtype.order_ready'),
            self::OrderOutFOrDelivery => __('messages.enum.notificationtype.order_out_for_delivery'),
            self::OrderDelivered => __('messages.enum.notificationtype.order_delivered'),
            self::OrderCancelled => __('messages.enum.notificationtype.order_cancelled'),
            self::OrderEdited => __('messages.enum.notificationtype.order_edited'),
            self::LoyaltyPointsEarned => __('messages.enum.notificationtype.loyalty_points_earned'),
            self::LoyaltyPointsExpiring => __('messages.enum.notificationtype.loyalty_points_expiring'),
            self::ReviewRequest => __('messages.enum.notificationtype.review_request'),
            self::DriverAssigned => __('messages.enum.notificationtype.driver_assigned'),
            self::AddressUpdateRequest => __('messages.enum.notificationtype.address_update_request'),
            self::BranchAvailable => __('messages.enum.notificationtype.branch_available'),
            self::General => __('messages.enum.notificationtype.general'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OrderPlaced => 'shopping_cart',
            self::OrderConfirmed => 'check_circle',
            self::OrderPreparing => 'restaurant',
            self::OrderReady => 'done_all',
            self::OrderOutFOrDelivery => 'local_shipping',
            self::OrderDelivered => 'local_shipping',
            self::OrderCancelled => 'cancel',
            self::OrderEdited => 'edit_note',
            self::LoyaltyPointsEarned => 'stars',
            self::LoyaltyPointsExpiring => 'hourglass_bottom',
            self::ReviewRequest => 'rate_review',
            self::DriverAssigned => 'delivery_dining',
            self::AddressUpdateRequest => 'edit_location',
            self::BranchAvailable => 'storefront',
            self::General => 'notification',
        };
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->title(),
        ], self::cases());
    }
}
