<?php

namespace App\Exceptions\PostpaidCharging;

use Exception;

class PostpaidChargingException extends Exception
{
    protected $errorCode;
    protected $context;

    public function __construct(
        string $message = "Postpaid Charging Error",
        string $errorCode = 'postpaid_error',
        int $code = 0,
        Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        \Log::error("Postpaid Charging Error [{$this->errorCode}]: " . $this->getMessage(), [
            'error_code' => $this->errorCode,
            'context' => $this->context,
            'exception' => $this,
        ]);

        return false;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error' => $this->errorCode,
            'context' => $this->context,
        ], $this->getCode() ?: 400);
    }
}

