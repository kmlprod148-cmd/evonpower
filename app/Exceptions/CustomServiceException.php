<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Class CustomServiceException
 *
 * Custom exception for service layer errors.
 *
 * @package App\Exceptions
 */
class CustomServiceException extends Exception
{
    /**
     * CustomServiceException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}