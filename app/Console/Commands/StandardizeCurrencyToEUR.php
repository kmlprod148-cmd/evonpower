<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class StandardizeCurrencyToEUR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:standardize-eur 
                            {--force : Forcer la standardisation même si des données existent}
                            {--dry-run : Afficher seulement les statistiques sans effectuer les changements}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Standardise toutes les devises vers EUR dans l\'application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Standardisation des devises vers EUR');
        $this->line('=' . str_repeat('=', 50));

        // Vérifier les statistiques actuelles
        $this->info('📊 Statistiques actuelles des devises:');
        $stats = CurrencyService::getCurrencyStatistics();
        
        foreach ($stats as $table => $currencies) {
            $this->line("   {$table}:");
            foreach ($currencies as $currency => $count) {
                $this->line("     - {$currency}: {$count} enregistrements");
            }
        }

        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode dry-run activé - Aucun changement ne sera effectué');
            return;
        }

        // Demander confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('Voulez-vous continuer avec la standardisation vers EUR ?')) {
                $this->info('Opération annulée');
                return;
            }
        }

        try {
            $this->info('🔄 Début de la standardisation...');
            
            $results = CurrencyService::standardizeToEUR();
            
            $this->info('✅ Standardisation terminée avec succès !');
            $this->line('');
            
            $totalUpdated = 0;
            foreach ($results as $table => $count) {
                if ($count > 0) {
                    $this->line("   ✅ {$table}: {$count} enregistrements mis à jour");
                    $totalUpdated += $count;
                }
            }
            
            $this->line('');
            $this->info("📊 Total: {$totalUpdated} enregistrements mis à jour");
            
            // Afficher les nouvelles statistiques
            $this->line('');
            $this->info('📊 Nouvelles statistiques des devises:');
            $newStats = CurrencyService::getCurrencyStatistics();
            
            foreach ($newStats as $table => $currencies) {
                $this->line("   {$table}:");
                foreach ($currencies as $currency => $count) {
                    $this->line("     - {$currency}: {$count} enregistrements");
                }
            }
            
            $this->line('');
            $this->info('🎯 Toutes les devises ont été standardisées vers EUR !');
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la standardisation: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}