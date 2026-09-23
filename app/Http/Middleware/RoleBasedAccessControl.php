<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredRole = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentification requise'
            ], 401);
        }

        // Vérifier si l'utilisateur a le rôle requis
        if ($requiredRole && !$this->hasRole($user, $requiredRole)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Rôle requis: ' . $requiredRole
            ], 403);
        }

        // Ajouter les informations de rôle à la requête
        $request->merge([
            'user_role' => $user->role,
            'user_id' => $user->id,
            'user_hierarchy' => $this->getUserHierarchy($user)
        ]);

        return $next($request);
    }

    /**
     * Vérifier si l'utilisateur a le rôle requis
     */
    private function hasRole($user, string $requiredRole): bool
    {
        return $user->role === $requiredRole;
    }

    /**
     * Obtenir la hiérarchie de l'utilisateur
     */
    private function getUserHierarchy($user): array
    {
        $hierarchy = [
            'user_id' => $user->id,
            'role' => $user->role,
            'can_view_all_transactions' => false,
            'can_configure_fees' => false,
            'can_view_all_business_profiles' => false,
            'accessible_user_ids' => [],
            'accessible_business_profile_ids' => []
        ];

        switch ($user->role) {
            case 'admin':
                $hierarchy['can_view_all_transactions'] = true;
                $hierarchy['can_configure_fees'] = true;
                $hierarchy['can_view_all_business_profiles'] = true;
                $hierarchy['accessible_user_ids'] = EnhancedUser::pluck('id')->toArray();
                $hierarchy['accessible_business_profile_ids'] = EnhancedBusinessProfile::pluck('id')->toArray();
                break;

            case 'integrator':
                $hierarchy['can_view_all_transactions'] = false;
                $hierarchy['can_configure_fees'] = true;
                $hierarchy['can_view_all_business_profiles'] = false;
                
                // Utilisateurs accessibles : l'intégrateur lui-même + ses opérateurs
                $hierarchy['accessible_user_ids'] = [$user->id];
                $hierarchy['accessible_user_ids'] = array_merge(
                    $hierarchy['accessible_user_ids'],
                    EnhancedUser::where('created_by', $user->id)->pluck('id')->toArray()
                );
                
                // Business profiles accessibles : ceux de l'intégrateur + ceux de ses opérateurs
                $hierarchy['accessible_business_profile_ids'] = EnhancedBusinessProfile::whereIn('owner_id', $hierarchy['accessible_user_ids'])->pluck('id')->toArray();
                break;

            case 'operator':
                $hierarchy['can_view_all_transactions'] = false;
                $hierarchy['can_configure_fees'] = false;
                $hierarchy['can_view_all_business_profiles'] = false;
                
                // Utilisateur accessible : seulement lui-même
                $hierarchy['accessible_user_ids'] = [$user->id];
                
                // Business profile accessible : seulement le sien
                if ($user->business_profile_id) {
                    $hierarchy['accessible_business_profile_ids'] = [$user->business_profile_id];
                }
                break;
        }

        return $hierarchy;
    }
}
