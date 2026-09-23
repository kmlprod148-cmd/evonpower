<?php

namespace App\Exceptions\ChargingPoint;

class UnauthorizedAccessException extends \Exception
{
    public function __construct(string $message = "Accès non autorisé", int $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}