<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateClientUser
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('client')->check()) {
            return response()->json(['message' => 'Non authentifié (client)'], 401);
        }
        return $next($request);
    }
} 