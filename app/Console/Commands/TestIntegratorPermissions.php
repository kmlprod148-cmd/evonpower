<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Partner;

class TestIntegratorPermissions extends Command
{
    protected $signature = 'test:integrator-permissions';
    protected $description = 'Test des permissions pour les intégrateurs';

    public function handle()
    {
        $this->info('=== Test des permissions pour les intégrateurs ===');
        $this->newLine();

        // 1. Vérifier les permissions
        $this->info('1. Vérification des permissions...');
        $requiredPermissions = [
            'view_partners', 'create_partners', 'edit_partners', 'delete_partners', 'show_partners'
        ];
        
        foreach ($requiredPermissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $this->line("   ✓ Permission '$permission' existe");
            } else {
                $this->line("   ✗ Permission '$permission' manquante");
            }
        }
        
        // 2. Vérifier le rôle intégrateur
        $this->newLine();
        $this->info('2. Vérification du rôle intégrateur...');
        $integratorRole = Role::where('name', 'integrator')->first();
        if ($integratorRole) {
            $this->line('   ✓ Rôle integrator existe');
            $permissions = $integratorRole->permissions->pluck('name')->toArray();
            $this->line('   Permissions assignées: ' . implode(', ', $permissions));
        } else {
            $this->line('   ✗ Rôle integrator manquant');
        }
        
        // 3. Vérifier les utilisateurs intégrateurs
        $this->newLine();
        $this->info('3. Vérification des utilisateurs intégrateurs...');
        $integrators = User::role('integrator')->get();
        $this->line('   Nombre d\'intégrateurs: ' . $integrators->count());
        
        foreach ($integrators as $integrator) {
            $this->line("   - Intégrateur ID: {$integrator->id}, Email: {$integrator->email}");
            $this->line("     Permissions: " . implode(', ', $integrator->getPermissionNames()->toArray()));
        }
        
        // 4. Vérifier les partenaires
        $this->newLine();
        $this->info('4. Vérification des partenaires...');
        $partners = Partner::with(['creator', 'integrator'])->get();
        $this->line('   Nombre de partenaires: ' . $partners->count());
        
        foreach ($partners as $partner) {
            $this->line("   - Partenaire ID: {$partner->id}, Nom: {$partner->name}");
            $this->line("     Créé par: " . ($partner->created_by ? "User ID {$partner->created_by}" : "Non défini"));
            $this->line("     Intégrateur ID: " . ($partner->integrator_id ?: "Non défini"));
            if ($partner->creator) {
                $this->line("     Créateur email: {$partner->creator->email}");
            }
        }
        
        // 5. Test des policies
        $this->newLine();
        $this->info('5. Test des policies...');
        $testIntegrator = $integrators->first();
        if ($testIntegrator) {
            $this->line("   Test avec l'intégrateur: {$testIntegrator->email}");
            
            // Test avec un partenaire créé par cet intégrateur
            $partnerCreatedByIntegrator = Partner::where('created_by', $testIntegrator->id)->first();
            if ($partnerCreatedByIntegrator) {
                $this->line("   ✓ Partenaire trouvé créé par cet intégrateur: {$partnerCreatedByIntegrator->name}");
                
                // Test des permissions
                if ($testIntegrator->can('view', $partnerCreatedByIntegrator)) {
                    $this->line("   ✓ Permission 'view' accordée");
                } else {
                    $this->line("   ✗ Permission 'view' refusée");
                }
                
                if ($testIntegrator->can('update', $partnerCreatedByIntegrator)) {
                    $this->line("   ✓ Permission 'update' accordée");
                } else {
                    $this->line("   ✗ Permission 'update' refusée");
                }
                
                if ($testIntegrator->can('delete', $partnerCreatedByIntegrator)) {
                    $this->line("   ✓ Permission 'delete' accordée");
                } else {
                    $this->line("   ✗ Permission 'delete' refusée");
                }
            } else {
                $this->line("   ⚠ Aucun partenaire trouvé créé par cet intégrateur");
            }
        } else {
            $this->line("   ⚠ Aucun intégrateur trouvé pour les tests");
        }
        
        $this->newLine();
        $this->info('=== Test terminé ===');
    }
}
