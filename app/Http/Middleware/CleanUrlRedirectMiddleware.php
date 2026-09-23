<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CleanUrlRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only redirect for admin user with ID 1
        if (!$user || $user->id !== 1) {
            return $next($request);
        }

        $path = $request->path();

        Log::info('CleanUrlRedirectMiddleware: Processing request', [
            'path' => $path,
            'method' => $request->method(),
            'user_id' => $user->id
        ]);

        Log::info('CleanUrlRedirectMiddleware: Current path', ['path' => $path]);

        // Define route mappings for clean URLs
        $routeMappings = [
            'integrators' => 'integrators.index',
            'groups' => 'groups.index',
            'partners' => 'partners.index',
            'charging-points' => 'charging-points.index',
            'business-profiles' => 'admin.business-profiles.index',
            'users' => 'admin.users.index',
        ];

        // Check for exact matches first
        if (isset($routeMappings[$path])) {
            $targetRoute = $routeMappings[$path];
            Log::info('CleanUrlRedirectMiddleware: Exact match found', [
                'target_route' => $targetRoute
            ]);

            if (Route::has($targetRoute)) {
                return redirect()->route($targetRoute);
            }
        }

        // Handle routes with IDs using regex
        $patterns = [
            'integrators/(\d+)' => 'integrators.show',
            'groups/(\d+)' => 'groups.show',
            'partners/(\d+)' => 'partners.show',
            'charging-points/(\d+)' => 'charging-points.show',
            'business-profiles/(\d+)' => 'admin.business-profiles.show',
            'users/(\d+)' => 'admin.users.show',
        ];

        foreach ($patterns as $pattern => $targetRoute) {
            if (preg_match("#^{$pattern}$#", $path, $matches)) {
                $id = $matches[1];

                Log::info('CleanUrlRedirectMiddleware: Pattern match found', [
                    'pattern' => $pattern,
                    'target_route' => $targetRoute,
                    'id' => $id
                ]);

                if (Route::has($targetRoute)) {
                    return redirect()->route($targetRoute, $id);
                }
            }
        }

        // No match found, continue with normal request
        return $next($request);
    }
}