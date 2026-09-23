<?php

namespace App\Exceptions;

class PartnerNotFoundException extends NotFoundException
{
    public function __construct(string $message = 'Partner not found', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct($message, $previous, $headers, $code);
    }
}