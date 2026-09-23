<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

trait ApiResponse
{
    /**
     * Send a success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function sendSuccess(
        $data = null,
        ?string $message = null,
        int $statusCode = HttpResponse::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->respond($response, $statusCode, $headers);
    }

    /**
     * Send a success response with pagination metadata.
     *
     * @param LengthAwarePaginator $paginator
     * @param string|null $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function sendPaginatedSuccess(
        LengthAwarePaginator $paginator,
        ?string $message = null,
        int $statusCode = HttpResponse::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'data' => $paginator->items(),
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'meta' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        return $this->respond($response, $statusCode, $headers);
    }

    /**
     * Send an error response.
     *
     * @param string $errorCode
     * @param string $errorMessage
     * @param int $statusCode
     * @param MessageBag|array|null $errors
     * @param array $headers
     * @return JsonResponse
     */
    protected function sendError(
        string $errorCode,
        string $errorMessage,
        int $statusCode = HttpResponse::HTTP_BAD_REQUEST,
        $errors = null,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $errorMessage,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors instanceof MessageBag) {
            $response['errors'] = $errors->toArray();
        } elseif (is_array($errors)) {
            $response['errors'] = $errors;
        }

        return $this->respond($response, $statusCode, $headers);
    }

    /**
     * Respond with the given data, status code, and headers, handling content negotiation.
     *
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respond(array $data, int $statusCode, array $headers): JsonResponse
    {
        $request = request();

        // For API endpoints, always return JSON regardless of Accept header
        // This prevents issues when accessing API endpoints through browsers
            return Response::json($data, $statusCode, $headers);
    }

    /**
     * Send a 204 No Content response.
     *
     * @param array $headers
     * @return JsonResponse
     */
    protected function sendNoContent(array $headers = []): JsonResponse
    {
        return $this->respond([], HttpResponse::HTTP_NO_CONTENT, $headers);
    }

    /**
     * Send a 404 Not Found error response.
     *
     * @param string $message
     * @param string $errorCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function sendNotFound(
        string $message = 'Resource not found.',
        string $errorCode = 'RESOURCE_NOT_FOUND',
        array $headers = []
    ): JsonResponse {
        return $this->sendError($errorCode, $message, HttpResponse::HTTP_NOT_FOUND, null, $headers);
    }

    /**
     * Send a 422 Unprocessable Entity error response for validation failures.
     *
     * @param MessageBag|array $errors
     * @param string $message
     * @param string $errorCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function sendValidationErrors(
        $errors,
        string $message = 'Validation failed.',
        string $errorCode = 'VALIDATION_FAILED',
        array $headers = []
    ): JsonResponse {
        return $this->sendError($errorCode, $message, HttpResponse::HTTP_UNPROCESSABLE_ENTITY, $errors, $headers);
    }

    /**
     * Add caching headers to the response.
     *
     * @param JsonResponse $response
     * @param string $etag
     * @param \DateTimeInterface|string $lastModified
     * @param int $maxAge
     * @param bool $isPublic
     * @return JsonResponse
     */
    protected function withCache(
        JsonResponse $response,
        string $etag,
        $lastModified,
        int $maxAge = 3600,
        bool $isPublic = true
    ): JsonResponse {
        $cacheControl = $isPublic ? 'public, ' : 'private, ';
        $cacheControl .= 'max-age=' . $maxAge;

        $response->header('Cache-Control', $cacheControl);
        $response->header('ETag', $etag);
        $response->header('Last-Modified', (new \DateTime($lastModified))->toRfc7231String());

        return $response;
    }
}