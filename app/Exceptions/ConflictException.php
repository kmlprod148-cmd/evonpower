<?php

namespace App\Exceptions;

use Exception;

class ConflictException extends Exception
{
    public function __construct(string $message = 'Conflict', int $code = 409, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}