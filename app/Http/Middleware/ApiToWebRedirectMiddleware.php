<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class ApiToWebRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('ApiToWebRedirectMiddleware: Checking request', [
            'is_api' => $request->is('api/*'),
            'accepts_html' => $request->acceptsHtml(),
            'user_id' => Auth::id(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
        ]);

        // Only apply to API routes when accessed from browser
        if (!$request->is('api/*') || !$request->acceptsHtml()) {
            Log::info('ApiToWebRedirectMiddleware: Skipping - not API or not accepting HTML');
            return $next($request);
        }

        $user = Auth::user();

        // Si l'utilisateur n'est pas admin ou n'a pas le droit, on ne redirige pas, on abort(403)
        if (!$user || !$user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Accès refusé.');
        }

        // Get the current path without 'api/' prefix
        $path = $request->path();
        $cleanPath = preg_replace('/^api\//', '', $path);

        // Remove v1 prefix if present
        $cleanPath = preg_replace('/^v1\//', '', $cleanPath);

        Log::info('ApiToWebRedirectMiddleware: Processing redirect for admin', [
            'original_path' => $path,
            'clean_path' => $cleanPath,
            'method' => $request->method()
        ]);

        // Define route mappings
        $routeMappings = [
            'integrators' => 'integrators.index',
            'groups' => 'groups.index',
            'partners' => 'partners.index',
            'charging-points' => 'charging-points.index',
            'admin/business-profiles' => 'admin.business-profiles.index',
            'admin/users' => 'admin.users.index',
        ];

        // Check for exact matches first
        Log::info('ApiToWebRedirectMiddleware: Checking exact matches', ['clean_path' => $cleanPath]);
        if (isset($routeMappings[$cleanPath])) {
            $targetRoute = $routeMappings[$cleanPath];
            Log::info('ApiToWebRedirectMiddleware: Exact match found', [
                'clean_path' => $cleanPath,
                'target_route' => $targetRoute
            ]);

            if (Route::has($targetRoute)) {
                Log::info('ApiToWebRedirectMiddleware: Target route exists, redirecting', ['target_route' => $targetRoute]);
                return redirect()->route($targetRoute);
            } else {
                Log::warning('ApiToWebRedirectMiddleware: Target route not found', ['target_route' => $targetRoute]);
            }
        }

        // Handle routes with IDs using regex
        $patterns = [
            'integrators/(\d+)' => 'integrators.show',
            'groups/(\d+)' => 'groups.show',
            'partners/(\d+)' => 'partners.show',
            'charging-points/(\d+)' => 'charging-points.show',
            'admin/business-profiles/(\d+)' => 'admin.business-profiles.show',
            'admin/users/(\d+)' => 'admin.users.show',
        ];

        // Handle routes with IDs using regex
        Log::info('ApiToWebRedirectMiddleware: Checking pattern matches', ['clean_path' => $cleanPath]);
        foreach ($patterns as $pattern => $targetRoute) {
            if (preg_match("#^{$pattern}$#", $cleanPath, $matches)) {
                $id = $matches[1];

                Log::info('ApiToWebRedirectMiddleware: Pattern match found', [
                    'pattern' => $pattern,
                    'clean_path' => $cleanPath,
                    'target_route' => $targetRoute,
                    'id' => $id
                ]);

                Log::info('ApiToWebRedirectMiddleware: Pattern match found', [
                    'pattern' => $pattern,
                    'target_route' => $targetRoute,
                    'id' => $id
                ]);

                if (Route::has($targetRoute)) {
                    Log::info('ApiToWebRedirectMiddleware: Target route exists, redirecting with ID', [
                        'target_route' => $targetRoute,
                        'id' => $id
                    ]);
                    // Pass the ID directly as the route expects
                    return redirect()->route($targetRoute, $id);
                } else {
                    Log::warning('ApiToWebRedirectMiddleware: Target route not found for pattern', [
                        'pattern' => $pattern,
                        'target_route' => $targetRoute
                    ]);
                }
            }
        }

        // No match found, continue with API request
        Log::info('ApiToWebRedirectMiddleware: No match found, continuing with API request');
        return $next($request);
    }
}