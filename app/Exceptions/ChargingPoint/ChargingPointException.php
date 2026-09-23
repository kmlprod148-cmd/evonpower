<?php

namespace App\Exceptions\ChargingPoint;

use Exception;

class ChargingPointException extends Exception
{
    /**
     * ChargingPointException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = "Charging Point Error", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}