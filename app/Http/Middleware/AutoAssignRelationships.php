<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AutoAssignmentService;
use Illuminate\Support\Facades\Auth;

class AutoAssignRelationships
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
        // Only process POST and PUT/PATCH requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Only process if user is authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        // Auto-assign relationships based on route and model
        $this->autoAssignRelationships($request);

        return $next($request);
    }

    /**
     * Auto-assign relationships based on the request
     */
    protected function autoAssignRelationships(Request $request)
    {
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        $data = $request->all();

        // Determine model type from route
        $modelType = $this->getModelTypeFromRoute($routeName, $request->path());
        
        if (!$modelType) {
            return;
        }

        // Auto-assign relationships based on model type
        switch ($modelType) {
            case 'user':
                $autoAssignedData = AutoAssignmentService::assignUserRelationships(new \App\Models\User(), $data);
                $this->mergeDataIntoRequest($request, $autoAssignedData);
                break;

            case 'charging_point':
                $autoAssignedData = AutoAssignmentService::assignChargingPointRelationships(new \App\Models\ChargingPoint(), $data);
                $this->mergeDataIntoRequest($request, $autoAssignedData);
                break;

            case 'group':
                $autoAssignedData = AutoAssignmentService::assignGroupRelationships(new \App\Models\Group(), $data);
                $this->mergeDataIntoRequest($request, $autoAssignedData);
                break;

            case 'partner':
                $autoAssignedData = AutoAssignmentService::assignPartnerRelationships(new \App\Models\Partner(), $data);
                $this->mergeDataIntoRequest($request, $autoAssignedData);
                break;
        }
    }

    /**
     * Get model type from route name or path
     */
    protected function getModelTypeFromRoute($routeName, $path)
    {
        // Check route name first
        if ($routeName) {
            if (str_contains($routeName, 'user') || str_contains($routeName, 'operator')) {
                return 'user';
            }
            if (str_contains($routeName, 'charging-point')) {
                return 'charging_point';
            }
            if (str_contains($routeName, 'group')) {
                return 'group';
            }
            if (str_contains($routeName, 'partner')) {
                return 'partner';
            }
        }

        // Check path as fallback
        if (str_contains($path, '/users') || str_contains($path, '/operators')) {
            return 'user';
        }
        if (str_contains($path, '/charging-points')) {
            return 'charging_point';
        }
        if (str_contains($path, '/groups')) {
            return 'group';
        }
        if (str_contains($path, '/partners')) {
            return 'partner';
        }

        return null;
    }

    /**
     * Merge auto-assigned data into the request
     */
    protected function mergeDataIntoRequest(Request $request, array $autoAssignedData)
    {
        if (empty($autoAssignedData)) {
            return;
        }

        // Merge auto-assigned data into request data
        $currentData = $request->all();
        $mergedData = array_merge($currentData, $autoAssignedData);
        
        // Update the request with merged data
        $request->merge($mergedData);

        // Store auto-assigned data in session for display purposes
        if (!empty($autoAssignedData)) {
            session()->flash('auto_assigned_fields', $autoAssignedData);
        }
    }

    /**
     * Get the authenticated user's available options for a model type
     */
    public static function getAvailableOptions($modelType)
    {
        return AutoAssignmentService::getAvailableOptions($modelType);
    }

    /**
     * Get default values for a model type
     */
    public static function getDefaultValues($modelType)
    {
        return AutoAssignmentService::getDefaultValues($modelType);
    }

    /**
     * Validate assignment for a model type
     */
    public static function validateAssignment($modelType, $data)
    {
        return AutoAssignmentService::validateAssignment($modelType, $data);
    }
}
