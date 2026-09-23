<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null, 'client'] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                \Illuminate\Support\Facades\Log::info('RedirectIfAuthenticated: User authenticated for guard ' . $guard . '. Redirecting to dashboard.');

                // Rediriger le client vers son propre dashboard
                if ($guard === 'client' || (!Auth::guard('web')->check() && Auth::guard('client')->check())) {
                    return redirect()->route('dashboard.client');
                }

                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}