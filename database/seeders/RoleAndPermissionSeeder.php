<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles and permissions
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('roles')->delete();
        DB::table('permissions')->delete();

        // Créer les rôles principaux
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $integratorRole = Role::create(['name' => 'integrator', 'guard_name' => 'web']);
        $operatorRole = Role::create(['name' => 'operator', 'guard_name' => 'web']);

        // Définition des permissions par module
        $permissions = [
            // Module Intégrateurs
            'view_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',

            // Module Partenaires/Opérateurs
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',

            // Module Bornes de recharge
            'view_all_charging_points',
            'view_integrator_charging_points',
            'view_own_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',

            // Module Groupes
            'view_all_groups',
            'view_integrator_groups',
            'view_own_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',

            // Module Transactions
            'view_all_transactions',
            'view_integrator_transactions',
            'view_own_transactions',

            // Module Rapports
            'view_all_reports',
            'view_integrator_reports',
            'view_own_reports',
            'create_reports',

            // Module Commissions
            'view_commission_settings',
            'manage_commissions',
            'admin_access'
        ];

        // Créer les permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Attribution des permissions aux rôles

        // Admin a toutes les permissions
        $adminRole->givePermissionTo(Permission::all());

        // Intégrateur a des permissions limitées
        $integratorRole->givePermissionTo([
            'view_integrator_charging_points',
            'view_own_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',

            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',

            'view_integrator_groups',
            'view_own_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',

            'view_integrator_transactions',
            'view_own_transactions',

            'view_integrator_reports',
            'view_own_reports',
            'create_reports'
        ]);

        // Opérateur a des permissions encore plus limitées
        $operatorRole->givePermissionTo([
            'view_own_charging_points',
            'create_charging_points',
            'edit_charging_points',

            'view_own_groups',
            'create_groups',
            'edit_groups',

            'view_own_transactions',

            'view_own_reports',
            'create_reports'
        ]);
    }
}