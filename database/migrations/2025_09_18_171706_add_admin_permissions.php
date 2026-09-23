<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Créer les permissions admin
        $adminPermissions = [
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'view_admin_notifications',
            'create_admin_notifications',
            'edit_admin_notifications',
            'delete_admin_notifications',
            'view_settings',
            'edit_settings',
            'view_analytics',
            'view_financial_transactions',
            'create_financial_transactions',
            'edit_financial_transactions',
            'delete_financial_transactions',
            'view_accounts',
            'create_accounts',
            'edit_accounts',
            'delete_accounts',
            'manage_commission_plans',
            'manage_pricing_plans',
            'manage_stations',
            'manage_integrators',
            'manage_partners',
            'view_audit_logs',
            'export_data',
            'import_data',
            'manage_system_settings',
            'view_dashboard',
            'manage_user_roles',
            'manage_permissions',
        ];

        // Créer les permissions
        foreach ($adminPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        // Assigner toutes les permissions au rôle admin
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($adminPermissions);

        // Assigner toutes les permissions à l'utilisateur admin (ID 1)
        $adminUser = \App\Models\User::find(1);
        if ($adminUser) {
            $adminUser->assignRole('admin');
            $adminUser->givePermissionTo($adminPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les permissions (optionnel)
        $adminPermissions = [
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'view_admin_notifications',
            'create_admin_notifications',
            'edit_admin_notifications',
            'delete_admin_notifications',
            'view_settings',
            'edit_settings',
            'view_analytics',
            'view_financial_transactions',
            'create_financial_transactions',
            'edit_financial_transactions',
            'delete_financial_transactions',
            'view_accounts',
            'create_accounts',
            'edit_accounts',
            'delete_accounts',
            'manage_commission_plans',
            'manage_pricing_plans',
            'manage_stations',
            'manage_integrators',
            'manage_partners',
            'view_audit_logs',
            'export_data',
            'import_data',
            'manage_system_settings',
            'view_dashboard',
            'manage_user_roles',
            'manage_permissions',
        ];

        foreach ($adminPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }
};
