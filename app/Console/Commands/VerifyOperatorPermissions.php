<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VerifyOperatorPermissions extends Command
{
    protected $signature = 'permissions:verify-operator';
    protected $description = 'Verify operator permissions are correctly set';

    public function handle()
    {
        $this->info('🔍 Vérification des permissions des opérateurs...');

        // Vérifier que la permission existe
        $permission = Permission::where('name', 'create_charging_points')->first();
        if (!$permission) {
            $this->error('❌ Permission "create_charging_points" n\'existe pas');
            return 1;
        }
        $this->info('✅ Permission "create_charging_points" existe');

        // Vérifier le rôle opérateur
        $operatorRole = Role::where('name', 'operator')->first();
        if (!$operatorRole) {
            $this->error('❌ Rôle "operator" n\'existe pas');
            return 1;
        }
        $this->info('✅ Rôle "operator" existe');

        // Vérifier que le rôle a la permission
        $hasPermission = $operatorRole->hasPermissionTo('create_charging_points');
        if (!$hasPermission) {
            $this->error('❌ Le rôle "operator" n\'a pas la permission "create_charging_points"');
            return 1;
        }
        $this->info('✅ Le rôle "operator" a la permission "create_charging_points"');

        // Vérifier les utilisateurs opérateurs
        $operators = User::role('operator')->get();
        $this->info("📊 Nombre d'opérateurs trouvés: " . $operators->count());

        $operatorsWithPermission = 0;
        $operatorsWithoutPermission = 0;

        foreach ($operators as $operator) {
            if ($operator->can('create_charging_points')) {
                $operatorsWithPermission++;
                $this->info("✅ {$operator->name} (ID: {$operator->id}) - Peut créer des points de charge");
            } else {
                $operatorsWithoutPermission++;
                $this->warn("⚠️  {$operator->name} (ID: {$operator->id}) - Ne peut PAS créer des points de charge");
            }
        }

        $this->info("📈 Résumé:");
        $this->info("   - Opérateurs avec permission: {$operatorsWithPermission}");
        $this->info("   - Opérateurs sans permission: {$operatorsWithoutPermission}");

        if ($operatorsWithoutPermission > 0) {
            $this->warn("⚠️  Certains opérateurs n'ont pas la permission. Exécutez: php artisan permissions:fix-operator");
        } else {
            $this->info("🎉 Tous les opérateurs ont les bonnes permissions !");
        }

        return 0;
    }
}
