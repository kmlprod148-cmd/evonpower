<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException as LaravelValidationException;

class CustomValidationException extends LaravelValidationException
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        // Log the validation errors
        \Log::warning('Validation Error: ' . $this->getMessage(), ['errors' => $this->errors()]);

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
        // Customize the response for validation errors
        return response()->json([
            'message' => $this->getMessage() ?: 'The given data was invalid.',
            'errors' => $this->errors(),
        ], $this->status);
    }
}