<?php

namespace App\Exceptions;

use Exception;

class OcppCommandException extends Exception
{
    /**
     * @var bool
     */
    protected bool $retryable;

    /**
     * @param string $message
     * @param int $code
     * @param bool $retryable
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, bool $retryable = false, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryable = $retryable;
    }

    /**
     * @return bool
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
