<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class IncompleteException extends Exception
{
    private int $userId;

    public function __construct(string $message, int $userId)
    {
        $this->userId = $userId;
        parent::__construct($message);
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
