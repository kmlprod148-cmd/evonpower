<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class IntegratorPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les permissions pour les intégrateurs
        $permissions = [
            // Charging Points Management
            'view_integrator_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'show_charging_points',
            
            // Partners/Operators Management (CRUD complet)
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
            
            // Users/Operators Management (CRUD complet)
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'show_users',
            'activate_users',
            'deactivate_users',
            
            // Groups Management
            'view_integrator_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'show_groups',
            
            // Business Profiles Management
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'show_business_profiles',
            
            // Financial Management
            'view_transactions',
            'view_integrator_transactions',
            'approve_withdrawal_requests',
            'manage_pricing_plans',
            
            // Dashboard and Reports
            'access_dashboard',
            'view_reports',
            'export_data',
            
            // System Permissions
            'manage_system_permissions',
            'view_system_audit',
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assigner les permissions au rôle intégrateur
        $integratorRole = Role::firstOrCreate(['name' => 'integrator']);
        $integratorRole->syncPermissions($permissions);

        $this->command->info('Permissions pour les intégrateurs créées et assignées avec succès.');
    }
}
