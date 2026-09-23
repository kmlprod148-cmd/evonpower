<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class HandlePolicyExceptions
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
        try {
            return $next($request);
        } catch (AuthorizationException $e) {
            // Log the authorization failure for debugging
            Log::warning('Authorization failed', [
                'user_id' => auth()->id(),
                'user_roles' => auth()->user()?->getRoleNames()->toArray() ?? [],
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'exception' => $e->getMessage()
            ]);

            // Store policy error message in session for display
            session()->flash('policy_error', $e->getMessage());

            // Return 403 error page
            return response()->view('errors.403', [], 403);
        }
    }
}
