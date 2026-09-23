<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugRouteMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Log the current route
        Log::info('Route accessed', [
            'url' => $request->fullUrl(),
            'route' => $request->route() ? $request->route()->getName() ?? 'unnamed route' : 'no route',
            'user' => auth()->check() ? auth()->id() : 'guest'
        ]);
        
        $response = $next($request);
        
        // Log if this is a redirect response
        if ($response->isRedirect()) {
            Log::info('Redirecting', [
                'from' => $request->fullUrl(),
                'to' => $response->getTargetUrl(),
            ]);
        }
        
        return $response;
    }
}