<?php

namespace App\Exceptions;

use Exception;

class PermissionDeniedException extends Exception
{
    public function __construct(string $message = 'Permission denied', int $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}