<?php

use App\Services\Payment\CreditCardGateway;
use App\Services\Payment\PaypalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'KWD'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | payment_method => settings. "driver" implements App\Services\Payment\PaymentGateway.
    | "api_key" / "secret" are the provider credentials a real gateway class would use.
    | "simulate" drives the simulated charge: success | fail | timeout.
    | "simulate_verify" drives the simulated status check: success | fail | pending | timeout.
    |
    | Adding a gateway = one class + one entry here; its key becomes a valid payment_method.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Pending Payments Check (payments:check-pending, every 5 minutes)
    |--------------------------------------------------------------------------
    |
    | A pending payment older than "check_after_minutes" is verified with its gateway
    | (in case the webhook never arrived). If it is still pending after
    | "alert_after_hours" it stays pending and a warning is logged for manual review.
    |
    */

    'pending' => [
        'check_after_minutes' => (int) env('PAYMENT_PENDING_CHECK_AFTER_MINUTES', 10),
        'alert_after_hours' => (int) env('PAYMENT_PENDING_ALERT_AFTER_HOURS', 24),
    ],

    'gateways' => [

        'credit_card' => [
            'driver' => CreditCardGateway::class,
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
            'simulate' => env('CREDIT_CARD_SIMULATE', 'success'),
            'simulate_verify' => env('CREDIT_CARD_SIMULATE_VERIFY', 'success'),
        ],

        'paypal' => [
            'driver' => PaypalGateway::class,
            'api_key' => env('PAYPAL_API_KEY'),
            'secret' => env('PAYPAL_SECRET'),
            'simulate' => env('PAYPAL_SIMULATE', 'success'),
            'simulate_verify' => env('PAYPAL_SIMULATE_VERIFY', 'success'),
        ],

    ],

];
