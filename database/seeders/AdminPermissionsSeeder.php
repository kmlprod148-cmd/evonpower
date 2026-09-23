<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->command) {
            $this->command->info('Création des permissions admin...');
        } else {
            echo "Création des permissions admin...\n";
        }

        // Permissions admin complètes
        $adminPermissions = [
            // Reports
            'view_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            
            // Transactions
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            
            // Users
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Business Profiles
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            
            // Charging Points
            'view_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            
            // Reservations
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            
            // Admin Notifications
            'view_admin_notifications',
            'create_admin_notifications',
            'edit_admin_notifications',
            'delete_admin_notifications',
            
            // Settings
            'view_settings',
            'edit_settings',
            
            // Analytics
            'view_analytics',
            
            // Financial Transactions
            'view_financial_transactions',
            'create_financial_transactions',
            'edit_financial_transactions',
            'delete_financial_transactions',
            
            // Accounts
            'view_accounts',
            'create_accounts',
            'edit_accounts',
            'delete_accounts',
            
            // Stations
            'view_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            
            // Management
            'manage_commission_plans',
            'manage_pricing_plans',
            'manage_stations',
            'manage_integrators',
            'manage_partners',
            
            // System
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
            if ($this->command) {
                $this->command->info("✅ Permission créée: {$permissionName}");
            } else {
                echo "✅ Permission créée: {$permissionName}\n";
            }
        }

        // Créer ou récupérer le rôle admin
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Assigner toutes les permissions au rôle admin
        $adminRole->syncPermissions($adminPermissions);
        if ($this->command) {
            $this->command->info("✅ Permissions assignées au rôle admin");
        } else {
            echo "✅ Permissions assignées au rôle admin\n";
        }

        // Assigner le rôle admin à l'utilisateur admin (ID 1)
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->assignRole('admin');
            $adminUser->syncPermissions($adminPermissions);
            if ($this->command) {
                $this->command->info("✅ Rôle et permissions assignés à l'utilisateur admin: {$adminUser->name}");
            } else {
                echo "✅ Rôle et permissions assignés à l'utilisateur admin: {$adminUser->name}\n";
            }
        } else {
            if ($this->command) {
                $this->command->warn("⚠️ Utilisateur admin (ID 1) non trouvé");
            } else {
                echo "⚠️ Utilisateur admin (ID 1) non trouvé\n";
            }
        }

        if ($this->command) {
            $this->command->info("🎉 Permissions admin configurées avec succès!");
        } else {
            echo "🎉 Permissions admin configurées avec succès!\n";
        }
    }
}
