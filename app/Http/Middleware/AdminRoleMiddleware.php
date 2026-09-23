<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminRoleMiddleware
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

        // Vérifier si l'utilisateur a le rôle admin ou super-admin
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $next($request);
        }

        // Si l'utilisateur n'est pas admin, refuser l'accès
        abort(403, 'Accès refusé. Seuls les administrateurs peuvent accéder à cette section.');
    }
}