<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthenticateSystemUser
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('system')->check()) {
            return response()->json(['message' => 'Non authentifié (utilisateur système)'], 401);
        }
        return $next($request);
    }
} 