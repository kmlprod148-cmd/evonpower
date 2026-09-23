<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class IntegratorPermissionMiddleware
{
    /**
     * Handle an incoming request with integrator-specific permissions
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Si aucun utilisateur n'est authentifié, rediriger vers la connexion
        if (!Auth::check()) {
            Log::warning('IntegratorPermissionMiddleware: User not authenticated, redirecting to login.');
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Log initial pour débogage
        Log::info("IntegratorPermissionMiddleware: Checking access", [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'permission' => $permission,
            'route' => $request->route()->getName(),
            'user_roles' => $user->getRoleNames()->toArray(),
            'integrator_id' => $user->integrator_id,
        ]);

        // Les administrateurs et super-administrateurs ont tous les droits
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower)) {
            Log::debug("IntegratorPermissionMiddleware: User {$user->id} is admin/super-admin, granting access.");
            return $next($request);
        }

        // Vérifier que l'utilisateur est un intégrateur (insensible à la casse)
        // Normaliser les rôles en minuscules pour la comparaison
        $isIntegrator = in_array('integrator', $userRolesLower);
        
        if (!$isIntegrator) {
            Log::warning("IntegratorPermissionMiddleware: User {$user->id} is not an integrator, denying access.", [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames()->toArray(),
                'user_roles_lower' => $userRolesLower,
                'permission' => $permission,
                'route' => $request->route()->getName(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès refusé. Seuls les intégrateurs peuvent accéder à cette ressource.'], 403);
            }

            abort(403, 'Accès refusé. Seuls les intégrateurs peuvent accéder à cette ressource.');
        }

        // Vérifier que l'intégrateur a l'intégrateur associé
        $integrator = $user->integrator;
        if (!$integrator) {
            Log::warning("IntegratorPermissionMiddleware: User {$user->id} is integrator but has no integrator record.", [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id,
                'permission' => $permission,
                'route' => $request->route()->getName(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès refusé. Intégrateur non configuré. Veuillez contacter votre administrateur.'], 403);
            }

            abort(403, 'Accès refusé. Intégrateur non configuré. Veuillez contacter votre administrateur.');
        }

        // Vérifier la permission spécifique
        // Utiliser hasPermissionTo qui vérifie via les rôles aussi
        $hasPermission = $user->hasPermissionTo($permission);
        
        if (!$hasPermission) {
            // Si la permission est refusée, vider le cache et réessayer une fois
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            cache()->forget('spatie.permission.cache');
            
            // Recharger l'utilisateur complètement
            $user->refresh();
            $user->load(['roles', 'roles.permissions', 'permissions']);
            
            // Réessayer avec hasPermissionTo
            $hasPermission = $user->hasPermissionTo($permission);
            
            // Si toujours pas, vérifier via can()
            if (!$hasPermission) {
                $hasPermission = $user->can($permission);
            }
            
            // Réessayer après vidage du cache
            if (!$hasPermission) {
                // Vérifier si le rôle a la permission mais pas l'utilisateur
                $integratorRole = \Spatie\Permission\Models\Role::where('name', 'integrator')->first();
                $roleHasPermission = $integratorRole ? $integratorRole->hasPermissionTo($permission) : false;
                
                // Dernière tentative : vérifier directement via les rôles
                $viaRoles = $user->getPermissionsViaRoles()->pluck('name')->toArray();
                $hasPermissionViaRoles = in_array($permission, $viaRoles);
                
                Log::warning("IntegratorPermissionMiddleware: User {$user->id} denied permission '{$permission}' even after cache clear.", [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'permission' => $permission,
                    'role_has_permission' => $roleHasPermission,
                    'has_permission_via_roles' => $hasPermissionViaRoles,
                    'user_direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
                    'user_permissions_via_roles' => $viaRoles,
                    'integrator_id' => $user->integrator_id,
                ]);
                
                // Si le rôle a la permission mais que l'utilisateur ne l'a pas, c'est un problème de cache/synchronisation
                if ($roleHasPermission && !$hasPermissionViaRoles) {
                    Log::error("IntegratorPermissionMiddleware: Role has permission but user doesn't - cache/sync issue!", [
                        'user_id' => $user->id,
                        'permission' => $permission,
                    ]);
                }
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => "Accès refusé. Permission '{$permission}' requise.",
                        'debug' => [
                            'user_id' => $user->id,
                            'user_roles' => $user->getRoleNames()->toArray(),
                            'required_permission' => $permission,
                            'role_has_permission' => $roleHasPermission,
                            'has_permission_via_roles' => $hasPermissionViaRoles,
                        ]
                    ], 403);
                }

                abort(403, "Accès refusé. Vous n'avez pas la permission '{$permission}' pour accéder à cette ressource.");
            }
        }

        Log::debug("IntegratorPermissionMiddleware: User {$user->id} granted permission '{$permission}'.");
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response): void
    {
        // Aucune action nécessaire après l'envoi de la réponse
    }
}
