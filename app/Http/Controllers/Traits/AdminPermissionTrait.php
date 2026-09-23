<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Services\Authorization\PermissionService;

trait AdminPermissionTrait
{
    protected PermissionService $permissionService;

    public function __construct()
    {
        $this->permissionService = app(PermissionService::class);
    }

    /**
     * Check if user has admin permissions using the PermissionService.
     *
     * @return bool
     */
    protected function isAdmin(): bool
    {
        $user = Auth::user();

        if (!$user) {
            Log::debug('AdminPermissionTrait@isAdmin: User not authenticated, returning false.');
            return false;
        }

        $isAdmin = $this->permissionService->hasRole('admin') || $this->permissionService->hasRole('super_admin');
        Log::debug('AdminPermissionTrait@isAdmin: User ID ' . $user->id . ', is_admin: ' . ($isAdmin ? 'true' : 'false') . ', Roles: ' . json_encode($user->getRoleNames()));

        return $isAdmin;
    }

    /**
     * Check if user has specific permission using the PermissionService.
     *
     * @param string $permission
     * @param mixed $model Optional: The model instance for policy checks.
     * @return bool
     */
    protected function hasPermission(string $permission, $model = null): bool
    {
        $user = Auth::user();

        if (!$user) {
            Log::debug('AdminPermissionTrait@hasPermission: User not authenticated for permission "' . $permission . '", returning false.');
            return false;
        }

        $canPermission = $this->permissionService->hasPermission($permission, $model);
        Log::debug('AdminPermissionTrait@hasPermission: User ID ' . $user->id . ', Result of $this->permissionService->hasPermission("' . $permission . '"): ' . ($canPermission ? 'true' : 'false'));

        return $canPermission;
    }

    /**
     * Check permission and redirect if not authorized.
     * Includes logic to prevent redirect loops.
     *
     * @param string|array $permissions The permission(s) to check. Can be a single permission string or an array of permissions (user needs ANY of them).
     * @param string|null $redirectRoute Optional: Specific route name to redirect to if unauthorized.
     * @param string|null $message Optional: Custom message to display if unauthorized.
     * @return \Illuminate\Http\RedirectResponse|null Returns a redirect response if unauthorized, otherwise null.
     */
    protected function checkPermissionOrRedirect(
        string|array $permissions,
        string $redirectRoute = null,
        string $message = null
    ) {
        $permissions = (array) $permissions; // Ensure permissions is an array

        Log::debug('AdminPermissionTrait@checkPermissionOrRedirect: Checking permission(s): ' . json_encode($permissions));

        // Check if the user has any of the required permissions
        if ($this->hasAnyPermission($permissions)) {
            Log::debug('AdminPermissionTrait@checkPermissionOrRedirect: User has at least one required permission, returning null.');
            return null; // User has permission, continue
        }

        // Log unauthorized access attempt
        Log::warning('Unauthorized access attempt', [
            'permissions_required' => $permissions,
            'user_id' => Auth::id(),
            'route' => request()->route()?->getName(),
            'url' => request()->fullUrl()
        ]);

        // Determine redirect route
        $targetRedirectRoute = $redirectRoute ?? $this->getRedirectRoute();
        $message = $message ?? $this->getPermissionDeniedMessage(implode(', ', $permissions)); // Use a combined message for multiple permissions

        // Prevent redirect loop: If the determined redirect route is the same as the current route,
        // and it's not the dashboard (which is the final fallback), redirect to dashboard instead.
        $currentRouteName = request()->route()?->getName();
        if ($currentRouteName === $targetRedirectRoute && $targetRedirectRoute !== 'dashboard') {
             Log::warning("AdminPermissionTrait@checkPermissionOrRedirect: Detected potential redirect loop. Current route '{$currentRouteName}' is same as target redirect route '{$targetRedirectRoute}'. Redirecting to dashboard.");
             $targetRedirectRoute = 'dashboard';
             $message = 'Access denied. You do not have permission to view the requested resource.'; // Generic message for loop prevention fallback
        } else if ($currentRouteName === $targetRedirectRoute && $targetRedirectRoute === 'dashboard') {
             // If we are already on the dashboard and still triggering a redirect,
             // it means the user doesn't even have access to the dashboard based on the logic.
             // This is an edge case, fallback to login.
             Log::warning("AdminPermissionTrait@checkPermissionOrRedirect: Detected potential redirect loop on dashboard. Redirecting to login.");
             $targetRedirectRoute = 'login';
             $message = 'Access denied. Please login with appropriate credentials.'; // Message for dashboard loop fallback
        }


        Log::debug('AdminPermissionTrait@checkPermissionOrRedirect: User does NOT have required permission(s), redirecting to route "' . $targetRedirectRoute . '" with message "' . $message . '"');

        return redirect()->route($targetRedirectRoute)->with('error', $message);
    }

    /**
     * Get the appropriate redirect route based on user permissions.
     * Prioritizes specific resource index pages over the dashboard.
     *
     * @return string The name of the redirect route.
     */
    /**
     * Get the appropriate redirect route based on user role
     *
     * @return string
     */
    protected function getRedirectRoute(): string
    {
        $user = Auth::user();

        if (!$user) {
            return 'login';
        }

        // Check if we're already on the dashboard to prevent redirect loops
        if (request()->route()?->getName() === 'dashboard') {
            // If we're already on dashboard and still redirecting, something is wrong
            // Return to home or login
            return 'login';
        }

        // Check what the user can access and redirect appropriately
        $possibleRoutes = [
            'charging-points.index' => ['view_charging_points', 'view_integrator_charging_points', 'view_own_charging_points'],
            'groups.index' => ['view_groups', 'view_all_groups', 'view_integrator_groups', 'view_own_groups'],
            'partners.index' => ['view_partners', 'view_integrator_partners', 'view_own_partners'],
            'transactions.index' => ['view_transactions', 'view_own_transactions'],
            'reports.index' => ['view_reports', 'view_own_reports'],
            'dashboard' => null, // Always accessible if authenticated
        ];

        foreach ($possibleRoutes as $route => $permissions) {
            if ($permissions === null) {
                // No permission check needed (like dashboard)
                if (\Route::has($route)) {
                    return $route;
                }
            } else {
                // Check if user has any of the required permissions
                // Check if user has any of the required permissions using the service
                if ($this->permissionService->hasAnyPermission((array)$permissions)) {
                    if (\Route::has($route)) {
                        return $route;
                    }
                    break; // Found a matching permission, no need to check others
                }
            }
        }

        return 'dashboard'; // Final fallback
    }

    /**
     * Get permission denied message
     */
    protected function getPermissionDeniedMessage(string $permission): string
    {
        $messages = [
            'view_business_profiles' => 'You do not have permission to view business profiles.',
            'create_business_profiles' => 'You do not have permission to create business profiles.',
            'edit_business_profiles' => 'You do not have permission to edit business profiles.',
            'delete_business_profiles' => 'You do not have permission to delete business profiles.',
            'admin_access' => 'You do not have admin access.',
        ];

        return $messages[$permission] ?? 'You do not have permission to access this resource.';
    }

    /**
     * Check multiple permissions (user needs ANY of them)
     */
    /**
     * Check multiple permissions (user needs ANY of them) using the PermissionService.
     */
    protected function hasAnyPermission(array $permissions, $model = null): bool
    {
        return $this->permissionService->hasAnyPermission($permissions, $model);
    }

    /**
     * Check multiple permissions (user needs ALL of them) using the PermissionService.
     */
    protected function hasAllPermissions(array $permissions, $model = null): bool
    {
        return $this->permissionService->hasAllPermissions($permissions, $model);
    }
}