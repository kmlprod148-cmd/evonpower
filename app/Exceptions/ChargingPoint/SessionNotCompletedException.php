<?php

namespace App\Exceptions\ChargingPoint;

use App\Exceptions\BaseCustomException;

class SessionNotCompletedException extends BaseCustomException
{
    protected $message = 'Charging session is not completed.';
    protected $code = 400; // Bad Request

    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
}