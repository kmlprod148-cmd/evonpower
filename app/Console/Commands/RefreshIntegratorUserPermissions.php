<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\IntegratorPermissionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class RefreshIntegratorUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:refresh-user-permissions {user_id? : ID de l\'utilisateur intégrateur (optionnel, tous si non spécifié)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rafraîchit les permissions d\'un ou de tous les utilisateurs intégrateurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');

        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($userId) {
            // Traiter un utilisateur spécifique
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("Utilisateur avec ID {$userId} non trouvé.");
                return Command::FAILURE;
            }

            // Vérifier si l'utilisateur a le rôle intégrateur (case-insensitive)
            $userRoles = $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
            if (!in_array('integrator', $userRoles)) {
                $this->error("L'utilisateur {$userId} n'a pas le rôle intégrateur. Rôles actuels: " . implode(', ', $user->getRoleNames()->toArray()));
                return Command::FAILURE;
            }

            $this->info("Rafraîchissement des permissions pour l'utilisateur {$userId} ({$user->name})...");
            
            $success = IntegratorPermissionService::assignIntegratorPermissions($user);
            
            if ($success) {
                $this->info("✓ Permissions rafraîchies avec succès pour l'utilisateur {$userId}");
                
                // Vérifier les permissions clés
                $keyPermissions = ['view_charging_points', 'create_charging_points', 'edit_charging_points'];
                $this->info("\nVérification des permissions clés:");
                foreach ($keyPermissions as $permission) {
                    $has = $user->can($permission) ? '✓' : '✗';
                    $this->line("  {$has} {$permission}");
                }
            } else {
                $this->error("✗ Échec du rafraîchissement des permissions pour l'utilisateur {$userId}");
                return Command::FAILURE;
            }
        } else {
            // Traiter tous les intégrateurs (case-insensitive)
            $integratorRole = \Spatie\Permission\Models\Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
            if (!$integratorRole) {
                $this->error("Le rôle intégrateur n'existe pas.");
                return Command::FAILURE;
            }
            $integrators = User::role($integratorRole)->get();
            
            if ($integrators->isEmpty()) {
                $this->warn("Aucun utilisateur avec le rôle intégrateur trouvé.");
                return Command::SUCCESS;
            }

            $this->info("Rafraîchissement des permissions pour " . $integrators->count() . " intégrateur(s)...\n");

            $successCount = 0;
            $failCount = 0;

            foreach ($integrators as $integrator) {
                $this->line("Traitement de l'utilisateur {$integrator->id} ({$integrator->name})...");
                
                $success = IntegratorPermissionService::assignIntegratorPermissions($integrator);
                
                if ($success) {
                    $successCount++;
                    $this->info("  ✓ Permissions rafraîchies pour l'utilisateur {$integrator->id}");
                } else {
                    $failCount++;
                    $this->error("  ✗ Échec pour l'utilisateur {$integrator->id}");
                }
            }

            $this->info("\n✓ Succès: {$successCount}");
            if ($failCount > 0) {
                $this->error("✗ Échecs: {$failCount}");
            }
        }

        // Réinitialiser le cache une dernière fois
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info("\n✓ Cache des permissions réinitialisé");
        $this->info("✅ Opération terminée!");

        return Command::SUCCESS;
    }
}

