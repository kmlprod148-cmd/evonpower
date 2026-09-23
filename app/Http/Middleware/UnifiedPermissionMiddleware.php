<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UnifiedPermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Check both web and client guards
        $user = Auth::user() ?? Auth::guard('client')->user();
        
        // Si aucun utilisateur n'est authentifié, rediriger vers la connexion
        if (!$user) {
            Log::warning('UnifiedPermissionMiddleware: User not authenticated, redirecting to login.');
            return redirect()->route('login');
        }

        Log::info('UnifiedPermissionMiddleware - START', [
            'user_id' => $user->id,
            'user_roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : ['client'],
            'permissions_required' => $permissions,
            'url' => $request->path()
        ]);

        // Special handling for ClientUser (client guard)
        if (get_class($user) === 'App\Models\ClientUser') {
            Log::info("UnifiedPermissionMiddleware: ClientUser detected, granting access for transaction viewing.");
            return $next($request);
        }

        // Les administrateurs et super-administrateurs ont tous les droits
        // Check for both lowercase and capitalized versions
        $isAdmin = $user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin']);
        Log::info('UnifiedPermissionMiddleware - Admin check', [
            'user_id' => $user->id,
            'isAdmin' => $isAdmin,
            'user_roles' => $user->getRoleNames()->toArray()
        ]);

        if ($isAdmin) {
            Log::info("UnifiedPermissionMiddleware: User {$user->id} is admin/super-admin, granting access.");
            return $next($request);
        }

        // Vérifier les opérateurs tôt pour les permissions de vue et création (admin reservations/transactions/charging points)
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isOperator = in_array('operator', $userRolesLower);
        
        if ($isOperator) {
            // Permissions pour voir les transactions et réservations (statistiques opérateur)
            $operatorViewPermissions = [
                'view_admin_transactions',
                'view_transactions',
                'view_admin_reservations',
                'view_reservations',
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $operatorViewPermissions)) {
                    Log::info("UnifiedPermissionMiddleware: Operator {$user->id} granted access to {$permission} via operator role fallback (early check).");
                    return $next($request);
                }
            }
            
            // Allow operators to create charging points (aligned with ChargingPointPolicy::create)
            if (in_array('create_charging_points', $permissions, true)) {
                Log::info("UnifiedPermissionMiddleware: Operator {$user->id} allowed to create charging points via operator role fallback (early check).");
                return $next($request);
            }
        }

        // Clients (user or client role) can view their own transactions
        $isUser = in_array('user', $userRolesLower) || in_array('client', $userRolesLower);
        if ($isUser) {
            $clientViewPermissions = [
                'view_transactions',
                'view_own_transactions',
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $clientViewPermissions) && $user->can('view_own_transactions')) {
                    Log::info("UnifiedPermissionMiddleware: User {$user->id} granted access to {$permission} via user role fallback.");
                    return $next($request);
                }
            }
        }

        // Operator fallback: allow edit/delete if policy on the specific charging point passes
        if ($user->hasRole('operator') && $user->integrator_id) {
            try {
                // Extract charging point ID from URL
                $chargingPointId = null;
                if (preg_match('#/charging-points/(\d+)#', $request->path(), $matches)) {
                    $chargingPointId = (int)$matches[1];
                }
                
                // Also try route parameters (for route model binding)
                if (!$chargingPointId) {
                    $route = $request->route();
                    if ($route) {
                        $routeParams = $route->parameters();
                        $param = $routeParams['charging_point'] 
                            ?? $routeParams['chargingPoint'] 
                            ?? $routeParams['charging_point_id']
                            ?? $routeParams['id']
                            ?? null;
                        
                        if ($param) {
                            if ($param instanceof \App\Models\ChargingPoint) {
                                $chargingPointId = $param->id;
                            } elseif (is_numeric($param)) {
                                $chargingPointId = (int)$param;
                            }
                        }
                    }
                }
                
                Log::info('UnifiedPermissionMiddleware: Operator fallback check', [
                    'user_id' => $user->id,
                    'user_integrator_id' => $user->integrator_id,
                    'charging_point_id' => $chargingPointId,
                    'request_path' => $request->path(),
                    'required_permissions' => $permissions
                ]);
                
                if ($chargingPointId && (in_array('edit_charging_points', $permissions, true) || in_array('delete_charging_points', $permissions, true))) {
                    // Load the charging point directly
                    $chargingPoint = \App\Models\ChargingPoint::find($chargingPointId);
                    
                    if ($chargingPoint) {
                        Log::info('UnifiedPermissionMiddleware: ChargingPoint loaded', [
                            'charging_point_id' => $chargingPoint->id,
                            'charging_point_integrator_id' => $chargingPoint->integrator_id,
                            'charging_point_user_id' => $chargingPoint->user_id,
                            'user_integrator_id' => $user->integrator_id,
                            'user_id' => $user->id
                        ]);
                        
                        // Check if operator can access this charging point (same integrator)
                        if ($chargingPoint->integrator_id === $user->integrator_id || $chargingPoint->user_id === $user->id) {
                            Log::info("UnifiedPermissionMiddleware: Operator {$user->id} authorized for charging point {$chargingPoint->id} (integrator match).");
                            return $next($request);
                        } else {
                            Log::warning("UnifiedPermissionMiddleware: Operator {$user->id} NOT authorized - integrator mismatch", [
                                'cp_integrator_id' => $chargingPoint->integrator_id,
                                'user_integrator_id' => $user->integrator_id,
                                'cp_user_id' => $chargingPoint->user_id
                            ]);
                        }
                    } else {
                        Log::warning("UnifiedPermissionMiddleware: ChargingPoint not found with ID: " . $chargingPointId);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('UnifiedPermissionMiddleware: operator policy fallback failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }


        // Vérifier chaque permission
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                Log::info("UnifiedPermissionMiddleware: User {$user->id} has permission '{$permission}', granting access.");
                return $next($request);
            }
            // Clients (user role) : view_public_charging_points donne accès à view_charging_points pour la liste
            if ($permission === 'view_charging_points' && $user->can('view_public_charging_points')) {
                Log::info("UnifiedPermissionMiddleware: User {$user->id} has view_public_charging_points, granting view_charging_points access.");
                return $next($request);
            }
            // Clients (user role) : view_own_transactions donne accès à view_transactions
            if ($permission === 'view_transactions' && $user->can('view_own_transactions')) {
                Log::info("UnifiedPermissionMiddleware: User {$user->id} has view_own_transactions, granting view_transactions access.");
                return $next($request);
            }

            // Fallback vers les policies (ex: viewAny/create sur ChargingPoint)
            try {
                $policyMap = [
                    'view_charging_points' => ['ability' => 'viewAny', 'class' => \App\Models\ChargingPoint::class],
                    'create_charging_points' => ['ability' => 'create', 'class' => \App\Models\ChargingPoint::class],
                    'create_partners' => ['ability' => 'create', 'class' => \App\Models\Partner::class],
                    'view_partners' => ['ability' => 'viewAny', 'class' => \App\Models\Partner::class],
                    'edit_partners' => ['ability' => 'update', 'class' => \App\Models\Partner::class],
                    'delete_partners' => ['ability' => 'delete', 'class' => \App\Models\Partner::class],
                    'view_business_profiles' => ['ability' => 'viewAny', 'class' => \App\Models\BusinessProfile::class],
                    'create_business_profiles' => ['ability' => 'create', 'class' => \App\Models\BusinessProfile::class],
                    'edit_business_profiles' => ['ability' => 'update', 'class' => \App\Models\BusinessProfile::class],
                    'delete_business_profiles' => ['ability' => 'delete', 'class' => \App\Models\BusinessProfile::class],
                    'manage_partner_rates' => ['ability' => 'managePartnerRates', 'class' => \App\Models\BusinessProfile::class],
                    'view_transactions' => ['ability' => 'viewAny', 'class' => \App\Models\Transaction::class],
                ];
                if (isset($policyMap[$permission])) {
                    $mapping = $policyMap[$permission];
                    if (\Illuminate\Support\Facades\Gate::allows($mapping['ability'], $mapping['class'])) {
                        Log::info("UnifiedPermissionMiddleware: Policy allows '{$mapping['ability']}' for {$mapping['class']}, granting access.");
                        return $next($request);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("UnifiedPermissionMiddleware: Policy fallback error for '{\$permission}': " . $e->getMessage());
            }
        }

        // Fallback spécial pour les intégrateurs et les permissions partenaires
        // Vérifier le rôle de manière insensible à la casse
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isIntegrator = in_array('integrator', $userRolesLower);
        
        if ($isIntegrator) {
            // Permissions partenaires - accès direct pour les intégrateurs
            $partnerPermissions = ['view_partners', 'create_partners', 'edit_partners', 'delete_partners'];
            foreach ($partnerPermissions as $partnerPermission) {
                if (in_array($partnerPermission, $permissions)) {
                    Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$partnerPermission} via role fallback.");
                    return $next($request);
                }
            }
            
            // Permissions de charging points - accès direct pour les intégrateurs
            $integratorChargingPointPermissions = [
                'view_charging_points',
                'create_charging_points',
                'edit_charging_points',
                'delete_charging_points',
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $integratorChargingPointPermissions)) {
                    Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$permission} via integrator role (direct access).");
                    return $next($request);
                }
            }
            
            // Fallback pour les permissions de charging points spécifiques aux intégrateurs (vérification de permissions spécifiques)
            $integratorChargingPointPermissionMap = [
                'view_charging_points' => 'view_integrator_charging_points',
                'create_charging_points' => 'create_integrator_charging_points',
                'edit_charging_points' => 'edit_integrator_charging_points',
                'delete_charging_points' => 'delete_integrator_charging_points',
            ];
            
            foreach ($permissions as $permission) {
                if (isset($integratorChargingPointPermissionMap[$permission])) {
                    $integratorPermission = $integratorChargingPointPermissionMap[$permission];
                    if ($user->can($integratorPermission)) {
                        Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$permission} via integrator permission {$integratorPermission}.");
                        return $next($request);
                    }
                }
            }
            
            // Fallback pour les permissions de business profiles spécifiques aux intégrateurs
            $integratorBusinessProfilePermissions = [
                'view_business_profiles',
                'create_business_profiles', 
                'edit_business_profiles',
                'delete_business_profiles',
                'manage_partner_rates'
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $integratorBusinessProfilePermissions)) {
                    Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$permission} via integrator role fallback.");
                    return $next($request);
                }
            }
            
            // Permissions pour gérer les opérateurs (utilisateurs créés par l'intégrateur)
            $integratorOperatorPermissions = [
                'manage_users',
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $integratorOperatorPermissions)) {
                    Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$permission} via integrator role fallback.");
                    return $next($request);
                }
            }
            
            // Permissions pour voir les transactions et réservations (statistiques intégrateur)
            $integratorViewPermissions = [
                'view_admin_transactions',
                'view_transactions',
                'view_admin_reservations',
                'view_reservations',
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $integratorViewPermissions)) {
                    Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$permission} via integrator role fallback.");
                    return $next($request);
                }
            }
            
            // Permissions pour les commissions (pour les intégrateurs)
            $integratorCommissionPermissions = [
                'view_commission_settings',
                'create_commission_plans',
                'edit_commission_plans',
                'delete_commission_plans',
            ];
            
            foreach ($permissions as $permission) {
                if (in_array($permission, $integratorCommissionPermissions)) {
                    Log::info("UnifiedPermissionMiddleware: Integrator {$user->id} granted access to {$permission} via integrator role fallback.");
                    return $next($request);
                }
            }
        }


        // Si aucune permission n'est accordée, retourner une erreur 403
        Log::warning("UnifiedPermissionMiddleware: User {$user->id} denied access. Required permissions: " . implode(', ', $permissions));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Accès refusé. Permissions insuffisantes.'], 403);
        }

        abort(403, 'Accès refusé. Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.');
    }
}