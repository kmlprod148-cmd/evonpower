<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;

class FixIntegratorData extends Command
{
    protected $signature = 'fix:integrator-data';
    protected $description = 'Correction des données pour les intégrateurs';

    public function handle()
    {
        $this->info('=== Correction des données pour les intégrateurs ===');
        $this->newLine();

        // 1. Assigner les permissions aux intégrateurs existants
        $this->info('1. Assignation des permissions aux intégrateurs...');
        $integratorRole = Role::where('name', 'integrator')->first();
        if ($integratorRole) {
            $integrators = User::role('integrator')->get();
            foreach ($integrators as $integrator) {
                // Assigner le rôle avec toutes ses permissions
                $integrator->assignRole('integrator');
                $this->line("   ✓ Permissions assignées à {$integrator->email}");
            }
        }
        
        // 2. Mettre à jour les partenaires existants avec created_by
        $this->newLine();
        $this->info('2. Mise à jour des partenaires existants...');
        $partners = Partner::whereNull('created_by')->get();
        foreach ($partners as $partner) {
            // Si le partenaire a un integrator_id, trouver l'utilisateur intégrateur correspondant
            if ($partner->integrator_id) {
                $integrator = Integrator::find($partner->integrator_id);
                if ($integrator) {
                    // Trouver l'utilisateur intégrateur correspondant
                    $integratorUser = User::where('integrator_id', $integrator->id)->first();
                    if ($integratorUser) {
                        $partner->update(['created_by' => $integratorUser->id]);
                        $this->line("   ✓ Partenaire {$partner->name} mis à jour avec created_by: {$integratorUser->email}");
                    } else {
                        $this->line("   ⚠ Aucun utilisateur trouvé pour l'intégrateur ID {$integrator->id}");
                    }
                } else {
                    $this->line("   ⚠ Intégrateur ID {$partner->integrator_id} non trouvé");
                }
            } else {
                $this->line("   ⚠ Partenaire {$partner->name} sans integrator_id");
            }
        }
        
        // 3. Vérifier les relations
        $this->newLine();
        $this->info('3. Vérification des relations...');
        $integrators = User::role('integrator')->with('integrator')->get();
        foreach ($integrators as $integrator) {
            $this->line("   - Intégrateur: {$integrator->email}");
            $this->line("     Integrator ID: " . ($integrator->integrator_id ?: "Non défini"));
            $this->line("     Modèle Integrator: " . ($integrator->integrator ? "Oui" : "Non"));
            $this->line("     Permissions: " . $integrator->getPermissionNames()->count() . " permissions");
            
            // Compter les partenaires créés par cet intégrateur
            $partnersCreated = Partner::where('created_by', $integrator->id)->count();
            $this->line("     Partenaires créés: {$partnersCreated}");
        }
        
        $this->newLine();
        $this->info('=== Correction terminée ===');
    }
}
