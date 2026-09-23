<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Middleware pour sécuriser l'accès à la gestion des clés API
 * Seuls les super-admins peuvent accéder à cette fonctionnalité
 */
class AdminApiKeysAccess
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
            Log::warning('Tentative d\'accès non autorisé aux clés API - utilisateur non authentifié', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();

        // Vérifier que l'utilisateur est un super-admin
        if (!$this->isSuperAdmin($user)) {
            Log::warning('Tentative d\'accès non autorisé aux clés API - utilisateur non super-admin', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return redirect()->back()->with('error', 'Accès refusé. Seuls les super-admins peuvent gérer les clés API.');
        }

        // Vérifier que l'utilisateur a les permissions nécessaires
        if (!$this->hasApiKeysPermission($user)) {
            Log::warning('Tentative d\'accès non autorisé aux clés API - permissions insuffisantes', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return redirect()->back()->with('error', 'Permissions insuffisantes pour gérer les clés API.');
        }

        // Log de l'accès autorisé pour audit
        Log::info('Accès autorisé aux clés API', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        return $next($request);
    }

    /**
     * Vérifie si l'utilisateur est un super-admin
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function isSuperAdmin($user): bool
    {
        // Vérifier par email (méthode simple)
        if ($user->email === 'admin@evon.com') {
            return true;
        }

        // Vérifier par rôle si le système de rôles est en place
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('super_admin') || $user->hasRole('admin');
        }

        // Vérifier par attribut is_admin si disponible
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        // Vérifier par attribut is_super_admin si disponible
        if (isset($user->is_super_admin) && $user->is_super_admin) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a les permissions pour gérer les clés API
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function hasApiKeysPermission($user): bool
    {
        // Vérifier les permissions spécifiques si le système de permissions est en place
        if (method_exists($user, 'can')) {
            return $user->can('manage-api-keys') || $user->can('admin.access');
        }

        // Par défaut, si l'utilisateur est super-admin, il a les permissions
        return $this->isSuperAdmin($user);
    }
}
