<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Middleware pour la gestion des permissions SteVe Monitoring
 * 
 * Ce middleware vérifie que l'utilisateur a les permissions nécessaires
 * pour accéder aux fonctionnalités de monitoring SteVe
 */
class SteVeMonitoringMiddleware
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
            Log::warning('Tentative d\'accès non autorisé au monitoring SteVe', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->url()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Authentification requise.'
            ], 401);
        }

        $user = Auth::user();
        
        // Vérifier les permissions selon le rôle
        if (!$this->hasSteVePermissions($user)) {
            Log::warning('Tentative d\'accès au monitoring SteVe sans permissions', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'ip' => $request->ip(),
                'url' => $request->url()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Permissions insuffisantes pour accéder au monitoring SteVe.'
            ], 403);
        }

        // Ajouter des informations de contexte à la requête
        $request->merge([
            'steve_monitoring_user' => [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $this->getUserSteVePermissions($user)
            ]
        ]);

        // Log de l'accès autorisé
        Log::info('Accès autorisé au monitoring SteVe', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'url' => $request->url(),
            'method' => $request->method()
        ]);

        return $next($request);
    }

    /**
     * Vérifier si l'utilisateur a les permissions SteVe
     */
    protected function hasSteVePermissions($user): bool
    {
        // Vérifier les rôles autorisés
        $allowedRoles = ['admin', 'super_admin', 'operator', 'integrator'];
        $userRoles = $user->roles->pluck('name')->toArray();
        
        foreach ($userRoles as $role) {
            if (in_array($role, $allowedRoles)) {
                return true;
            }
        }

        // Vérifier les permissions spécifiques
        $requiredPermissions = [
            'steve.monitoring.view',
            'steve.monitoring.control',
            'steve.ocpp.commands'
        ];

        foreach ($requiredPermissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtenir les permissions SteVe de l'utilisateur
     */
    protected function getUserSteVePermissions($user): array
    {
        $permissions = [];
        
        // Permissions de base
        $permissions['view_monitoring'] = $user->can('steve.monitoring.view') || 
                                         $user->hasRole(['admin', 'super_admin', 'operator']);
        
        $permissions['control_charging_points'] = $user->can('steve.monitoring.control') || 
                                                 $user->hasRole(['admin', 'super_admin', 'operator']);
        
        $permissions['send_ocpp_commands'] = $user->can('steve.ocpp.commands') || 
                                           $user->hasRole(['admin', 'super_admin']);
        
        $permissions['view_logs'] = $user->can('steve.monitoring.logs') || 
                                   $user->hasRole(['admin', 'super_admin', 'operator']);
        
        $permissions['system_administration'] = $user->hasRole(['admin', 'super_admin']);

        return $permissions;
    }
}
