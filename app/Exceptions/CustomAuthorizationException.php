<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException as LaravelAuthorizationException;

class CustomAuthorizationException extends LaravelAuthorizationException
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        // Log the authorization attempt
        \Log::warning('Authorization Error: ' . $this->getMessage());

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
        // Customize the response for authorization errors
        return response()->json([
            'message' => $this->getMessage() ?: 'You are not authorized to perform this action.',
        ], 403); // 403 Forbidden
    }
}