<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\IntegratorBusinessProfilePermissionService;

class IntegratorBusinessProfileMiddleware
{
    protected $permissionService;

    public function __construct(IntegratorBusinessProfilePermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request for integrator business profile permissions
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            Log::warning('IntegratorBusinessProfileMiddleware: User not authenticated');
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin : accès complet
        if ($user->hasRole(['admin', 'super_admin'])) {
            Log::info('Admin access granted to business profiles', [
                'user_id' => $user->id,
                'permission' => $permission
            ]);
            return $next($request);
        }

        // Vérifier que l'utilisateur est un intégrateur
        if (!$user->hasRole('integrator')) {
            Log::warning('Non-integrator user trying to access business profiles', [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames()->toArray(),
                'permission' => $permission
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès refusé. Seuls les intégrateurs peuvent accéder aux profils d\'entreprise.'], 403);
            }

            abort(403, 'Accès refusé. Seuls les intégrateurs peuvent accéder aux profils d\'entreprise.');
        }

        // Vérifier les permissions spécifiques
        $hasPermission = $this->checkPermission($user, $permission, $request);

        if (!$hasPermission) {
            Log::warning('Integrator lacks business profile permission', [
                'user_id' => $user->id,
                'integrator_id' => $user->integrator_id,
                'permission' => $permission,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Permission insuffisante pour cette action.'], 403);
            }

            abort(403, 'Permission insuffisante pour cette action.');
        }

        Log::info('Integrator business profile access granted', [
            'user_id' => $user->id,
            'integrator_id' => $user->integrator_id,
            'permission' => $permission
        ]);

        return $next($request);
    }

    /**
     * Vérifier une permission spécifique
     * 
     * @param User $user
     * @param string $permission
     * @param Request $request
     * @return bool
     */
    protected function checkPermission($user, string $permission, Request $request): bool
    {
        switch ($permission) {
            case 'create':
                return $this->permissionService->canCreateBusinessProfile($user);
                
            case 'edit':
                $businessProfileId = $request->route('businessProfile');
                if ($businessProfileId) {
                    $businessProfile = \App\Models\BusinessProfile::find($businessProfileId);
                    if ($businessProfile) {
                        return $this->permissionService->canEditBusinessProfile($user, $businessProfile);
                    }
                }
                return false;
                
            case 'delete':
                $businessProfileId = $request->route('businessProfile');
                if ($businessProfileId) {
                    $businessProfile = \App\Models\BusinessProfile::find($businessProfileId);
                    if ($businessProfile) {
                        return $this->permissionService->canDeleteBusinessProfile($user, $businessProfile);
                    }
                }
                return false;
                
            case 'view':
                // Les intégrateurs peuvent voir leurs profils et ceux de leur admin
                return true;
                
            default:
                // Vérifier les permissions Spatie
                return $user->can($permission);
        }
    }
}
