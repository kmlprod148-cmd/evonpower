<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\IntegratorPermissionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class SyncAllIntegratorsPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:sync-all-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise tous les intégrateurs pour s\'assurer qu\'ils ont le rôle et toutes les permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Synchronisation de tous les intégrateurs...');

        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // S'assurer que le rôle intégrateur existe avec toutes les permissions
        $this->info('Vérification du rôle intégrateur...');
        IntegratorPermissionService::createIntegratorRole();
        $this->info('✓ Rôle intégrateur créé/mis à jour');

        // Trouver tous les utilisateurs avec integrator_id
        $usersWithIntegratorId = User::whereNotNull('integrator_id')->get();
        
        // Trouver aussi les utilisateurs avec le rôle intégrateur
        $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
        $usersWithRole = $integratorRole ? User::role($integratorRole)->get() : collect();
        
        // Combiner les deux listes et supprimer les doublons
        $allIntegrators = $usersWithIntegratorId->merge($usersWithRole)->unique('id');
        
        $this->info("Trouvé " . $allIntegrators->count() . " intégrateur(s) à synchroniser\n");

        if ($allIntegrators->isEmpty()) {
            $this->warn("Aucun intégrateur trouvé.");
            return Command::SUCCESS;
        }

        $successCount = 0;
        $failCount = 0;
        $bar = $this->output->createProgressBar($allIntegrators->count());
        $bar->start();

        foreach ($allIntegrators as $user) {
            try {
                // S'assurer que l'utilisateur a le rôle intégrateur
                $userRoles = $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
                
                if (!in_array('integrator', $userRoles)) {
                    if (!$integratorRole) {
                        $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                        if (!$integratorRole) {
                            IntegratorPermissionService::createIntegratorRole();
                            $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
                        }
                    }
                    if ($integratorRole) {
                        $user->assignRole($integratorRole);
                        Log::info("Assigned integrator role to user {$user->id}");
                    }
                }

                // Assigner toutes les permissions
                $success = IntegratorPermissionService::assignIntegratorPermissions($user);
                
                if ($success) {
                    $successCount++;
                } else {
                    $failCount++;
                    Log::warning("Failed to assign permissions to user {$user->id}");
                }
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Error syncing integrator user {$user->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Réinitialiser le cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info("✓ Succès: {$successCount}");
        if ($failCount > 0) {
            $this->error("✗ Échecs: {$failCount}");
        }

        $this->info("\n✅ Synchronisation terminée!");
        
        return Command::SUCCESS;
    }
}

