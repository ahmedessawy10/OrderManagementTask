<?php

declare(strict_types=1);

namespace App\Services\Payment;

final class PaypalGateway implements PaymentGateway
{
    use SimulatesGateway;

    protected function key(): string
    {
        return 'paypal';
    }
}
