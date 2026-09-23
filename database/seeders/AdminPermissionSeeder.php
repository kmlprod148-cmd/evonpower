<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define permissions
        $permissions = [
            'admin_access',
            'manage_all',
            'manage_all_business_profiles',
            'view_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
            'bulk_update_partners',
            'export_partners',
            'view_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'view_commission_plans',
            'create_commission_plans',
            'edit_commission_plans',
            'delete_commission_plans',
            'view_commissions',
            'view_integrator_profiles',
            'create_integrator_profiles',
            'edit_integrator_profiles',
            'delete_integrator_profiles',
            'view_partner_profiles',
            'create_partner_profiles',
            'edit_partner_profiles',
            'delete_partner_profiles',
            'view_plans',
            'create_plans',
            'edit_plans',
            'delete_plans',
            'apply_plans',
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'view_settings',
            'manage_settings',
            'view_activity_logs',
            'manage_commission_plans',
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            'view_transactions',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_withdrawal_requests',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Find or create the 'admin' role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Assign all permissions to the 'admin' role
        $adminRole->givePermissionTo($permissions);

        // Find the admin user (ID 1)
        $adminUser = User::find(1);

        // Assign the 'admin' role to the admin user
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }

        // Créer la permission si elle n'existe pas
        $deletePermission = Permission::firstOrCreate(['name' => 'delete charging points']);

        // Donner la permission au rôle admin
        $adminRole->givePermissionTo($deletePermission);

        // Donner toutes les permissions à tous les utilisateurs ayant le rôle admin
        $adminUsers = User::role('admin')->get();
        foreach ($adminUsers as $adminUser) {
            $adminUser->assignRole('admin');
            $adminUser->givePermissionTo($permissions);
        }
        
        // Log pour confirmation
        \Log::info('AdminPermissionSeeder: Permissions et rôles admin attribués à tous les admins existants.');

        // Donner le rôle admin à l'utilisateur admin@example.com
        $adminUser = User::where('email', 'admin@example.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
}