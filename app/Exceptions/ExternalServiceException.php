<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ExternalServiceException extends Exception
{
    protected $serviceName;
    protected $statusCode;
    protected $responseBody;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, string $serviceName = 'External Service', ?int $statusCode = null, ?string $responseBody = null)
    {
        parent::__construct($message, $code, $previous);
        $this->serviceName = $serviceName;
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        // Log the exception details
        \Log::error("External Service Error [{$this->serviceName}]: " . $this->getMessage(), [
            'service' => $this->serviceName,
            'status_code' => $this->statusCode,
            'response_body' => $this->responseBody,
            'exception' => $this,
        ]);

        // Optionally send notification for critical external service errors
        // if ($this->getCode() >= 500 || ($this->statusCode && $this->statusCode >= 500)) {
        //     // Send Slack/email notification
        // }

        return false; // Let the exception propagate to the handler
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        // Customize the response for external service errors
        return response()->json([
            'message' => 'An error occurred while communicating with an external service.',
            'service' => $this->serviceName,
            'status_code' => $this->statusCode,
            // Optionally include response body in debug mode
            // 'response_body' => config('app.debug') ? $this->responseBody : null,
        ], $this->getCode() ?: ($this->statusCode ?: 503)); // Default to 503 Service Unavailable
    }
}