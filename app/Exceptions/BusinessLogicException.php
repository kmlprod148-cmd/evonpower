<?php

namespace App\Exceptions;

use Exception;

class BusinessLogicException extends Exception
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        // Log the exception details
        \Log::error('Business Logic Error: ' . $this->getMessage(), ['exception' => $this]);

        // Optionally send notification for critical business logic errors
        // if ($this->getCode() >= 500) {
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
        // Customize the response for this exception
        return response()->json([
            'message' => $this->getMessage() ?: 'A business logic error occurred.',
            'code' => $this->getCode() ?: 400, // Default to 400 Bad Request
        ], $this->getCode() ?: 400);
    }
}