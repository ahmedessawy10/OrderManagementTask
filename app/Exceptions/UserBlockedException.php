<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class UserBlockedException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('messages.auth.account_blocked'));
    }
}
