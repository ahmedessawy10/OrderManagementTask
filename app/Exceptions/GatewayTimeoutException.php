<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class GatewayTimeoutException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('messages.payment.gateway_timeout'));
    }
}
