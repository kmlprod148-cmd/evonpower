<?php

namespace App\Core\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseResourceController extends LaravelController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Determine if the request expects a JSON response.
     *
     * @param Request $request
     * @return bool
     */
    protected function expectsJson(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api/*');
    }

    /**
     * Return a response in the appropriate format (HTML or JSON).
     *
     * @param Request $request
     * @param mixed $data
     * @param string|null $view
     * @param int $status
     * @param array $headers
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    protected function respond(Request $request, $data, ?string $view = null, int $status = 200, array $headers = [])
    {
        if ($this->expectsJson($request)) {
            return response()->json($data, $status, $headers);
        }

        if ($view) {
            return view($view, $data);
        }

        // Fallback: return data as JSON if no view is provided
        return response()->json($data, $status, $headers);
    }

    /**
     * Return a standardized success response.
     *
     * @param Request $request
     * @param mixed $data
     * @param string|null $message
     * @param string|null $view
     * @param int $status
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    protected function successResponse(Request $request, $data = null, ?string $message = null, ?string $view = null, int $status = 200)
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return $this->respond($request, $payload, $view, $status);
    }

    /**
     * Return a standardized error response.
     *
     * @param Request $request
     * @param string $message
     * @param int $status
     * @param array $errors
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function errorResponse(Request $request, string $message, int $status = 400, array $errors = [])
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ];

        return $this->respond($request, $payload, null, $status);
    }
}