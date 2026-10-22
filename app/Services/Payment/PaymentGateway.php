<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Exceptions\GatewayTimeoutException;
use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Charge the payment amount. Must not save the payment.
     *
     * @return array{status: PaymentStatus, gateway_reference: ?string}
     *
     * @throws GatewayTimeoutException when the outcome is unknown
     */
    public function charge(Payment $payment): array;

    /**
     * Ask the gateway what happened to an earlier charge (by $payment->reference). Never charges.
     *
     * @return array{status: PaymentStatus, gateway_reference: ?string}
     *
     * @throws GatewayTimeoutException when the gateway does not answer
     */
    public function verify(Payment $payment): array;
}
