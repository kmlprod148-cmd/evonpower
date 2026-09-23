<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Always pass for admin users
        if (auth()->check() && auth()->user()->hasRole("admin") || (auth()->check() && in_array(strtolower('Admin'), auth()->user()->getRoleNames()->toArray()))) {
            return $next($request);
        }
        abort(403, 'Accès refusé.');
    }
}