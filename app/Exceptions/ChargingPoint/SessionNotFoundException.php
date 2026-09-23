<?php

namespace App\Exceptions\ChargingPoint;

class SessionNotFoundException extends \Exception
{
    public function __construct(string $message = "Session de recharge non trouvée", int $code = 404, \Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
}