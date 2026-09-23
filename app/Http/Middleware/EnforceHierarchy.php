<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\HierarchicalValidationService;
use Illuminate\Support\Facades\Auth;

class EnforceHierarchy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }

        // Check if the request involves resource management
        if ($this->isResourceManagementRequest($request)) {
            $resource = $this->getResourceFromRequest($request);
            
            if ($resource && !HierarchicalValidationService::canUserManageResource($user, $resource)) {
                return response()->json([
                    'error' => 'Access denied. You do not have permission to manage this resource.',
                    'message' => 'This resource is outside your hierarchical scope.'
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if the request involves resource management
     */
    private function isResourceManagementRequest(Request $request)
    {
        $method = $request->method();
        $path = $request->path();
        
        return in_array($method, ['PUT', 'PATCH', 'DELETE']) || 
               str_contains($path, '/update') || 
               str_contains($path, '/delete') ||
               str_contains($path, '/store');
    }

    /**
     * Get the resource from the request
     */
    private function getResourceFromRequest(Request $request)
    {
        $route = $request->route();
        
        if (!$route) {
            return null;
        }

        $parameters = $route->parameters();
        
        // Try to get the model from route parameters
        foreach ($parameters as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return $parameter;
            }
        }

        return null;
    }
}
