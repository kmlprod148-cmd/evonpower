<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Partner;
use App\Models\Integrator;

class CheckDatabaseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:database-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les données de base nécessaires (partners, integrators)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Vérification des données de base...");
        $this->newLine();

        // Vérifier les partenaires
        $partners = Partner::count();
        $this->info("📊 Partenaires: {$partners}");
        
        if ($partners > 0) {
            $firstPartner = Partner::first();
            $this->info("  - Premier partenaire: {$firstPartner->name} (ID: {$firstPartner->id})");
        } else {
            $this->warn("  - ⚠️ Aucun partenaire trouvé");
        }

        // Vérifier les intégrateurs
        $integrators = Integrator::count();
        $this->info("📊 Intégrateurs: {$integrators}");
        
        if ($integrators > 0) {
            $firstIntegrator = Integrator::first();
            $this->info("  - Premier intégrateur: {$firstIntegrator->name} (ID: {$firstIntegrator->id})");
        } else {
            $this->warn("  - ⚠️ Aucun intégrateur trouvé");
        }

        $this->newLine();
        
        if ($partners == 0 || $integrators == 0) {
            $this->error("❌ Données manquantes ! Créons des données de base...");
            
            if ($partners == 0) {
                $partner = Partner::create([
                    'name' => 'Partenaire Test',
                    'email' => 'test@partner.com',
                    'is_active' => true,
                ]);
                $this->info("✅ Partenaire créé avec ID: {$partner->id}");
            }
            
            if ($integrators == 0) {
                $integrator = Integrator::create([
                    'name' => 'Intégrateur Test',
                    'email' => 'test@integrator.com',
                    'is_active' => true,
                ]);
                $this->info("✅ Intégrateur créé avec ID: {$integrator->id}");
            }
        } else {
            $this->info("✅ Données de base disponibles");
        }

        return 0;
    }
}
