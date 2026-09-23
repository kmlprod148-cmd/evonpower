<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class AssignIntegratorRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:assign-role {user_id : The ID of the user to assign the integrator role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the integrator role to a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $this->info("🔐 Assignation du rôle intégrateur à l'utilisateur ID {$userId}...");
        $this->newLine();

        // Trouver l'utilisateur
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Utilisateur ID {$userId} non trouvé !");
            
            // Essayer de trouver par email
            $this->info("🔍 Recherche par email 'integrator07@evonpower.com'...");
            $user = User::where('email', 'integrator07@evonpower.com')->first();
            
            if (!$user) {
                $this->error("❌ Utilisateur non trouvé.");
                return 1;
            }
            
            $this->info("✅ Utilisateur trouvé: ID {$user->id}");
        }

        $this->info("📋 Informations de l'utilisateur:");
        $this->line("   - ID: {$user->id}");
        $this->line("   - Nom: {$user->name}");
        $this->line("   - Email: {$user->email}");
        $this->line("   - Integrator ID: " . ($user->integrator_id ?? 'NULL'));
        
        $currentRoles = $user->getRoleNames();
        $this->line("   - Rôles actuels: " . ($currentRoles->isEmpty() ? 'AUCUN' : $currentRoles->implode(', ')));
        $this->newLine();

        // Vérifier si l'utilisateur a déjà le rôle
        if ($user->hasRole('integrator')) {
            $this->warn("⚠️  L'utilisateur a déjà le rôle 'integrator'.");
            
            if ($this->confirm('Voulez-vous quand même réassigner le rôle ?', false)) {
                $user->removeRole('integrator');
                $this->info("   🔄 Rôle retiré");
            } else {
                $this->info("✅ Aucune action nécessaire.");
                return 0;
            }
        }

        // Trouver ou créer le rôle intégrateur
        $integratorRole = Role::where('name', 'integrator')->first();
        
        if (!$integratorRole) {
            $this->warn("⚠️  Le rôle 'integrator' n'existe pas. Création...");
            $integratorRole = Role::create([
                'name' => 'integrator',
                'guard_name' => 'web'
            ]);
            $this->info("✅ Rôle 'integrator' créé");
        }

        // Assigner le rôle
        $this->info("🔧 Assignation du rôle 'integrator'...");
        $user->assignRole('integrator');
        $this->info("✅ Rôle assigné avec succès");
        $this->newLine();

        // Vider le cache
        $this->info("🔄 Vidage du cache des permissions...");
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        if (function_exists('cache')) {
            cache()->flush();
        }
        $this->info("✅ Cache vidé");
        $this->newLine();

        // Recharger et vérifier
        $user->refresh();
        $user->load('roles.permissions');

        $this->info("📊 Vérification finale:");
        $this->line("   - Rôle intégrateur: " . ($user->hasRole('integrator') ? '✅ OUI' : '❌ NON'));
        $this->line("   - Integrator ID: " . ($user->integrator_id ?? 'NULL'));
        $this->line("   - Permission view_users: " . ($user->can('view_users') ? '✅ OUI' : '❌ NON'));
        $this->line("   - Permission create_users: " . ($user->can('create_users') ? '✅ OUI' : '❌ NON'));
        $this->line("   - Permission show_users: " . ($user->can('show_users') ? '✅ OUI' : '❌ NON'));
        $this->newLine();

        $this->info("✅ Correction terminée avec succès !");
        $this->newLine();
        $this->info("📋 PROCHAINES ÉTAPES:");
        $this->line("   1. Déconnectez-vous et reconnectez-vous pour rafraîchir la session");
        $this->line("   2. Testez l'accès à /integrator/operators");
        $this->line("   3. Le problème devrait être résolu maintenant");

        Log::info("Integrator role assigned to user", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'integrator_id' => $user->integrator_id,
        ]);

        return 0;
    }
}

