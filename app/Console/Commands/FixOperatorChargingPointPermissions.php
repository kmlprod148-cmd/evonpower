<?php

namespace App\Console\Commands;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Console\Command;

class FixOperatorChargingPointPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'operators:fix-charging-point-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ajoute la permission view_charging_points aux opérateurs pour corriger l\'erreur 403';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction des permissions des opérateurs pour les points de charge...');

        try {
            // 1. Créer la permission si elle n'existe pas
            $permission = Permission::firstOrCreate([
                'name' => 'view_charging_points',
                'guard_name' => 'web'
            ]);

            $this->info("✅ Permission 'view_charging_points' créée/trouvée");

            // 2. Récupérer le rôle operator
            $operatorRole = Role::where('name', 'operator')->first();
            
            if (!$operatorRole) {
                $this->error('❌ Rôle operator non trouvé');
                return 1;
            }

            // 3. Ajouter la permission au rôle operator
            $operatorRole->givePermissionTo('view_charging_points');
            $this->info("✅ Permission 'view_charging_points' ajoutée au rôle operator");

            // 4. Récupérer tous les utilisateurs avec le rôle operator
            $operators = User::role('operator')->get();
            $this->info("📊 {$operators->count()} opérateurs trouvés");

            // 5. Ajouter la permission directement aux opérateurs existants
            $updated = 0;
            foreach ($operators as $operator) {
                if (!$operator->hasPermissionTo('view_charging_points')) {
                    $operator->givePermissionTo('view_charging_points');
                    $updated++;
                }
            }

            $this->info("✅ {$updated} opérateurs mis à jour avec la permission");

            // 6. Vérifier les permissions
            $this->info("\n📋 Vérification des permissions:");
            $this->table(
                ['Opérateur', 'Email', 'Permission view_charging_points'],
                $operators->map(function ($operator) {
                    return [
                        $operator->name,
                        $operator->email,
                        $operator->hasPermissionTo('view_charging_points') ? '✅ Oui' : '❌ Non'
                    ];
                })->toArray()
            );

            $this->info("\n🎉 Correction terminée avec succès!");
            $this->info("Les opérateurs peuvent maintenant accéder à /charging-points");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}
