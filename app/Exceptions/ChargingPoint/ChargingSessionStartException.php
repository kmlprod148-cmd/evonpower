<?php

namespace App\Exceptions\ChargingPoint;

class ChargingSessionStartException extends \Exception
{
    public function __construct(string $message = "Erreur lors du démarrage de la session de recharge", int $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}