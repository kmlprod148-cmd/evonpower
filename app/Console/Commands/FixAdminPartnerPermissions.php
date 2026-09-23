<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class FixAdminPartnerPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:fix-admin-partners 
                            {--create-admin : Créer un utilisateur admin par défaut si aucun n\'existe}
                            {--dry-run : Afficher ce qui serait fait sans l\'exécuter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les permissions d\'administrateur pour créer des partenaires';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction des Permissions Admin pour les Partenaires');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $createAdmin = $this->option('create-admin');

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
        }

        try {
            // 1. Vider les caches
            $this->info('1. Vidage des caches...');
            if (!$dryRun) {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }
            $this->info('   ✅ Caches vidés');
            $this->newLine();

            // 2. Créer le rôle operator s'il n'existe pas
            $this->info('2. Création du rôle operator...');
            if (!$dryRun) {
                $operatorRole = Role::firstOrCreate([
                    'name' => 'operator',
                    'guard_name' => 'web'
                ]);
                $this->info("   ✅ Rôle operator créé/vérifié (ID: {$operatorRole->id})");
            } else {
                $this->info('   ✅ Rôle operator sera créé/vérifié');
            }
            $this->newLine();

            // 3. Créer les permissions manquantes pour les partenaires
            $this->info('3. Création des permissions pour les partenaires...');
            $partnerPermissions = [
                'view_partners',
                'create_partners', 
                'edit_partners',
                'delete_partners',
                'view_own_partner',
                'edit_own_partner',
                'manage_partners'
            ];

            $createdPermissions = [];
            foreach ($partnerPermissions as $permissionName) {
                if (!$dryRun) {
                    $permission = Permission::firstOrCreate([
                        'name' => $permissionName,
                        'guard_name' => 'web'
                    ]);
                    
                    if ($permission->wasRecentlyCreated) {
                        $createdPermissions[] = $permissionName;
                    }
                } else {
                    $existingPermission = Permission::where('name', $permissionName)->first();
                    if (!$existingPermission) {
                        $createdPermissions[] = $permissionName;
                    }
                }
            }
            
            if (!empty($createdPermissions)) {
                $this->info("   ✅ Permissions à créer: " . implode(', ', $createdPermissions));
            } else {
                $this->info("   ✅ Toutes les permissions existent déjà");
            }
            $this->newLine();

            // 4. Attribuer les permissions au rôle admin
            $this->info('4. Attribution des permissions au rôle admin...');
            if (!$dryRun) {
                $adminRole = Role::where('name', 'admin')->first();
                
                if (!$adminRole) {
                    $this->warn("   ⚠️  Rôle admin non trouvé, création...");
                    $adminRole = Role::create([
                        'name' => 'admin',
                        'guard_name' => 'web'
                    ]);
                }
                
                // Donner toutes les permissions au rôle admin
                $allPermissions = Permission::all();
                $adminRole->syncPermissions($allPermissions);
                $this->info("   ✅ Rôle admin a maintenant " . $allPermissions->count() . " permissions");
            } else {
                $this->info("   ✅ Rôle admin recevra toutes les permissions");
            }
            $this->newLine();

            // 5. Attribuer les permissions au rôle super-admin s'il existe
            $this->info('5. Vérification du rôle super-admin...');
            if (!$dryRun) {
                $superAdminRole = Role::where('name', 'super_admin')->first();
                
                if ($superAdminRole) {
                    $allPermissions = Permission::all();
                    $superAdminRole->syncPermissions($allPermissions);
                    $this->info("   ✅ Rôle super-admin mis à jour avec " . $allPermissions->count() . " permissions");
                } else {
                    $this->info("   ℹ️  Rôle super-admin non trouvé (normal)");
                }
            } else {
                $this->info("   ✅ Rôle super-admin sera mis à jour s'il existe");
            }
            $this->newLine();

            // 6. Attribuer les permissions appropriées au rôle operator
            $this->info('6. Attribution des permissions au rôle operator...');
            $operatorPermissions = [
                'view_own_charging_points',
                'edit_own_charging_points', 
                'view_own_groups',
                'edit_own_groups',
                'view_own_transactions',
                'access_basic_dashboard',
                'view_own_partner',
                'edit_own_partner'
            ];
            
            if (!$dryRun) {
                $operatorPermissionModels = Permission::whereIn('name', $operatorPermissions)->get();
                $operatorRole = Role::where('name', 'operator')->first();
                if ($operatorRole) {
                    $operatorRole->syncPermissions($operatorPermissionModels);
                    $this->info("   ✅ Rôle operator a maintenant " . $operatorPermissionModels->count() . " permissions");
                }
            } else {
                $this->info("   ✅ Rôle operator recevra les permissions appropriées");
            }
            $this->newLine();

            // 7. Vérifier les utilisateurs admin existants
            $this->info('7. Vérification des utilisateurs admin...');
            $adminUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'super_admin']);
            })->get();
            
            if ($adminUsers->count() > 0) {
                $this->info("   ✅ " . $adminUsers->count() . " utilisateur(s) admin trouvé(s):");
                foreach ($adminUsers as $user) {
                    $this->line("      - ID: {$user->id}, Email: {$user->email}, Rôles: " . $user->getRoleNames()->implode(', '));
                }
            } else {
                $this->warn("   ⚠️  Aucun utilisateur admin trouvé");
            }
            $this->newLine();

            // 8. Créer un utilisateur admin par défaut si demandé
            if ($createAdmin && $adminUsers->count() == 0) {
                $this->info('8. Création d\'un utilisateur admin par défaut...');
                if (!$dryRun) {
                    $adminUser = User::create([
                        'name' => 'Administrateur',
                        'email' => 'admin@evonpower.com',
                        'password' => bcrypt('password123'),
                        'email_verified_at' => now()
                    ]);
                    
                    $adminUser->assignRole('admin');
                    $this->info("   ✅ Utilisateur admin créé: admin@evonpower.com (mot de passe: password123)");
                    $this->warn("   ⚠️  IMPORTANT: Changez le mot de passe après la première connexion!");
                } else {
                    $this->info("   ✅ Utilisateur admin sera créé: admin@evonpower.com");
                }
                $this->newLine();
            }

            $this->info('🎉 Correction terminée avec succès!');
            $this->line('================================');
            $this->line('Le rôle operator a été créé et les permissions admin ont été configurées.');
            $this->line('Vous pouvez maintenant créer des partenaires en tant qu\'administrateur.');
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }
}
