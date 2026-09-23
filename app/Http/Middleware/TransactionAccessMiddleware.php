<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class TransactionAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check both web and client guards
        $user = Auth::user() ?? Auth::guard('client')->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Les administrateurs et super-administrateurs ont tous les droits
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $next($request);
        }

        // Les autres rôles principaux ont accès aux transactions
        if ($user->hasRole(['integrator', 'operator', 'partner'])) {
            return $next($request);
        }

        // Les clients (rôle user ou client) peuvent voir leurs propres transactions
        // Le TransactionController filtre déjà pour n'afficher que les transactions de l'utilisateur
        if ($user->hasRole(['user', 'User', 'client'])) {
            return $next($request);
        }

        // Si l'utilisateur a la permission view_transactions ou view_own_transactions, l'autoriser
        if ($user->can('view_transactions') || $user->can('view_own_transactions') || $user->can('view_own_history')) {
            return $next($request);
        }

        // Refuser l'accès
        abort(403, 'Accès refusé. Vous n\'avez pas les permissions nécessaires pour accéder aux transactions.');
    }
}
