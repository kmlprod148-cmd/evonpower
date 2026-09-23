<?php

namespace App\Exceptions\ChargingPoint;

class RateLimitExceededException extends \Exception
{
    public function __construct(string $message = "Trop de requêtes. Veuillez réessayer plus tard.", int $code = 429, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}