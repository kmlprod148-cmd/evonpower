<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\HierarchicalDemoDataSeeder_2025_09_27;

class GenerateHierarchicalDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:generate-hierarchical-data {--force : Force generation even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate hierarchical demo data with Admin → Integrator → Operator structure and fee distribution';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Génération des données de démonstration hiérarchiques');
        $this->info('Date: ' . date('Y-m-d H:i:s'));
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Voulez-vous générer les données de démonstration ?')) {
                $this->info('Opération annulée.');
                return 0;
            }
        }

        $this->info('📋 Plan d\'exécution:');
        $this->line('1. Création des utilisateurs hiérarchiques (Admin → Intégrateur → Opérateur)');
        $this->line('2. Création des profils d\'affaires avec frais hiérarchiques');
        $this->line('3. Création des plans tarifaires (énergie et temps)');
        $this->line('4. Création des bornes de recharge (rapide et standard)');
        $this->line('5. Génération des réservations avec distribution des frais');
        $this->newLine();

        try {
            // Run the seeder
            $seeder = new HierarchicalDemoDataSeeder_2025_09_27();
            $seeder->setCommand($this);
            $seeder->run();

            $this->newLine();
            $this->info('✅ SUCCÈS! Données de démonstration générées.');
            $this->newLine();

            $today = date('Y-m-d');
            
            $this->info('🔐 COMPTES CRÉÉS:');
            $this->table(['Rôle', 'Email', 'Mot de passe'], [
                ['Admin', "admin.demo.{$today}@evon.ma", 'password123'],
                ['Intégrateur', "integrator.user.demo.{$today}@evon.ma", 'password123'],
                ['Opérateur', "operator.demo.{$today}@evon.ma", 'password123'],
            ]);

            $this->info('⚡ BORNES CRÉÉES:');
            $this->table(['Type', 'Puissance', 'Tarification'], [
                ['Borne Rapide', '50kW', 'Par énergie (kWh)'],
                ['Borne Standard', '22kW', 'Par temps (minutes)'],
            ]);

            $this->info('💰 STRUCTURE DES FRAIS:');
            $this->table(['Niveau', 'Frais Fixes', 'Frais Pourcentage'], [
                ['Admin', '0.50€', '2%'],
                ['Intégrateur', '0.30€', '1.5%'],
                ['Opérateur', '-', 'Reste après frais'],
            ]);

            $this->info('📊 RÉSERVATIONS GÉNÉRÉES:');
            $this->table(['Type', 'Quantité', 'Prix Total'], [
                ['Énergie', '25 kWh', '6.75€'],
                ['Temps', '120 minutes', '19.30€'],
            ]);

            $this->newLine();
            $this->info('🎯 PROCHAINES ÉTAPES:');
            $this->line('1. Connectez-vous avec l\'un des comptes créés');
            $this->line('2. Explorez les bornes de recharge dans l\'interface');
            $this->line('3. Vérifiez les transactions et la distribution des frais');
            $this->line('4. Testez les actions à distance sur les bornes');
            $this->newLine();

            $this->comment('📝 NOTE: Les données sont marquées avec la date d\'aujourd\'hui pour faciliter l\'identification.');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ ERREUR lors de la génération: ' . $e->getMessage());
            $this->line('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
