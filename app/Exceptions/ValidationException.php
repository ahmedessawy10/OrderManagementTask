<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public $errors;

    public function __construct($message = null, $errors = [])
    {
        $this->errors = $errors;
        parent::__construct($message ?? __('messages.validation_error'));
    }
}
