<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('Processing middleware for route: ' . $request->route()->getName());
        // Log the next middleware in the pipeline - this might require inspecting the pipeline object,
        // which is not directly accessible here. Let's log the route action instead.
        $routeAction = $request->route()->getActionName();
        Log::info('Route action: ' . $routeAction);

        return $next($request);
    }
}