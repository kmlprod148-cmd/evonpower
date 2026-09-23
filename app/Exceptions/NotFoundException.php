<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Resource not found', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct($message, $previous, $headers, $code);
    }
}