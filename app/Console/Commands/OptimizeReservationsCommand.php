<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReservationPerformanceOptimizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Commande pour optimiser les performances des réservations
 */
class OptimizeReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reservations:optimize 
                            {--warm-cache : Préchauffer le cache}
                            {--clear-cache : Nettoyer le cache}
                            {--optimize-db : Optimiser la base de données}
                            {--all : Exécuter toutes les optimisations}';

    /**
     * The console command description.
     */
    protected $description = 'Optimise les performances des réservations et des frais';

    protected $optimizer;

    public function __construct(ReservationPerformanceOptimizer $optimizer)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Optimisation des réservations en cours...');

        $warmCache = $this->option('warm-cache') || $this->option('all');
        $clearCache = $this->option('clear-cache') || $this->option('all');
        $optimizeDb = $this->option('optimize-db') || $this->option('all');

        try {
            // Nettoyer le cache si demandé
            if ($clearCache) {
                $this->info('🧹 Nettoyage du cache...');
                $this->optimizer->clearReservationCache();
                Cache::flush();
                $this->info('✅ Cache nettoyé avec succès');
            }

            // Optimiser la base de données si demandé
            if ($optimizeDb) {
                $this->info('🗄️ Optimisation de la base de données...');
                $this->optimizer->optimizeDatabaseQueries();
                $this->info('✅ Base de données optimisée');
            }

            // Préchauffer le cache si demandé
            if ($warmCache) {
                $this->info('🔥 Préchauffage du cache...');
                $this->optimizer->warmUpCache();
                $this->info('✅ Cache préchauffé');
            }

            // Afficher les métriques de performance
            $this->displayPerformanceMetrics();

            $this->info('🎉 Optimisation terminée avec succès !');

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'optimisation: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Affiche les métriques de performance
     */
    private function displayPerformanceMetrics()
    {
        $this->info('📊 Métriques de performance:');
        
        $metrics = $this->optimizer->getPerformanceMetrics();
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Réservations', number_format($metrics['reservations_count'])],
                ['Transactions', number_format($metrics['transactions_count'])],
                ['Business Profiles', number_format($metrics['business_profiles_count'])],
                ['Temps d\'exécution', $metrics['execution_time_ms'] . 'ms'],
                ['Taux de cache', $metrics['cache_hit_rate'] . '%'],
                ['Mémoire utilisée', $this->formatBytes($metrics['memory_usage'])],
                ['Pic de mémoire', $this->formatBytes($metrics['peak_memory_usage'])]
            ]
        );
    }

    /**
     * Formate les bytes en unités lisibles
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
