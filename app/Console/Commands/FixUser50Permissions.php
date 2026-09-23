<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\IntegratorPermissionService;
use Illuminate\Support\Facades\Log;

class FixUser50Permissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:fix-permissions {user_id=50 : L\'ID de l\'utilisateur à corriger}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger le rôle et les permissions d\'un utilisateur intégrateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("🔧 Correction de l'utilisateur ID {$userId}...");
        $this->newLine();

        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Utilisateur ID {$userId} non trouvé !");
            return 1;
        }

        $this->info("📋 Informations de l'utilisateur:");
        $this->line("   - ID: {$user->id}");
        $this->line("   - Nom: {$user->name}");
        $this->line("   - Email: {$user->email}");
        $this->line("   - Integrator ID: " . ($user->integrator_id ?? 'NULL'));
        
        $currentRoles = $user->getRoleNames();
        $this->line("   - Rôles actuels: " . ($currentRoles->isEmpty() ? 'AUCUN' : $currentRoles->implode(', ')));
        $this->newLine();

        try {
            // 1. S'assurer que le rôle integrator existe avec toutes les permissions
            $this->info('📝 Étape 1: Création/vérification du rôle integrator avec toutes les permissions...');
            IntegratorPermissionService::createIntegratorRole();
            $this->info('✅ Rôle integrator configuré');
            $this->newLine();

            // 2. Assigner le rôle integrator
            if (!$user->hasRole('integrator')) {
                $this->info('📝 Étape 2: Assignation du rôle integrator...');
                $user->assignRole('integrator');
                $this->info('✅ Rôle integrator assigné');
            } else {
                $this->info('✅ Rôle integrator déjà assigné');
            }
            $this->newLine();

            // 3. Vérifier et assigner l'intégrateur si nécessaire
            if ($user->integrator_id) {
                $integrator = $user->integrator;
                if ($integrator && !$integrator->user_id) {
                    $this->info('📝 Mise à jour de l\'intégrateur avec user_id...');
                    $integrator->update(['user_id' => $user->id]);
                    $this->info('✅ Integrator mis à jour');
                }
            }
            $this->newLine();

            // 4. Vider le cache des permissions AVANT d'assigner
            $this->info('📝 Étape 3: Vidage du cache des permissions...');
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            cache()->forget('spatie.permission.cache');
            $this->info('✅ Cache vidé');
            $this->newLine();

            // 5. Assigner toutes les permissions
            $this->info('📝 Étape 4: Assignation de toutes les permissions nécessaires...');
            $result = IntegratorPermissionService::assignIntegratorPermissions($user);
            
            if ($result) {
                $this->info('✅ Toutes les permissions assignées');
            } else {
                $this->warn('⚠️  Certaines permissions peuvent être manquantes');
            }
            $this->newLine();

            // 6. Vider à nouveau le cache après assignation
            $this->info('📝 Vidage final du cache...');
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            cache()->forget('spatie.permission.cache');
            cache()->flush(); // Vider complètement le cache
            $this->info('✅ Cache vidé');
            $this->newLine();

            // 7. Recharger l'utilisateur complètement
            $user->refresh();
            $user->load(['roles', 'roles.permissions', 'permissions']);

            // 8. Vérifier les permissions
            $this->info('📝 Étape 5: Vérification des permissions...');
            $keyPermissions = [
                'view_users',      // CRITIQUE pour /integrator/operators
                'create_users',
                'edit_users',
                'show_users',
                'delete_users',
                'activate_users',
                'deactivate_users',
                'access_dashboard',
                'view_charging_points',
                'view_partners',
            ];

            $allOk = true;
            foreach ($keyPermissions as $permission) {
                // Vérifier avec hasPermissionTo ET can
                $hasPermissionTo = $user->hasPermissionTo($permission);
                $canPermission = $user->can($permission);
                $hasPermission = $hasPermissionTo || $canPermission;
                
                if ($hasPermission) {
                    $this->line("   ✅ {$permission}");
                } else {
                    $this->line("   ❌ {$permission} - MANQUANT");
                    $this->line("      - hasPermissionTo: " . ($hasPermissionTo ? 'OUI' : 'NON'));
                    $this->line("      - can: " . ($canPermission ? 'OUI' : 'NON'));
                    $allOk = false;
                }
            }
            $this->newLine();

            if ($allOk) {
                $this->info('✅ Toutes les permissions sont correctement assignées !');
            } else {
                $this->warn('⚠️  Certaines permissions sont manquantes.');
                $this->newLine();
                $this->info('🔍 Diagnostic:');
                $this->line('   - Vérifiez que le rôle "integrator" existe dans la table roles');
                $this->line('   - Vérifiez que les permissions existent dans la table permissions');
                $this->line('   - Vérifiez que role_has_permissions contient les bonnes associations');
                $this->line('   - Vérifiez que model_has_roles contient l\'association user-id => integrator');
            }

            $this->newLine();
            $this->info('🎉 Correction terminée !');
            $this->newLine();
            $this->info('📋 PROCHAINES ÉTAPES:');
            $this->line('   1. Déconnectez-vous et reconnectez-vous pour rafraîchir la session');
            $this->line('   2. Videz le cache de votre navigateur');
            $this->line("   3. Testez l'accès à /integrator/operators");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: {$e->getMessage()}");
            $this->error("Stack trace: " . $e->getTraceAsString());
            Log::error("Erreur lors de la correction de l'utilisateur 50: " . $e->getMessage());
            return 1;
        }
    }
}
