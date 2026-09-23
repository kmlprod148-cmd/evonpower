<?php

namespace App\Exceptions\ChargingPoint;

class SessionAlreadyStoppedException extends \Exception
{
    public function __construct(string $message = "La session de recharge est déjà arrêtée", int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}