<?php

namespace App\Exceptions\ChargingPoint;

class InvalidQRCodeException extends \Exception
{
    public function __construct(string $message = "QR code invalide ou expiré", int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}