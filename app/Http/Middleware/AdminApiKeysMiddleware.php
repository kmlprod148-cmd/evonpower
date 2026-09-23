<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware pour protéger les routes de gestion des clés API
 */
class AdminApiKeysMiddleware
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
        // Vérifier que l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Vérifier que l'utilisateur a les permissions admin
        $user = Auth::user();
        
        if (!$user->hasRole('admin') && !$user->hasPermissionTo('manage_api_keys')) {
            abort(403, 'Accès refusé. Permissions insuffisantes pour gérer les clés API.');
        }

        return $next($request);
    }
}
