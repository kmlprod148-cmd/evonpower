<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

abstract class BaseResourceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Determine the desired response format based on the request.
     *
     * @param Request $request
     * @return string 'html' or 'json'
     */
    protected function getResponseFormat(Request $request): string
    {
        // Prioritize Accept header for API requests
        if ($request->expectsJson()) {
            return 'json';
        }

        // Default to HTML for web requests
        return 'html';
    }

    /**
     * Handle content negotiation for index requests.
     *
     * @param Request $request
     * @param mixed $data
     * @param string $viewName
     * @param string $resourceClass
     * @return JsonResponse|View
     */
    protected function handleIndexResponse(Request $request, $data, string $viewName, string $resourceClass)
    {
        switch ($this->getResponseFormat($request)) {
            case 'json':
                return JsonResponse::fromJsonString($resourceClass::collection($data)->toJson());
            case 'html':
            default:
                return view($viewName, ['items' => $data]);
        }
    }

    /**
     * Handle content negotiation for show requests.
     *
     * @param Request $request
     * @param mixed $item
     * @param string $viewName
     * @param string $resourceClass
     * @return JsonResponse|View
     */
    protected function handleShowResponse(Request $request, $item, string $viewName, string $resourceClass)
    {
        switch ($this->getResponseFormat($request)) {
            case 'json':
                return JsonResponse::fromJsonString((new $resourceClass($item))->toJson());
            case 'html':
            default:
                return view($viewName, ['item' => $item]);
        }
    }

    // Add more helper methods for create, store, edit, update, destroy as needed
}