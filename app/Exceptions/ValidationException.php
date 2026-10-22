<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public $errors;

    public int $status;

    public function __construct($message = null, $errors = [], int $status = 422)
    {
        $this->errors = $errors;
        $this->status = $status;
        parent::__construct($message ?? __('messages.validation_error'));
    }
}
