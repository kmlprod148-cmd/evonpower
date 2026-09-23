<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\CustomValidationException;
use App\Exceptions\CustomAuthorizationException;
use App\Exceptions\ExternalServiceException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log the exception details
            \Log::error('Application Error: ' . $e->getMessage(), ['exception' => $e]);

            // Placeholder for error tracking integration (e.g., Sentry, Bugsnag)
            // if (app()->bound('sentry') && $this->shouldReport($e)) {
            //     app('sentry')->captureException($e);
            // }

            // Send notifications for critical errors
            if ($this->shouldSendNotification($e)) {
                // Implement Slack/email notification logic here
                // \Notification::route('mail', config('app.admin_email'))
                //     ->notify(new \App\Notifications\CriticalErrorNotification($e));
                // \Notification::route('slack', config('logging.channels.slack.url'))
                //     ->notify(new \App\Notifications\CriticalErrorNotification($e));
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e): Response
    {
        // Log the exception for debugging
        \Log::error('Exception in Handler::render', [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $request->url(),
            'method' => $request->method(),
            'expects_json' => $request->expectsJson(),
            'is_api' => $request->is('api/*'),
            'accept_header' => $request->header('Accept'),
            'ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson()
        ]);

        // Check if request expects JSON (including AJAX requests)
        // Also check for X-Requested-With header which is sent by fetch/XHR requests
        $isAjaxRequest = $request->ajax() || 
                         $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                         $request->header('Accept') === 'application/json' ||
                         str_contains($request->header('Accept', ''), 'application/json');
        
        if ($request->expectsJson() || $request->is('api/*') || $isAjaxRequest || $request->wantsJson()) {
            return $this->handleApiException($request, $e);
        }

        // Handle web requests for ModelNotFoundException
        if ($e instanceof ModelNotFoundException) {
            return $this->handleWebModelNotFoundException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException($request, Throwable $e): JsonResponse
    {
        if ($e instanceof BusinessLogicException) {
            return response()->json([
                'message' => $e->getMessage() ?: 'A business logic error occurred.',
                'code' => $e->getCode() ?: 400,
            ], $e->getCode() ?: 400);
        }

        if ($e instanceof CustomValidationException) {
             return response()->json([
                'message' => $e->getMessage() ?: 'The given data was invalid.',
                'errors' => $e->errors(),
            ], $e->status);
        }

         if ($e instanceof CustomAuthorizationException) {
             return response()->json([
                'message' => $e->getMessage() ?: 'You are not authorized to perform this action.',
            ], 403); // 403 Forbidden
        }

        if ($e instanceof ExternalServiceException) {
            return response()->json([
                'message' => 'An error occurred while communicating with an external service.',
                'service' => $e->serviceName,
                'status_code' => $e->statusCode,
                // 'response_body' => config('app.debug') ? $e->responseBody : null,
            ], $e->getCode() ?: ($e->statusCode ?: 503)); // Default to 503 Service Unavailable
        }

        // Handle other common API exceptions
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], $e->status);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found.',
            ], 404);
        }

        if ($e instanceof NotFoundHttpException) {
             return response()->json([
                'message' => 'The requested URL was not found.',
            ], 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
             return response()->json([
                'message' => 'The HTTP method is not allowed for this route.',
            ], 405);
        }


        // Default handling for other exceptions, always return JSON for API requests
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $statusCode = $statusCode < 100 || $statusCode >= 600 ? 500 : $statusCode; // Ensure valid HTTP status code

        $response = [
            'message' => $e->getMessage() ?: 'An unexpected error occurred.',
        ];

        if (config('app.debug')) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = explode("\n", $e->getTraceAsString());
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Handle ModelNotFoundException for web requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\ModelNotFoundException  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleWebModelNotFoundException($request, ModelNotFoundException $e): Response
    {
        $model = $e->getModel();
        $ids = $e->getIds();
        
        // Log the error for debugging
        \Log::warning('Model not found for web request', [
            'model' => $model,
            'ids' => $ids,
            'url' => $request->url(),
            'user_id' => auth()->id(),
            'ip' => $request->ip()
        ]);

        // Determine appropriate redirect based on the model
        if (str_contains($model, 'Reservation')) {
            return redirect()->route('reservations.index')
                ->with('error', 'Cette réservation n\'existe pas ou a été supprimée.');
        } elseif (str_contains($model, 'ChargingPoint')) {
            return redirect()->route('charging-points.index')
                ->with('error', 'Ce point de charge n\'existe pas ou a été supprimé.');
        } elseif (str_contains($model, 'User')) {
            return redirect()->route('dashboard')
                ->with('error', 'Cet utilisateur n\'existe pas.');
        } elseif (str_contains($model, 'Transaction')) {
            return redirect()->route('transactions.index')
                ->with('error', 'Cette transaction n\'existe pas ou a été supprimée.');
        }

        // Default fallback
        return redirect()->route('dashboard')
            ->with('error', 'La ressource demandée n\'existe pas.');
    }

    /**
     * Determine if the exception should trigger a notification.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function shouldSendNotification(Throwable $e): bool
    {
        // Define conditions for sending notifications (e.g., only for server errors)
        return $e instanceof \ErrorException || // Catch critical PHP errors
               ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() >= 500) ||
               $e instanceof BusinessLogicException && $e->getCode() >= 500 ||
               $e instanceof ExternalServiceException && ($e->getCode() >= 500 || ($e->statusCode && $e->statusCode >= 500));
    }
}