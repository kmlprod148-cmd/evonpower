<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationAuthenticationMiddleware
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
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            Log::warning('Tentative d\'acces aux notifications sans authentification', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise',
                'code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }

        $user = Auth::user();
        
        // Vérifier les permissions de manière plus souple - permettre à tous les utilisateurs authentifiés
        // Les notifications sont maintenant accessibles à tous les utilisateurs connectés
        // if (!$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'partner'])) {
        //     Log::warning('Tentative d\'acces aux notifications avec permissions insuffisantes', [
        //         'user_id' => $user->id,
        //         'user_roles' => $user->getRoleNames(),
        //         'url' => $request->fullUrl()
        //     ]);
        //     
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Permissions insuffisantes',
        //         'code' => 'INSUFFICIENT_PERMISSIONS'
        //     ], 403);
        // }

        return $next($request);
    }
}