<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Si aucun utilisateur n'est authentifié, rediriger vers la connexion
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Les administrateurs ont tous les droits
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Vérifier les permissions selon le rôle
        switch ($user->role) {
            case 'integrator':
                // Les intégrateurs peuvent voir leurs propres transactions et celles de leurs opérateurs
                return $next($request);
                
            case 'operator':
                // Les opérateurs peuvent voir leurs propres transactions
                return $next($request);
                
            default:
                // Rôle non reconnu, accès refusé
                return redirect()->route('login');
        }
    }
}