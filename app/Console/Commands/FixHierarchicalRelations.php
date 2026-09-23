<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\ChargingPoint;

class FixHierarchicalRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hierarchy:fix-relations {--verify : Only verify relations without fixing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix hierarchical relations: Integrator->Admin, Operator->Integrator, Group->Partner, ChargingPoint->Group';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('verify')) {
            $this->verifyRelations();
        } else {
            $this->fixRelations();
            $this->verifyRelations();
        }
    }

    /**
     * Fix all hierarchical relations
     */
    private function fixRelations()
    {
        $this->info('🔧 Début de la correction des relations hiérarchiques...');
        $this->newLine();
        
        $this->fixIntegratorAdminRelations();
        $this->fixOperatorIntegratorRelations();
        $this->fixGroupPartnerRelations();
        $this->fixChargingPointGroupRelations();
        
        $this->newLine();
        $this->info('✅ Correction des relations hiérarchiques terminée !');
    }
    
    /**
     * Fix Integrator -> Admin relations
     */
    private function fixIntegratorAdminRelations()
    {
        $this->info('1️⃣ Vérification des relations Intégrateur -> Admin...');
        
        $integratorsWithoutAdmin = Integrator::whereNull('created_by')->get();
        
        if ($integratorsWithoutAdmin->count() > 0) {
            $this->warn("   ⚠️  Trouvé {$integratorsWithoutAdmin->count()} intégrateur(s) sans admin créateur");
            
            $admin = User::whereHas('roles', function($query) {
                $query->where('name', 'admin');
            })->first();
            
            if ($admin) {
                foreach ($integratorsWithoutAdmin as $integrator) {
                    $integrator->update([
                        'created_by' => $admin->id,
                        'created_by_type' => get_class($admin),
                        'created_by_id' => $admin->id
                    ]);
                    $this->line("   ✅ Intégrateur '{$integrator->name}' assigné à l'admin '{$admin->name}'");
                }
            } else {
                $this->error('   ❌ Aucun admin trouvé pour assigner les intégrateurs');
            }
        } else {
            $this->line('   ✅ Tous les intégrateurs ont un admin créateur');
        }
        
        $this->newLine();
    }
    
    /**
     * Fix Operator -> Integrator relations
     */
    private function fixOperatorIntegratorRelations()
    {
        $this->info('2️⃣ Vérification des relations Opérateur -> Intégrateur...');
        
        $operatorsWithoutIntegrator = User::whereHas('roles', function($query) {
            $query->where('name', 'operator');
        })->where(function($query) {
            $query->whereNull('created_by')
                  ->orWhereNull('integrator_id');
        })->get();
        
        if ($operatorsWithoutIntegrator->count() > 0) {
            $this->warn("   ⚠️  Trouvé {$operatorsWithoutIntegrator->count()} opérateur(s) sans intégrateur");
            
            $integrators = Integrator::with('user')->where('is_active', true)->get();
            
            if ($integrators->count() > 0) {
                foreach ($operatorsWithoutIntegrator as $operator) {
                    $integrator = $integrators->first();
                    
                    $operator->update([
                        'created_by' => $integrator->user_id,
                        'integrator_id' => $integrator->id
                    ]);
                    
                    $this->line("   ✅ Opérateur '{$operator->name}' assigné à l'intégrateur '{$integrator->name}'");
                }
            } else {
                $this->error('   ❌ Aucun intégrateur actif trouvé pour assigner les opérateurs');
            }
        } else {
            $this->line('   ✅ Tous les opérateurs ont un intégrateur créateur');
        }
        
        $this->newLine();
    }
    
    /**
     * Fix Group -> Partner relations
     */
    private function fixGroupPartnerRelations()
    {
        $this->info('3️⃣ Vérification des relations Groupe -> Partenaire...');
        
        $groupsWithoutPartner = Group::whereNull('partner_id')->get();
        
        if ($groupsWithoutPartner->count() > 0) {
            $this->warn("   ⚠️  Trouvé {$groupsWithoutPartner->count()} groupe(s) sans partenaire");
            
            $partners = Partner::where('is_active', true)->get();
            
            if ($partners->count() > 0) {
                foreach ($groupsWithoutPartner as $group) {
                    $partner = $partners->first();
                    
                    $group->update([
                        'partner_id' => $partner->id
                    ]);
                    
                    $this->line("   ✅ Groupe '{$group->name}' assigné au partenaire '{$partner->name}'");
                }
            } else {
                $this->error('   ❌ Aucun partenaire actif trouvé pour assigner les groupes');
            }
        } else {
            $this->line('   ✅ Tous les groupes ont un partenaire');
        }
        
        $this->newLine();
    }
    
    /**
     * Fix ChargingPoint -> Group relations
     */
    private function fixChargingPointGroupRelations()
    {
        $this->info('4️⃣ Vérification des relations Borne -> Groupe...');
        
        $chargingPointsWithoutGroup = ChargingPoint::whereNull('group_id')->get();
        
        if ($chargingPointsWithoutGroup->count() > 0) {
            $this->warn("   ⚠️  Trouvé {$chargingPointsWithoutGroup->count()} borne(s) sans groupe");
            
            $groups = Group::all();
            
            if ($groups->count() > 0) {
                foreach ($chargingPointsWithoutGroup as $chargingPoint) {
                    $group = $groups->first();
                    
                    $chargingPoint->update([
                        'group_id' => $group->id
                    ]);
                    
                    $this->line("   ✅ Borne '{$chargingPoint->name}' assignée au groupe '{$group->name}'");
                }
            } else {
                $this->error('   ❌ Aucun groupe trouvé pour assigner les bornes');
            }
        } else {
            $this->line('   ✅ Toutes les bornes ont un groupe');
        }
        
        $this->newLine();
    }
    
    /**
     * Verify all hierarchical relations
     */
    private function verifyRelations()
    {
        $this->info('🔍 Vérification de l\'intégrité des relations...');
        $this->newLine();
        
        $integratorsWithoutAdmin = Integrator::whereNull('created_by')->count();
        $this->line("Intégrateurs sans admin: {$integratorsWithoutAdmin}");
        
        $operatorsWithoutIntegrator = User::whereHas('roles', function($query) {
            $query->where('name', 'operator');
        })->where(function($query) {
            $query->whereNull('created_by')
                  ->orWhereNull('integrator_id');
        })->count();
        $this->line("Opérateurs sans intégrateur: {$operatorsWithoutIntegrator}");
        
        $groupsWithoutPartner = Group::whereNull('partner_id')->count();
        $this->line("Groupes sans partenaire: {$groupsWithoutPartner}");
        
        $chargingPointsWithoutGroup = ChargingPoint::whereNull('group_id')->count();
        $this->line("Bornes sans groupe: {$chargingPointsWithoutGroup}");
        
        $total = $integratorsWithoutAdmin + $operatorsWithoutIntegrator + $groupsWithoutPartner + $chargingPointsWithoutGroup;
        
        $this->newLine();
        
        if ($total === 0) {
            $this->info('✅ Toutes les relations hiérarchiques sont correctes !');
        } else {
            $this->warn("⚠️  {$total} relation(s) hiérarchique(s) manquante(s)");
        }
    }
}
