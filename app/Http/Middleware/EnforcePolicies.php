<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use App\Exceptions\PolicyException;

class EnforcePolicies
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
        try {
            return $next($request);
        } catch (AuthorizationException $e) {
            // Convert authorization exceptions to policy exceptions with better messages
            $user = auth()->user();
            $route = $request->route();
            
            // Extract policy information from the route
            $policy = $this->extractPolicyFromRoute($route);
            $action = $this->extractActionFromRequest($request);
            $model = $this->extractModelFromRoute($route);
            
            throw PolicyException::denied($policy, $action, $model, $user);
        }
    }

    /**
     * Extract policy name from route
     */
    protected function extractPolicyFromRoute($route)
    {
        if (!$route) {
            return 'Unknown';
        }

        $controller = $route->getController();
        if (!$controller) {
            return 'Unknown';
        }

        $controllerName = class_basename($controller);
        
        // Map controller names to policy names
        $policyMap = [
            'UserController' => 'OperatorPolicy',
            'IntegratorController' => 'IntegratorPolicy',
            'ChargingPointController' => 'ChargingPointPolicy',
            'PartnerController' => 'PartnerPolicy',
            'GroupController' => 'GroupPolicy',
        ];

        return $policyMap[$controllerName] ?? 'UnknownPolicy';
    }

    /**
     * Extract action from request
     */
    protected function extractActionFromRequest(Request $request)
    {
        $method = $request->method();
        $path = $request->path();

        // Map HTTP methods and paths to actions
        if (str_contains($path, '/create') || $method === 'POST') {
            return 'create';
        }

        if (str_contains($path, '/edit') || str_contains($path, '/update') || $method === 'PUT' || $method === 'PATCH') {
            return 'update';
        }

        if (str_contains($path, '/delete') || $method === 'DELETE') {
            return 'delete';
        }

        if (str_contains($path, '/manage')) {
            return 'manage';
        }

        return 'view';
    }

    /**
     * Extract model from route parameters
     */
    protected function extractModelFromRoute($route)
    {
        if (!$route) {
            return null;
        }

        $parameters = $route->parameters();
        
        // Look for model instances in route parameters
        foreach ($parameters as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return $parameter;
            }
        }

        return null;
    }
}
