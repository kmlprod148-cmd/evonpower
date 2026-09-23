<?php

namespace App\Console\Commands;

use App\Services\ApiKeysSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour synchroniser les clés API entre la base de données et les variables d'environnement
 */
class SyncApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-keys:sync 
                            {--direction=both : Direction de synchronisation (db-to-env, env-to-db, both)}
                            {--force : Forcer la synchronisation même en cas d\'erreurs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les clés API entre la base de données et les variables d\'environnement';

    protected ApiKeysSyncService $syncService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ApiKeysSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $direction = $this->option('direction');
        $force = $this->option('force');

        $this->info('🔄 Synchronisation des clés API...');
        $this->newLine();

        try {
            switch ($direction) {
                case 'db-to-env':
                    $this->syncFromDatabaseToEnvironment($force);
                    break;
                case 'env-to-db':
                    $this->syncFromEnvironmentToDatabase($force);
                    break;
                case 'both':
                default:
                    $this->syncBidirectional($force);
                    break;
            }

            $this->newLine();
            $this->info('✅ Synchronisation terminée avec succès');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            Log::error('Erreur lors de la synchronisation des clés API', [
                'error' => $e->getMessage(),
                'direction' => $direction,
                'force' => $force
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Synchronise depuis la base de données vers l'environnement
     */
    private function syncFromDatabaseToEnvironment(bool $force): void
    {
        $this->info('📤 Synchronisation DB → ENV...');
        
        $success = $this->syncService->syncToEnvironment();
        
        if ($success) {
            $this->info('✅ Variables d\'environnement mises à jour');
        } else {
            $this->error('❌ Échec de la synchronisation DB → ENV');
            if (!$force) {
                throw new \Exception('Synchronisation DB → ENV échouée');
            }
        }
    }

    /**
     * Synchronise depuis l'environnement vers la base de données
     */
    private function syncFromEnvironmentToDatabase(bool $force): void
    {
        $this->info('📥 Synchronisation ENV → DB...');
        
        $success = $this->syncService->syncFromEnvironment();
        
        if ($success) {
            $this->info('✅ Base de données mise à jour');
        } else {
            $this->error('❌ Échec de la synchronisation ENV → DB');
            if (!$force) {
                throw new \Exception('Synchronisation ENV → DB échouée');
            }
        }
    }

    /**
     * Synchronisation bidirectionnelle
     */
    private function syncBidirectional(bool $force): void
    {
        $this->info('🔄 Synchronisation bidirectionnelle...');
        
        // D'abord ENV → DB
        $this->syncFromEnvironmentToDatabase($force);
        
        // Puis DB → ENV
        $this->syncFromDatabaseToEnvironment($force);
        
        $this->info('✅ Synchronisation bidirectionnelle terminée');
    }

    /**
     * Affiche le statut de synchronisation
     */
    private function displaySyncStatus(): void
    {
        $status = $this->syncService->getSyncStatus();
        
        $this->newLine();
        $this->info('📊 Statut de synchronisation:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Clés synchronisées', count($status['synchronized'])],
                ['Clés désynchronisées', count($status['out_of_sync'])],
                ['Total des clés', $status['total_keys']],
                ['Pourcentage de sync', round($status['sync_percentage'], 2) . '%']
            ]
        );

        if (!empty($status['out_of_sync'])) {
            $this->newLine();
            $this->warn('⚠️  Clés désynchronisées:');
            foreach ($status['out_of_sync'] as $key) {
                $this->line("  - {$key['key']}: DB ≠ ENV");
            }
        }
    }
}
