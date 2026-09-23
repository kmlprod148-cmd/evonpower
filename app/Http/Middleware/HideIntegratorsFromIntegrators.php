<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HideIntegratorsFromIntegrators
{
    /**
     * Middleware pour masquer la route /integrators aux comptes d'intégrateurs
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Si l'utilisateur est UNIQUEMENT un intégrateur (sans être admin), bloquer l'accès
        // Check for both lowercase and capitalized versions
        if ($user->hasRole(['integrator', 'Integrator']) && !$user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin'])) {
            Log::info('Integrator access blocked to /integrators route', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->getRoleNames(),
                'route' => $request->route()->getName(),
                'url' => $request->url()
            ]);

            // Rediriger vers le dashboard avec un message d'erreur
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'avez pas accès à cette section.');
        }

        // Si l'utilisateur est admin, super-admin ou super_admin, autoriser l'accès
        // Check for both lowercase and capitalized versions
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin'])) {
            Log::debug('Admin access granted to /integrators route', [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames(),
                'route' => $request->route()->getName()
            ]);
            return $next($request);
        }

        // Pour tous les autres rôles (admin, super-admin, etc.), autoriser l'accès
        Log::debug('Access granted to /integrators route', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames(),
            'route' => $request->route()->getName(),
            'is_admin' => $user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin']),
            'is_integrator' => $user->hasRole(['integrator', 'Integrator'])
        ]);

        return $next($request);
    }
}
