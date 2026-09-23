<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController; // Extend ApiController

abstract class BaseApiController extends \App\Http\Controllers\Controller
{
    // No need to use ApiResponse trait directly here as it's in ApiController

    protected function handleException(\Exception $e, string $defaultMessage = 'An error occurred')
    {
        \Log::error($e->getMessage(), [
            'exception' => $e,
            'stacktrace' => $e->getTraceAsString(), // Add stack trace
            'request' => request()->all(),
            'user_id' => auth()->id()
        ]);

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->sendError('VALIDATION_FAILED', 'Validation failed', 422, $e->errors());
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->sendNotFound();
        }

        return $this->sendError('INTERNAL_ERROR', $defaultMessage, 500);
    }
}