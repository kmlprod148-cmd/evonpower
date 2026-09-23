<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionService;

class FixIntegratorPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:fix-integrator 
                            {--user= : Email spécifique de l\'utilisateur à corriger}
                            {--dry-run : Afficher ce qui serait fait sans l\'exécuter}
                            {--verbose : Afficher les détails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les permissions des utilisateurs intégrateurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction des Permissions Intégrateur');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $verbose = $this->option('verbose');
        $specificUser = $this->option('user');

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
        }

        try {
            // 1. Vider les caches
            $this->info('1. Vidage des caches...');
            if (!$dryRun) {
                $this->clearCaches();
            }
            $this->info('   ✅ Caches vidés');

            // 2. Vérifier et créer les permissions
            $this->info('2. Vérification des permissions...');
            $permissions = $this->ensurePermissions($dryRun, $verbose);
            $this->info("   ✅ {$permissions} permissions vérifiées");

            // 3. Vérifier et créer le rôle intégrateur
            $this->info('3. Vérification du rôle intégrateur...');
            $role = $this->ensureIntegratorRole($dryRun, $verbose);
            $this->info("   ✅ Rôle intégrateur vérifié (ID: {$role->id})");

            // 4. Assigner les permissions au rôle
            $this->info('4. Attribution des permissions au rôle intégrateur...');
            $this->assignPermissionsToRole($role, $dryRun, $verbose);
            $this->info('   ✅ Permissions attribuées au rôle');

            // 5. Vérifier et corriger les utilisateurs
            $this->info('5. Vérification des utilisateurs intégrateurs...');
            $users = $this->fixIntegratorUsers($specificUser, $dryRun, $verbose);
            $this->info("   ✅ {$users} utilisateurs intégrateurs vérifiés");

            // 6. Vider le cache des permissions
            $this->info('6. Vidage du cache des permissions...');
            if (!$dryRun) {
                $this->clearPermissionCache();
            }
            $this->info('   ✅ Cache des permissions vidé');

            // 7. Test final
            $this->info('7. Test final des permissions...');
            $this->testPermissions($specificUser, $verbose);

            $this->newLine();
            $this->info('✅ Correction des permissions terminée avec succès!');

            if ($dryRun) {
                $this->warn('⚠️  Mode DRY-RUN - Aucune modification n\'a été effectuée');
                $this->info('Pour appliquer les corrections, relancez la commande sans --dry-run');
            } else {
                $this->info('🔄 Prochaines étapes:');
                $this->line('   1. Redémarrez votre serveur web');
                $this->line('   2. Testez l\'accès à la page de création de points de recharge');
                $this->line('   3. Vérifiez les logs Laravel si le problème persiste');
            }

        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            $this->error('Fichier: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }

    /**
     * Vider tous les caches Laravel
     */
    private function clearCaches()
    {
        Artisan::call('cache:clear');
        Artisan::call('permission:cache-reset');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    /**
     * S'assurer que toutes les permissions nécessaires existent
     */
    private function ensurePermissions($dryRun, $verbose)
    {
        $requiredPermissions = [
            'view_charging_points',
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
        ];

        $count = 0;
        foreach ($requiredPermissions as $permissionName) {
            if ($verbose) {
                $this->line("   Vérification de la permission: {$permissionName}");
            }

            if (!$dryRun) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
            }
            $count++;
        }

        return $count;
    }

    /**
     * S'assurer que le rôle intégrateur existe
     */
    private function ensureIntegratorRole($dryRun, $verbose)
    {
        if ($verbose) {
            $this->line('   Vérification du rôle intégrateur');
        }

        if (!$dryRun) {
            return Role::firstOrCreate([
                'name' => 'integrator',
                'guard_name' => 'web'
            ]);
        }

        // En mode dry-run, retourner un objet simulé
        $role = new Role();
        $role->id = 1;
        return $role;
    }

    /**
     * Assigner les permissions au rôle intégrateur
     */
    private function assignPermissionsToRole($role, $dryRun, $verbose)
    {
        $integratorPermissions = [
            'view_charging_points',
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
        ];

        if ($verbose) {
            $this->line('   Attribution des permissions au rôle intégrateur');
        }

        if (!$dryRun) {
            $permissions = Permission::whereIn('name', $integratorPermissions)->get();
            $role->syncPermissions($permissions);
        }
    }

    /**
     * Corriger les utilisateurs intégrateurs
     */
    private function fixIntegratorUsers($specificUser, $dryRun, $verbose)
    {
        $query = User::whereHas('roles', function ($query) {
            $query->where('name', 'integrator');
        });

        if ($specificUser) {
            $query->where('email', $specificUser);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('   ⚠️  Aucun utilisateur avec le rôle intégrateur trouvé');
            
            // Essayer de trouver l'utilisateur de test
            $testUser = User::where('email', 'integrator@demo.evonpower.com')->first();
            if ($testUser) {
                if ($verbose) {
                    $this->line("   Attribution du rôle intégrateur à l'utilisateur de test");
                }
                if (!$dryRun) {
                    $testUser->assignRole('integrator');
                }
                $users = collect([$testUser]);
            }
        }

        foreach ($users as $user) {
            if ($verbose) {
                $this->line("   Vérification de l'utilisateur: {$user->name} ({$user->email})");
            }

            // S'assurer que l'utilisateur a bien le rôle
            if (!$user->hasRole('integrator')) {
                if ($verbose) {
                    $this->line("     - Attribution du rôle intégrateur");
                }
                if (!$dryRun) {
                    $user->assignRole('integrator');
                }
            }

            // Vérifier les permissions
            if (!$user->can('create_charging_points')) {
                if ($verbose) {
                    $this->line("     - ⚠️  Permission create_charging_points manquante");
                    $this->line("     - Resynchronisation des permissions");
                }
                if (!$dryRun) {
                    $user->syncRoles(['integrator']);
                }
            } else {
                if ($verbose) {
                    $this->line("     - ✅ Permission create_charging_points OK");
                }
            }
        }

        return $users->count();
    }

    /**
     * Vider le cache des permissions
     */
    private function clearPermissionCache()
    {
        try {
            $permissionService = app(PermissionService::class);
            
            $users = User::whereHas('roles', function ($query) {
                $query->where('name', 'integrator');
            })->get();

            foreach ($users as $user) {
                $permissionService->invalidateUserPermissionsCache($user->id);
            }

            $permissionService->invalidateAllPermissionsCache();
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Erreur lors du vidage du cache: " . $e->getMessage());
            $this->info("   ℹ️  Utilisation d'Artisan à la place");
            
            // Alternative: vider le cache via Artisan
            Artisan::call('cache:clear');
            Artisan::call('permission:cache-reset');
        }
    }

    /**
     * Tester les permissions finales
     */
    private function testPermissions($specificUser, $verbose)
    {
        $query = User::whereHas('roles', function ($query) {
            $query->where('name', 'integrator');
        });

        if ($specificUser) {
            $query->where('email', $specificUser);
        }

        $users = $query->get();

        foreach ($users as $user) {
            if ($verbose) {
                $this->line("   Test pour {$user->name}:");
            }

            $hasRole = $user->hasRole('integrator');
            $hasPermission = $user->can('create_charging_points');

            if ($verbose) {
                $this->line("     - Rôle intégrateur: " . ($hasRole ? '✅ Oui' : '❌ Non'));
                $this->line("     - Permission create_charging_points: " . ($hasPermission ? '✅ Oui' : '❌ Non'));
            }

            if (!$hasRole || !$hasPermission) {
                $this->warn("     - ⚠️  Problème détecté pour {$user->name}");
            }
        }
    }
} 