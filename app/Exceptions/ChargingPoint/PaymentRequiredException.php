<?php

namespace App\Exceptions\ChargingPoint;

class PaymentRequiredException extends \Exception
{
    protected array $paymentOptions;

    public function __construct(string $message = "Un paiement est requis", array $paymentOptions = [], int $code = 402, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->paymentOptions = $paymentOptions;
    }

    public function getPaymentOptions(): array
    {
        return $this->paymentOptions;
    }
}