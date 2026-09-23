<?php

namespace App\Exceptions\ChargingPoint;

class ValidationException extends \Exception
{
    protected array $errors;

    public function __construct(string $message = "Erreur de validation", array $errors = [], int $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}