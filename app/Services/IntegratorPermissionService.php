<?php

namespace App\Services;

use App\Models\User;
use App\Models\Integrator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class IntegratorPermissionService
{
    /**
     * Assign all necessary permissions to an integrator user
     */
    public static function assignIntegratorPermissions(User $user): bool
    {
        try {
            // S'assurer que le rôle integrator existe avec toutes les permissions
            self::createIntegratorRole();

            // S'assurer que l'utilisateur a le rôle integrator (vérification insensible à la casse)
            $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $hasIntegratorRole = in_array('integrator', $userRolesLower);
            
            if (!$hasIntegratorRole) {
                // Trouver le rôle integrator existant (insensible à la casse)
                $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                if (!$integratorRole) {
                    // Créer le rôle avec le nom en minuscules
                    $integratorRole = Role::create(['name' => 'integrator', 'guard_name' => 'web']);
                }
                $user->assignRole($integratorRole);
                Log::info("Assigned integrator role to user: {$user->id}");
            }

            // Récupérer le rôle integrator (insensible à la casse) - déjà fait ci-dessus si assigné
            if (!isset($integratorRole) || !$integratorRole) {
                $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                if (!$integratorRole) {
                    Log::error("Integrator role not found - creating it");
                    self::createIntegratorRole();
                    $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                    
                    if (!$integratorRole) {
                        Log::error("Failed to create integrator role");
                        return false;
                    }
                }
            }

            // Vérifier que le rôle a toutes les permissions nécessaires
            $requiredPermissions = [
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
                'show_users',
                'activate_users',
                'deactivate_users',
                'view_charging_points',
                'view_integrator_charging_points',
                'view_partners',
                'view_integrator_partners',
                'access_dashboard'
            ];

            $missingPermissions = [];
            foreach ($requiredPermissions as $permission) {
                $perm = Permission::where('name', $permission)->first();
                if (!$perm) {
                    $missingPermissions[] = $permission;
                } elseif (!$integratorRole->hasPermissionTo($permission)) {
                    $missingPermissions[] = $permission;
                }
            }

            // Si des permissions manquent, les ajouter
            if (!empty($missingPermissions)) {
                Log::warning("Integrator role missing permissions: " . implode(', ', $missingPermissions));
                
                // Créer les permissions manquantes
                foreach ($missingPermissions as $permission) {
                    Permission::firstOrCreate(['name' => $permission]);
                }
                
                // Assigner toutes les permissions au rôle
                $integratorRole->givePermissionTo($missingPermissions);
                Log::info("Added missing permissions to integrator role");
            }

            // Vider le cache des permissions pour s'assurer que les permissions sont à jour
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Recharger l'utilisateur pour avoir les dernières permissions
            $user->refresh();
            $user->load('roles.permissions');

            // Vérifier que les permissions fonctionnent
            $keyPermissions = [
                'view_users',
                'view_charging_points',
                'view_integrator_charging_points',
                'view_partners',
                'view_integrator_partners',
                'access_dashboard'
            ];

            $allPermissionsWorking = true;
            foreach ($keyPermissions as $permission) {
                if (!$user->can($permission)) {
                    Log::warning("User {$user->id} missing permission: {$permission}");
                    $allPermissionsWorking = false;
                }
            }

            if ($allPermissionsWorking) {
                Log::info("All permissions working for integrator user: {$user->id}");
            } else {
                Log::warning("Some permissions missing for integrator user: {$user->id}");
                // Réessayer après avoir vidé le cache
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            return $allPermissionsWorking;

        } catch (\Exception $e) {
            Log::error("Error assigning integrator permissions: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Ensure all integrators have proper permissions
     */
    public static function fixAllIntegrators(): array
    {
        $results = [];
        $integrators = User::role('integrator')->get();

        foreach ($integrators as $integrator) {
            $results[$integrator->id] = self::assignIntegratorPermissions($integrator);
        }

        return $results;
    }

    /**
     * Create integrator role with all necessary permissions
     */
    public static function createIntegratorRole(): bool
    {
        try {
            $integratorPermissions = [
                // Charging Points Management (Full access)
                'view_charging_points',
                'create_charging_points',
                'edit_charging_points',
                'delete_charging_points',
                'show_charging_points',
                'manage_charging_points',
                'view_integrator_charging_points',
                'create_integrator_charging_points',
                'edit_integrator_charging_points',
                'delete_integrator_charging_points',
                
                // Partners/Operators Management (Full access)
                'view_partners',
                'create_partners',
                'edit_partners',
                'delete_partners',
                'show_partners',
                'activate_partners',
                'deactivate_partners',
                'manage_partners',
                'view_integrator_partners',
                'create_integrator_partners',
                'edit_integrator_partners',
                'delete_integrator_partners',
                
                // Users/Operators Management (Full access)
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
                'show_users',
                'activate_users',
                'deactivate_users',
                'manage_users',
                'view_operators',
                'create_operators',
                'edit_operators',
                'delete_operators',
                'view_integrator_operators',
                'create_integrator_operators',
                'edit_integrator_operators',
                'delete_integrator_operators',
                
                // Groups Management (Full access)
                'view_groups',
                'create_groups',
                'edit_groups',
                'delete_groups',
                'show_groups',
                'manage_groups',
                'view_integrator_groups',
                'create_integrator_groups',
                'edit_integrator_groups',
                'delete_integrator_groups',
                
                // Business Profiles Management (Full access)
                'view_business_profiles',
                'create_business_profiles',
                'edit_business_profiles',
                'delete_business_profiles',
                'show_business_profiles',
                'manage_business_profiles',
                
                // Financial Management (Full access)
                'view_transactions',
                'view_integrator_transactions',
                'approve_withdrawal_requests',
                'manage_pricing_plans',
                'manage_financials',
                
                // Pricing Plans Management (Full access)
                'view_pricing_plans',
                'create_pricing_plans',
                'edit_pricing_plans',
                'delete_pricing_plans',
                'manage_pricing_plans',
                
                // Dashboard and Reports (Full access)
                'access_dashboard',
                'view_integrator_dashboard',
                'view_reports',
                'export_data',
                'manage_reports',
                
                // Reservations Management (Full access)
                'view_reservations',
                'create_reservations',
                'edit_reservations',
                'delete_reservations',
                'manage_reservations',
                
                // Wallet Management (Full access)
                'view_wallet',
                'manage_wallet',
                'credit_wallet',
                'debit_wallet',
                
                // Settings Management (Full access)
                'view_settings',
                'edit_settings',
                'manage_settings',
                
                // Remote Control (Full access)
                'manage_remote_control',
                
                // System Permissions (Full access)
                'manage_system_permissions',
                'view_system_audit',
                
                // Analytics and Statistics (Full access)
                'view_analytics',
                'view_statistics',
                'export_analytics',
                
                // NOT INCLUDED: Integrator management permissions
                // 'view_integrators', 'create_integrators', 'edit_integrators', 'delete_integrators'
            ];

            // Create permissions if they don't exist
            foreach ($integratorPermissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            // Get or create integrator role (normaliser en minuscules)
            // Chercher d'abord si un rôle existe avec une casse différente
            $existingRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
            if (!$existingRole) {
                $integratorRole = Role::create(['name' => 'integrator', 'guard_name' => 'web']);
                Log::info("Created integrator role");
            } else {
                $integratorRole = $existingRole;
                // Si le rôle existe mais avec une casse différente, le renommer
                if (strtolower($integratorRole->name) !== 'integrator') {
                    $oldName = $integratorRole->name;
                    $integratorRole->name = 'integrator';
                    $integratorRole->save();
                    Log::info("Renamed role from '{$oldName}' to 'integrator'");
                }
            }
            
            // Assign all permissions to integrator role
            $integratorRole->syncPermissions($integratorPermissions);

            Log::info("Integrator role created/updated with all permissions");
            return true;

        } catch (\Exception $e) {
            Log::error("Error creating integrator role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify integrator permissions
     */
    public static function verifyIntegratorPermissions(User $user): array
    {
        $permissions = [
            'view_charging_points' => $user->can('view_charging_points'),
            'view_integrator_charging_points' => $user->can('view_integrator_charging_points'),
            'view_partners' => $user->can('view_partners'),
            'view_integrator_partners' => $user->can('view_integrator_partners'),
            'access_dashboard' => $user->can('access_dashboard'),
            'view_groups' => $user->can('view_groups'),
            'view_business_profiles' => $user->can('view_business_profiles'),
            'view_pricing_plans' => $user->can('view_pricing_plans'),
            'view_transactions' => $user->can('view_transactions'),
        ];

        return $permissions;
    }
}