<?php

namespace App\Services\ErrorHandling;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notification\NotificationService;
use Exception;
use Throwable;

class ErrorHandlingService
{
    protected $auditService;
    protected $notificationService;

    public function __construct(
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle application errors with comprehensive logging and notifications
     */
    public function handleError(
        Throwable $exception,
        string $context = 'application',
        array $metadata = [],
        bool $notifyAdmins = false
    ): void {
        // Log the error
        $this->logError($exception, $context, $metadata);

        // Create audit log for security-related errors
        if ($this->isSecurityError($exception)) {
            $this->auditService->logSecurityEvent(
                'error_occurred',
                "Security-related error: {$exception->getMessage()}",
                array_merge($metadata, [
                    'exception_class' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ])
            );
        }

        // Notify administrators for critical errors
        if ($notifyAdmins || $this->isCriticalError($exception)) {
            $this->notifyAdmins($exception, $context, $metadata);
        }

        // Send user-friendly error response
        $this->sendUserFriendlyError($exception, $context);
    }

    /**
     * Handle API errors with proper HTTP status codes
     */
    public function handleApiError(
        Throwable $exception,
        string $context = 'api',
        array $metadata = []
    ): array {
        $this->logError($exception, $context, $metadata);

        $statusCode = $this->getHttpStatusCode($exception);
        $errorCode = $this->getErrorCode($exception);
        $message = $this->getUserFriendlyMessage($exception);

        return [
            'error' => true,
            'code' => $errorCode,
            'message' => $message,
            'status_code' => $statusCode,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID', uniqid())
        ];
    }

    /**
     * Handle validation errors with detailed field information
     */
    public function handleValidationError(
        array $errors,
        string $context = 'validation',
        array $metadata = []
    ): array {
        $this->logValidationError($errors, $context, $metadata);

        return [
            'error' => true,
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
            'errors' => $errors,
            'context' => $context,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Handle database errors with recovery suggestions
     */
    public function handleDatabaseError(
        Throwable $exception,
        string $operation = 'unknown',
        array $metadata = []
    ): array {
        $this->logError($exception, 'database', array_merge($metadata, [
            'operation' => $operation
        ]));

        $suggestions = $this->getDatabaseErrorSuggestions($exception);

        return [
            'error' => true,
            'code' => 'DATABASE_ERROR',
            'message' => 'Database operation failed',
            'suggestions' => $suggestions,
            'operation' => $operation,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Handle authentication errors with security logging
     */
    public function handleAuthError(
        string $reason,
        ?User $user = null,
        array $metadata = []
    ): void {
        $this->auditService->logSecurityEvent(
            'auth_failed',
            "Authentication failed: {$reason}",
            array_merge($metadata, [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]),
            $user
        );

        Log::warning('Authentication failed', [
            'reason' => $reason,
            'user_id' => $user?->id,
            'ip' => request()->ip()
        ]);
    }

    /**
     * Handle authorization errors with detailed logging
     */
    public function handleAuthorizationError(
        string $permission,
        ?User $user = null,
        string $resource = null,
        array $metadata = []
    ): void {
        $this->auditService->logSecurityEvent(
            'unauthorized_access',
            "Unauthorized access attempt: {$permission}",
            array_merge($metadata, [
                'user_id' => $user?->id,
                'permission' => $permission,
                'resource' => $resource,
                'ip_address' => request()->ip()
            ]),
            $user
        );

        Log::warning('Authorization failed', [
            'permission' => $permission,
            'user_id' => $user?->id,
            'resource' => $resource
        ]);
    }

    /**
     * Get error statistics for monitoring
     */
    public function getErrorStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        // This would typically query a dedicated error log table
        // For now, we'll return mock data
        return [
            'total_errors' => 0,
            'errors_by_type' => [],
            'errors_by_context' => [],
            'critical_errors' => 0,
            'security_errors' => 0,
            'database_errors' => 0,
            'api_errors' => 0
        ];
    }

    /**
     * Log error with context
     */
    private function logError(Throwable $exception, string $context, array $metadata): void
    {
        Log::error("Error in {$context}", [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'metadata' => $metadata,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Log validation errors
     */
    private function logValidationError(array $errors, string $context, array $metadata): void
    {
        Log::warning("Validation error in {$context}", [
            'errors' => $errors,
            'context' => $context,
            'metadata' => $metadata,
            'url' => request()->fullUrl(),
            'method' => request()->method()
        ]);
    }

    /**
     * Check if error is security-related
     */
    private function isSecurityError(Throwable $exception): bool
    {
        $securityPatterns = [
            'Unauthorized',
            'Forbidden',
            'Authentication',
            'Authorization',
            'Security',
            'Permission',
            'Access denied'
        ];

        $message = $exception->getMessage();
        foreach ($securityPatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if error is critical
     */
    private function isCriticalError(Throwable $exception): bool
    {
        $criticalPatterns = [
            'Database',
            'Connection',
            'Memory',
            'Fatal',
            'Critical',
            'System'
        ];

        $message = $exception->getMessage();
        foreach ($criticalPatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Notify administrators about critical errors
     */
    private function notifyAdmins(Throwable $exception, string $context, array $metadata): void
    {
        $adminUsers = User::role(['admin', 'super_admin'])->get();
        
        foreach ($adminUsers as $admin) {
            $this->notificationService->sendNotification(
                $admin,
                'error',
                'Critical Error Alert',
                "A critical error occurred in {$context}: " . $exception->getMessage(),
                array_merge($metadata, [
                    'exception_class' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'context' => $context
                ])
            );
        }
    }

    /**
     * Send user-friendly error response
     */
    private function sendUserFriendlyError(Throwable $exception, string $context): void
    {
        // This would typically be handled by a custom exception handler
        // For now, we'll just log it
        Log::info("User-friendly error sent", [
            'context' => $context,
            'exception' => get_class($exception)
        ]);
    }

    /**
     * Get HTTP status code for exception
     */
    private function getHttpStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        if (method_exists($exception, 'getCode') && $exception->getCode() >= 400) {
            return $exception->getCode();
        }

        return 500;
    }

    /**
     * Get error code for exception
     */
    private function getErrorCode(Throwable $exception): string
    {
        $class = get_class($exception);
        $parts = explode('\\', $class);
        $className = end($parts);
        
        return strtoupper($className);
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        
        // Map technical errors to user-friendly messages
        $friendlyMessages = [
            'Database connection failed' => 'Service temporarily unavailable. Please try again later.',
            'Authentication failed' => 'Invalid credentials. Please check your login information.',
            'Authorization failed' => 'You do not have permission to perform this action.',
            'Validation failed' => 'Please check your input and try again.',
            'Not found' => 'The requested resource was not found.',
            'Server error' => 'An unexpected error occurred. Please try again later.'
        ];

        foreach ($friendlyMessages as $technical => $friendly) {
            if (stripos($message, $technical) !== false) {
                return $friendly;
            }
        }

        return 'An unexpected error occurred. Please try again later.';
    }

    /**
     * Get database error recovery suggestions
     */
    private function getDatabaseErrorSuggestions(Throwable $exception): array
    {
        $message = $exception->getMessage();
        $suggestions = [];

        if (stripos($message, 'connection') !== false) {
            $suggestions[] = 'Check database connection settings';
            $suggestions[] = 'Verify database server is running';
        }

        if (stripos($message, 'table') !== false) {
            $suggestions[] = 'Run database migrations';
            $suggestions[] = 'Check table structure';
        }

        if (stripos($message, 'constraint') !== false) {
            $suggestions[] = 'Check foreign key constraints';
            $suggestions[] = 'Verify data integrity';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Contact system administrator';
        }

        return $suggestions;
    }
}
