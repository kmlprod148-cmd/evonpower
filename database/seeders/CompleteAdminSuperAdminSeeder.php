<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class CompleteAdminSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Création des utilisateurs Admin et Super Admin avec toutes les permissions...');

        DB::beginTransaction();

        try {
            // 1. Créer toutes les permissions
            $this->createAllPermissions();

            // 2. Créer les rôles
            $this->createRoles();

            // 3. Créer le Super Admin
            $superAdmin = $this->createSuperAdmin();

            // 4. Créer l'Admin
            $admin = $this->createAdmin();

            // 5. Créer d'autres utilisateurs de démonstration
            $this->createDemoUsers();

            DB::commit();

            $this->command->info('✅ Tous les utilisateurs ont été créés avec succès !');
            $this->displayCredentials();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur lors de la création des utilisateurs: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Créer toutes les permissions du système
     */
    private function createAllPermissions(): void
    {
        $this->command->info('📋 Création des permissions...');

        $permissions = [
            // Permissions Admin
            'admin_access',
            'manage_all',
            'view_admin_dashboard',
            'view_dashboard_stats',
            'view_dashboard_chart_data',

            // Gestion des utilisateurs
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_users',
            'activate_users',
            'deactivate_users',
            'show_users',

            // Gestion des rôles et permissions
            'roles.manage',
            'permissions.manage',
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'view_permissions',
            'create_permissions',
            'edit_permissions',
            'delete_permissions',

            // Gestion des intégrateurs
            'view_integrators',
            'view_all_integrators',
            'create_integrators',
            'edit_integrators',
            'delete_integrators',
            'manage_integrators',
            'activate_integrators',
            'deactivate_integrators',
            'show_integrators',
            'integrators.manage',

            // Gestion des partenaires
            'view_partners',
            'view_all_partners',
            'view_integrator_partners',
            'view_own_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'manage_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
            'bulk_update_partners',
            'export_partners',
            'partners.manage',

            // Gestion des opérateurs
            'view_operators',
            'create_operators',
            'edit_operators',
            'delete_operators',
            'manage_operators',
            'operators.manage',

            // Gestion des bornes de recharge
            'view_charging_points',
            'view_all_charging_points',
            'view_integrator_charging_points',
            'view_own_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'manage_charging_points',
            'show_charging_points',
            'activate_charging_points',
            'deactivate_charging_points',
            'charging_points.manage',
            'charging_points.view',

            // Gestion des QR codes
            'manage_qr_codes',
            'view_qr_codes',
            'create_qr_codes',
            'delete_qr_codes',

            // Gestion des stations
            'view_stations',
            'view_all_stations',
            'create_stations',
            'edit_stations',
            'delete_stations',
            'manage_stations',
            'show_stations',

            // Gestion des groupes
            'view_groups',
            'view_all_groups',
            'view_integrator_groups',
            'view_own_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'manage_groups',
            'show_groups',

            // Gestion des réservations
            'view_reservations',
            'view_all_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'manage_reservations',
            'confirm_reservations',
            'cancel_reservations',

            // Gestion des transactions
            'view_transactions',
            'view_all_transactions',
            'view_integrator_transactions',
            'view_own_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            'manage_transactions',
            'transactions.view',
            'transactions.manage',

            // Gestion des profils business
            'view_business_profiles',
            'view_all_business_profiles',
            'view_integrator_profiles',
            'view_partner_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'show_business_profiles',
            'manage_all_business_profiles',
            'create_integrator_profiles',
            'edit_integrator_profiles',
            'delete_integrator_profiles',
            'create_partner_profiles',
            'edit_partner_profiles',
            'delete_partner_profiles',
            'business_profiles.manage',
            'business_profiles.view',

            // Gestion des commissions
            'view_commissions',
            'view_commission_settings',
            'view_commission_plans',
            'create_commission_plans',
            'edit_commission_plans',
            'delete_commission_plans',
            'manage_commissions',

            // Gestion des plans tarifaires
            'view_plans',
            'view_pricing_plans',
            'create_plans',
            'edit_plans',
            'delete_plans',
            'apply_plans',

            // Gestion des rapports
            'view_reports',
            'view_all_reports',
            'view_integrator_reports',
            'view_own_reports',
            'create_reports',
            'edit_reports',
            'delete_reports',
            'export_reports',
            'generate_reports',
            'reports.generate',

            // Gestion des paramètres
            'view_settings',
            'edit_settings',
            'manage_settings',
            'settings.manage',

            // Gestion du wallet
            'view_wallet',
            'manage_wallet',
            'wallet.manage',
            'wallet.view',

            // Gestion des sessions de recharge
            'view_sessions',
            'create_sessions',
            'edit_sessions',
            'delete_sessions',
            'manage_sessions',
            'sessions.manage',
            'sessions.create',

            // Gestion des packs de crédit
            'view_credit_packs',
            'create_credit_packs',
            'edit_credit_packs',
            'delete_credit_packs',
            'manage_credit_packs',

            // Gestion des logs d'audit
            'view_audit_logs',
            'audit_logs.view',

            // Gestion des paiements
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
            'manage_payments',

            // Gestion des virements bancaires
            'view_wire_transfers',
            'create_wire_transfers',
            'approve_wire_transfers',
            'reject_wire_transfers',

            // Gestion des TVA
            'view_vat_rates',
            'create_vat_rates',
            'edit_vat_rates',
            'delete_vat_rates',

            // Autres permissions spéciales
            'impersonate_users',
            'view_system_logs',
            'manage_system',
            'backup_system',
            'restore_system',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('   ✅ ' . count($permissions) . ' permissions créées');
    }

    /**
     * Créer les rôles
     */
    private function createRoles(): void
    {
        $this->command->info('👥 Création des rôles...');

        $roles = [
            'super_admin',
            'admin',
            'integrator',
            'partner',
            'operator',
            'client',
            'user',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('   ✅ ' . count($roles) . ' rôles créés');
    }

    /**
     * Créer le Super Admin avec toutes les permissions
     */
    private function createSuperAdmin(): User
    {
        $this->command->info('👑 Création du Super Admin...');

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@evonpower.com'],
            [
                'name' => 'Super Administrateur',
                'password' => Hash::make('SuperAdmin2024!'),
                'email_verified_at' => now(),
                'is_active' => true,
                'balance' => 0.00,
                'currency' => 'EUR',
                'language' => 'fr',
                'timezone' => 'Africa/Casablanca',
            ]
        );

        // Assigner le rôle super_admin
        if (!$superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole('super_admin');
        }

        // Donner TOUTES les permissions au super admin
        $superAdmin->givePermissionTo(Permission::all());

        $this->command->info('   ✅ Super Admin créé avec TOUTES les permissions');

        return $superAdmin;
    }

    /**
     * Créer l'Admin avec les permissions admin
     */
    private function createAdmin(): User
    {
        $this->command->info('🛡️ Création de l\'Admin...');

        $admin = User::updateOrCreate(
            ['email' => 'admin@evonpower.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('Admin2024!'),
                'email_verified_at' => now(),
                'is_active' => true,
                'balance' => 0.00,
                'currency' => 'EUR',
                'language' => 'fr',
                'timezone' => 'Africa/Casablanca',
            ]
        );

        // Assigner le rôle admin
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Donner les permissions admin (presque toutes sauf les permissions super sensibles)
        $adminPermissions = Permission::whereNotIn('name', [
            'impersonate_users',
            'backup_system',
            'restore_system',
        ])->get();

        $admin->syncPermissions($adminPermissions);

        $this->command->info('   ✅ Admin créé avec les permissions administrateur');

        return $admin;
    }

    /**
     * Créer les utilisateurs de démonstration
     */
    private function createDemoUsers(): void
    {
        $this->command->info('🎭 Création des utilisateurs de démonstration...');

        // First create an integrator that can be used by operator
        $integratorUser = User::updateOrCreate(
            ['email' => 'integrator@evonpower.com'],
            [
                'name' => 'Intégrateur Démo',
                'password' => Hash::make('Integrator2024!'),
                'email_verified_at' => now(),
                'is_active' => true,
                'balance' => 100.00,
                'currency' => 'EUR',
                'language' => 'fr',
                'timezone' => 'Africa/Casablanca',
            ]
        );

        if (!$integratorUser->hasRole('integrator')) {
            $integratorUser->assignRole('integrator');
        }

        $this->command->info("   👤 integrator: integrator@evonpower.com");

        // Create actual Integrator record for operator validation
        // First get the admin user to be the creator
        $adminUser = User::where('email', 'admin@evonpower.com')->first();
        
        $integrator = \App\Models\Integrator::firstOrCreate(
            ['email' => 'integrator@evonpower.com'],
            [
                'name' => 'Intégrateur Démo',
                'email' => 'integrator@evonpower.com',
                'phone' => '+212600000000',
                'address' => 'Demo Address',
                'city' => 'Casablanca',
                'country' => 'Morocco',
                'is_active' => true,
                'created_by' => $adminUser ? $adminUser->id : 1,
            ]
        );

        // Update integrator user to link to integrator
        $integratorUser->integrator_id = $integrator->id;
        $integratorUser->save();

        $demoUsers = [
            [
                'email' => 'demo.admin@evonpower.com',
                'name' => 'Admin Démo',
                'password' => 'Demo2024!',
                'role' => 'admin',
            ],
            [
                'email' => 'partner@evonpower.com',
                'name' => 'Partenaire Démo',
                'password' => 'Partner2024!',
                'role' => 'partner',
            ],
            [
                'email' => 'operator@evonpower.com',
                'name' => 'Opérateur Démo',
                'password' => 'Operator2024!',
                'role' => 'operator',
                'integrator_id' => $integrator->id,
                'created_by' => $integratorUser->id,
            ],
            [
                'email' => 'user@evonpower.com',
                'name' => 'Utilisateur Démo',
                'password' => 'User2024!',
                'role' => 'user',
            ],
        ];

        foreach ($demoUsers as $userData) {
            $userDataCreate = [
                'name' => $userData['name'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
                'is_active' => true,
                'balance' => 100.00,
                'currency' => 'EUR',
                'language' => 'fr',
                'timezone' => 'Africa/Casablanca',
            ];

            // Add integrator_id and created_by for operator
            if (isset($userData['integrator_id'])) {
                $userDataCreate['integrator_id'] = $userData['integrator_id'];
            }
            if (isset($userData['created_by'])) {
                $userDataCreate['created_by'] = $userData['created_by'];
            }

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userDataCreate
            );

            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            $this->command->info("   👤 {$userData['role']}: {$userData['email']}");
        }
    }

    /**
     * Afficher les identifiants créés
     */
    private function displayCredentials(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║         🔐 IDENTIFIANTS CRÉÉS AVEC SUCCÈS                   ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->info('');
        
        $this->command->info('┌──────────────────────────────────────────────────────────────┐');
        $this->command->info('│ 👑 SUPER ADMINISTRATEUR (Tous les droits)                   │');
        $this->command->info('├──────────────────────────────────────────────────────────────┤');
        $this->command->info('│ Email    : superadmin@evonpower.com                          │');
        $this->command->info('│ Password : SuperAdmin2024!                                   │');
        $this->command->info('│ Rôle     : super_admin                                       │');
        $this->command->info('│ Permissions : TOUTES (*)                                     │');
        $this->command->info('└──────────────────────────────────────────────────────────────┘');
        $this->command->info('');
        
        $this->command->info('┌──────────────────────────────────────────────────────────────┐');
        $this->command->info('│ 🛡️ ADMINISTRATEUR (Permissions admin)                        │');
        $this->command->info('├──────────────────────────────────────────────────────────────┤');
        $this->command->info('│ Email    : admin@evonpower.com                               │');
        $this->command->info('│ Password : Admin2024!                                        │');
        $this->command->info('│ Rôle     : admin                                             │');
        $this->command->info('│ Permissions : Gestion complète du système                    │');
        $this->command->info('└──────────────────────────────────────────────────────────────┘');
        $this->command->info('');
        
        $this->command->info('┌──────────────────────────────────────────────────────────────┐');
        $this->command->info('│ 🎭 UTILISATEURS DE DÉMONSTRATION                             │');
        $this->command->info('├──────────────────────────────────────────────────────────────┤');
        $this->command->info('│ Admin Démo       : demo.admin@evonpower.com / Demo2024!      │');
        $this->command->info('│ Intégrateur      : integrator@evonpower.com / Integrator2024!│');
        $this->command->info('│ Partenaire       : partner@evonpower.com / Partner2024!      │');
        $this->command->info('│ Opérateur        : operator@evonpower.com / Operator2024!    │');
        $this->command->info('│ Utilisateur      : user@evonpower.com / User2024!            │');
        $this->command->info('└──────────────────────────────────────────────────────────────┘');
        $this->command->info('');
        
        $this->command->info('✅ Tous les comptes sont actifs et prêts à l\'utilisation !');
        $this->command->info('');
        
        // Afficher les statistiques
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        $totalUsers = User::count();
        
        $this->command->info('📊 STATISTIQUES:');
        $this->command->info("   • Permissions créées : {$totalPermissions}");
        $this->command->info("   • Rôles créés        : {$totalRoles}");
        $this->command->info("   • Utilisateurs créés : {$totalUsers}");
        $this->command->info('');
    }
}

