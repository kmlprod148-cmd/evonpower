<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AssignIntegratorPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:assign-integrator {--force : Force assignment even if permissions exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign missing permissions to integrator role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Attribution des permissions aux intégrateurs...');
        $this->newLine();

        // Permissions requises pour les intégrateurs
        $requiredPermissions = [
            // Users/Operators Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'show_users',
            'activate_users',
            'deactivate_users',
            
            // Partners Management
            'view_partners',
            'create_partners',
            'edit_partners',
            'delete_partners',
            'show_partners',
            'activate_partners',
            'deactivate_partners',
            
            // Charging Points Management
            'view_integrator_charging_points',
            'create_charging_points',
            'edit_charging_points',
            'delete_charging_points',
            'show_charging_points',
            
            // Groups Management
            'view_integrator_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'show_groups',
            
            // Business Profiles Management
            'view_business_profiles',
            'create_business_profiles',
            'edit_business_profiles',
            'delete_business_profiles',
            'show_business_profiles',
            
            // Financial Management
            'view_transactions',
            'view_integrator_transactions',
            'approve_withdrawal_requests',
            'manage_pricing_plans',
            
            // Dashboard and Reports
            'access_dashboard',
            'view_reports',
            'export_data',
        ];

        // Créer les permissions manquantes
        $createdPermissions = 0;
        foreach ($requiredPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            if ($permission->wasRecentlyCreated) {
                $createdPermissions++;
                $this->line("   ✅ Permission créée: {$permissionName}");
            }
        }

        if ($createdPermissions > 0) {
            $this->info("   📝 {$createdPermissions} nouvelle(s) permission(s) créée(s)");
        } else {
            $this->line("   ✅ Toutes les permissions existent déjà");
        }

        $this->newLine();

        // Obtenir le rôle intégrateur
        $integratorRole = Role::where('name', 'integrator')->first();
        
        if (!$integratorRole) {
            $this->error('❌ Le rôle "integrator" n\'existe pas !');
            return 1;
        }

        // Assigner les permissions au rôle
        $assignedPermissions = 0;
        foreach ($requiredPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission && !$integratorRole->hasPermissionTo($permission)) {
                $integratorRole->givePermissionTo($permission);
                $assignedPermissions++;
                $this->line("   ✅ Permission assignée: {$permissionName}");
            }
        }

        if ($assignedPermissions > 0) {
            $this->info("   🔗 {$assignedPermissions} permission(s) assignée(s) au rôle intégrateur");
        } else {
            $this->line("   ✅ Toutes les permissions sont déjà assignées au rôle intégrateur");
        }

        $this->newLine();

        // Vérifier les intégrateurs individuels
        $integrators = User::role('integrator')->get();
        $this->info("👥 Vérification des permissions pour {$integrators->count()} intégrateur(s)...");

        foreach ($integrators as $integrator) {
            $missingPermissions = [];
            foreach ($requiredPermissions as $permissionName) {
                if (!$integrator->can($permissionName)) {
                    $missingPermissions[] = $permissionName;
                }
            }

            if (count($missingPermissions) > 0) {
                $this->warn("   ⚠️  {$integrator->name} manque " . count($missingPermissions) . " permission(s)");
                // Les permissions seront automatiquement héritées du rôle
            } else {
                $this->line("   ✅ {$integrator->name} a toutes les permissions");
            }
        }

        $this->newLine();
        $this->info('✅ Attribution des permissions terminée !');

        return 0;
    }
}
