<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRedirectMiddleware
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
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admins pass through unconditionally
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $next($request);
        }

        $routeName = $request->route()->getName();

        // Routes that require a specific permission for non-admins.
        // If the route matches a pattern the user must have the mapped permission;
        // if no pattern matches, the controller's own Gate::authorize() is authoritative.
        $routePermissions = [
            'charging-points.*'   => 'view_charging_points',
            'business-profiles.*' => 'manage_business_profiles',
            'users.*'             => 'manage_users',
            'settings.*'          => 'manage_settings',
            'partners.*'          => 'manage_partners',
        ];

        foreach ($routePermissions as $pattern => $permission) {
            if (fnmatch($pattern, $routeName)) {
                if ($user->can($permission)) {
                    return $next($request);
                }
                abort(403, 'Accès refusé.');
            }
        }

        // Route not in the restricted map — let the controller decide
        return $next($request);
    }
}