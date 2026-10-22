<?php

declare(strict_types=1);

namespace App\Services\Payment;

final class CreditCardGateway implements PaymentGateway
{
    use SimulatesGateway;

    protected function key(): string
    {
        return 'credit_card';
    }
}
