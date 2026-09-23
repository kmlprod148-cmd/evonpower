<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\IntegratorPermissionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class AssignIntegratorRoleToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:assign-role {user_id : ID de l\'utilisateur}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigne le rôle intégrateur et toutes les permissions à un utilisateur';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');

        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Utilisateur avec ID {$userId} non trouvé.");
            return Command::FAILURE;
        }

        $this->info("Traitement de l'utilisateur {$userId} ({$user->name})...");

        // Trouver ou créer le rôle intégrateur (case-insensitive)
        $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
        
        if (!$integratorRole) {
            $this->info("Création du rôle intégrateur...");
            IntegratorPermissionService::createIntegratorRole();
            $integratorRole = Role::whereRaw('LOWER(name) = ?', ['integrator'])->first();
        }

        if (!$integratorRole) {
            $this->error("Impossible de créer ou trouver le rôle intégrateur.");
            return Command::FAILURE;
        }

        // Assigner le rôle à l'utilisateur
        $userRoles = $user->getRoleNames()->map(fn($role) => strtolower($role))->toArray();
        
        if (!in_array('integrator', $userRoles)) {
            $this->info("Assignation du rôle intégrateur à l'utilisateur...");
            $user->assignRole($integratorRole);
            $this->info("✓ Rôle intégrateur assigné");
        } else {
            $this->info("✓ L'utilisateur a déjà le rôle intégrateur");
        }

        // Assigner toutes les permissions via le service
        $this->info("Assignation des permissions...");
        $success = IntegratorPermissionService::assignIntegratorPermissions($user);

        if ($success) {
            $this->info("✓ Permissions assignées avec succès");
            
            // Vérifier les permissions clés
            $keyPermissions = [
                'view_charging_points',
                'create_charging_points',
                'edit_charging_points',
                'delete_charging_points',
            ];
            
            $this->info("\nVérification des permissions clés:");
            foreach ($keyPermissions as $permission) {
                $has = $user->can($permission) ? '✓' : '✗';
                $this->line("  {$has} {$permission}");
            }
            
            // Réinitialiser le cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            $this->info("\n✅ Opération terminée avec succès!");
            return Command::SUCCESS;
        } else {
            $this->error("✗ Échec de l'assignation des permissions");
            return Command::FAILURE;
        }
    }
}

