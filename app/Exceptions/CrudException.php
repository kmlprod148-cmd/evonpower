<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CrudException extends Exception
{
    /**
     * Create a new CrudException instance.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message = 'CRUD operation failed', int $code = 400, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return Response|JsonResponse|RedirectResponse
     */
    public function render(Request $request)
    {
        // If the request expects JSON (API request), return JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => $this->getMessage(),
                'code' => $this->getCode(),
                'type' => 'CrudException'
            ], $this->getCode());
        }

        // For web requests, redirect back with error message
        return redirect()->back()
            ->with('error', $this->getMessage())
            ->withInput();
    }

    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        // Don't report CrudExceptions to logs as they're expected business logic exceptions
        // These are handled exceptions that represent normal business rule violations
        return false;
    }

    /**
     * Get the exception's context information.
     *
     * @return array
     */
    public function context(): array
    {
        return [
            'exception_type' => 'CrudException',
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    /**
     * Create a new CrudException for validation errors.
     *
     * @param string $message
     * @param array $errors
     * @return static
     */
    public static function validationError(string $message = 'Validation failed', array $errors = []): static
    {
        $exception = new static($message, 422);
        $exception->errors = $errors;
        return $exception;
    }

    /**
     * Create a new CrudException for not found errors.
     *
     * @param string $resource
     * @return static
     */
    public static function notFound(string $resource = 'Resource'): static
    {
        return new static("{$resource} not found", 404);
    }

    /**
     * Create a new CrudException for unauthorized access.
     *
     * @param string $action
     * @return static
     */
    public static function unauthorized(string $action = 'perform this action'): static
    {
        return new static("You are not authorized to {$action}", 403);
    }

    /**
     * Create a new CrudException for duplicate entries.
     *
     * @param string $field
     * @return static
     */
    public static function duplicate(string $field = 'entry'): static
    {
        return new static("A {$field} with these details already exists", 409);
    }

    /**
     * Create a new CrudException for business rule violations.
     *
     * @param string $rule
     * @return static
     */
    public static function businessRule(string $rule): static
    {
        return new static("Business rule violation: {$rule}", 422);
    }

    /**
     * Create a new CrudException for database errors.
     *
     * @param string $operation
     * @return static
     */
    public static function databaseError(string $operation = 'database operation'): static
    {
        return new static("Failed to perform {$operation}", 500);
    }

    /**
     * Create a new CrudException for invalid state transitions.
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public static function invalidStateTransition(string $from, string $to): static
    {
        return new static("Cannot transition from '{$from}' to '{$to}'", 422);
    }

    /**
     * Create a new CrudException for dependency conflicts.
     *
     * @param string $resource
     * @param string $dependency
     * @return static
     */
    public static function dependencyConflict(string $resource, string $dependency): static
    {
        return new static("Cannot delete {$resource} because it has associated {$dependency}", 409);
    }

    /**
     * Create a new CrudException for quota exceeded.
     *
     * @param string $quota
     * @return static
     */
    public static function quotaExceeded(string $quota): static
    {
        return new static("Quota exceeded for {$quota}", 429);
    }

    /**
     * Create a new CrudException for insufficient funds.
     *
     * @param float $required
     * @param float $available
     * @return static
     */
    public static function insufficientFunds(float $required, float $available): static
    {
        return new static("Insufficient funds. Required: {$required}, Available: {$available}", 422);
    }

    /**
     * Get validation errors if this is a validation exception.
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->errors ?? [];
    }

    /**
     * Check if this exception has validation errors.
     *
     * @return bool
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Convert the exception to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'success' => false,
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'type' => 'CrudException'
        ];

        if ($this->hasValidationErrors()) {
            $array['errors'] = $this->getValidationErrors();
        }

        return $array;
    }

    /**
     * Convert the exception to JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Private property to store validation errors.
     *
     * @var array
     */
    private array $errors = [];
}