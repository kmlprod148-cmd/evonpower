<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integrator;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FixIntegratorsRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrators:fix-roles {--dry-run : Afficher ce qui sera fait sans modifier la base de données}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger les intégrateurs existants pour s\'assurer que tous les utilisateurs associés ont le rôle integrator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Mode dry-run : aucune modification ne sera effectuée');
            $this->newLine();
        }

        $this->info('🔧 Recherche des intégrateurs sans utilisateur avec le rôle "integrator"...');
        $this->newLine();

        $integrators = Integrator::with('user')->get();
        $fixedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($integrators as $integrator) {
            // Si l'intégrateur a un user_id
            if ($integrator->user_id) {
                $user = User::find($integrator->user_id);
                
                if (!$user) {
                    $this->warn("⚠️  Intégrateur {$integrator->id}: utilisateur {$integrator->user_id} introuvable");
                    $errorCount++;
                    continue;
                }

                // Vérifier si l'utilisateur a le rôle integrator
                if (!$user->hasRole('integrator')) {
                    $this->info("📝 Intégrateur {$integrator->id} ({$integrator->name})");
                    $this->line("   Utilisateur: {$user->id} - {$user->name} ({$user->email})");
                    $this->line("   Statut: ❌ Pas de rôle 'integrator'");
                    
                    if (!$dryRun) {
                        try {
                            $user->assignRole('integrator');
                            
                            // Mettre à jour l'integrator_id si nécessaire
                            if (!$user->integrator_id) {
                                $user->update(['integrator_id' => $integrator->id]);
                            }
                            
                            // Assigner toutes les permissions nécessaires
                            \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
                            
                            $this->line("   ✅ Rôle 'integrator' et permissions assignés");
                            $fixedCount++;
                            
                            Log::info("Rôle 'integrator' et permissions assignés à l'utilisateur {$user->id} pour l'intégrateur {$integrator->id} via la commande fix-roles");
                        } catch (\Exception $e) {
                            $this->error("   ❌ Erreur: {$e->getMessage()}");
                            $errorCount++;
                        }
                    } else {
                        $this->line("   [DRY-RUN] Rôle 'integrator' et permissions seraient assignés");
                        $fixedCount++;
                    }
                    $this->newLine();
                } else {
                    // Vérifier aussi que l'integrator_id est correct
                    if ($user->integrator_id != $integrator->id) {
                        $this->info("📝 Intégrateur {$integrator->id} ({$integrator->name})");
                        $this->line("   Utilisateur: {$user->id} - {$user->name} ({$user->email})");
                        $this->line("   Statut: ⚠️  integrator_id incorrect (actuel: {$user->integrator_id}, attendu: {$integrator->id})");
                        
                        if (!$dryRun) {
                            try {
                                $user->update(['integrator_id' => $integrator->id]);
                                $this->line("   ✅ integrator_id corrigé");
                                $fixedCount++;
                            } catch (\Exception $e) {
                                $this->error("   ❌ Erreur: {$e->getMessage()}");
                                $errorCount++;
                            }
                        } else {
                            $this->line("   [DRY-RUN] integrator_id serait corrigé");
                            $fixedCount++;
                        }
                        $this->newLine();
                    } else {
                        $skippedCount++;
                    }
                }
            } else {
                // Intégrateur sans user_id - vérifier si des utilisateurs ont cet intégrateur comme integrator_id
                $usersWithIntegratorId = User::where('integrator_id', $integrator->id)
                    ->whereDoesntHave('roles', function ($query) {
                        $query->where('name', 'integrator');
                    })
                    ->get();
                
                foreach ($usersWithIntegratorId as $user) {
                    $this->info("📝 Intégrateur {$integrator->id} ({$integrator->name})");
                    $this->line("   Utilisateur: {$user->id} - {$user->name} ({$user->email})");
                    $this->line("   Statut: ⚠️  Utilisateur avec integrator_id mais sans rôle 'integrator'");
                    
                    if (!$dryRun) {
                        try {
                            $user->assignRole('integrator');
                            
                            // Mettre à jour le user_id de l'intégrateur si vide
                            if (!$integrator->user_id) {
                                $integrator->update(['user_id' => $user->id]);
                                $this->line("   ✅ user_id assigné à l'intégrateur");
                            }
                            
                            // Assigner toutes les permissions nécessaires
                            \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
                            
                            $this->line("   ✅ Rôle 'integrator' et permissions assignés");
                            $fixedCount++;
                            
                            Log::info("Rôle 'integrator' et permissions assignés à l'utilisateur {$user->id} pour l'intégrateur {$integrator->id} via la commande fix-roles");
                        } catch (\Exception $e) {
                            $this->error("   ❌ Erreur: {$e->getMessage()}");
                            $errorCount++;
                        }
                    } else {
                        $this->line("   [DRY-RUN] Rôle 'integrator' et permissions seraient assignés");
                        $fixedCount++;
                    }
                    $this->newLine();
                }
                
                if ($usersWithIntegratorId->isEmpty()) {
                    $skippedCount++;
                }
            }
        }

        $this->newLine();
        $this->info('📊 Résumé:');
        $this->line("   ✅ Corrigés: {$fixedCount}");
        $this->line("   ⏭️  Ignorés (déjà corrects): {$skippedCount}");
        if ($errorCount > 0) {
            $this->line("   ❌ Erreurs: {$errorCount}");
        }

        if ($dryRun && $fixedCount > 0) {
            $this->newLine();
            $this->warn('⚠️  Ceci était un dry-run. Pour appliquer les modifications, relancez la commande sans --dry-run');
        }

        // Vider le cache des permissions
        if (!$dryRun && $fixedCount > 0) {
            $this->newLine();
            $this->info('🔄 Nettoyage du cache des permissions...');
            try {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                $this->info('✅ Cache des permissions vidé');
            } catch (\Exception $e) {
                $this->warn("⚠️  Impossible de vider le cache des permissions: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
