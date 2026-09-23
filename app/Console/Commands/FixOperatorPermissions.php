<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class FixOperatorPermissions extends Command
{
    protected $signature = 'permissions:fix-operator';
    protected $description = 'Fix operator permissions to allow charging point creation';

    public function handle()
    {
        $this->info('🔧 Correction des permissions des opérateurs...');

        // Vérifier si la permission existe
        $permission = Permission::firstOrCreate(['name' => 'create_charging_points']);

        // Récupérer le rôle opérateur
        $operatorRole = Role::where('name', 'operator')->first();
        
        if (!$operatorRole) {
            $this->error('❌ Rôle "operator" non trouvé');
            return 1;
        }

        // Ajouter la permission au rôle opérateur
        if (!$operatorRole->hasPermissionTo('create_charging_points')) {
            $operatorRole->givePermissionTo('create_charging_points');
            $this->info('✅ Permission "create_charging_points" ajoutée au rôle opérateur');
        } else {
            $this->info('ℹ️  Permission "create_charging_points" déjà présente pour le rôle opérateur');
        }

        // Mettre à jour les permissions des utilisateurs opérateurs existants
        $operators = User::role('operator')->get();
        $updatedCount = 0;

        foreach ($operators as $operator) {
            if (!$operator->hasPermissionTo('create_charging_points')) {
                $operator->givePermissionTo('create_charging_points');
                $updatedCount++;
            }
        }

        $this->info("✅ Permissions mises à jour pour {$updatedCount} opérateurs existants");

        // Vérifier les permissions
        $this->info('🔍 Vérification des permissions...');
        
        $testOperator = User::role('operator')->first();
        if ($testOperator) {
            $canCreate = $testOperator->can('create_charging_points');
            $this->info('Test opérateur - Peut créer des points de charge: ' . ($canCreate ? '✅ Oui' : '❌ Non'));
        }

        $this->info('🎉 Correction des permissions terminée !');
        return 0;
    }
}
