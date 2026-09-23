<?php

namespace App\Exceptions\ChargingPoint;

class ChargingPointNotFoundException extends \Exception
{
    public function __construct(string $message = "Borne de recharge non trouvée", int $code = 404, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}