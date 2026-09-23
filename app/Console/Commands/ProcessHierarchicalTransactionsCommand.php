<?php

namespace App\Console\Commands;

use App\Services\ChargingSessionCompletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessHierarchicalTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-hierarchical 
                            {--charging-point= : ID du point de charge spécifique}
                            {--session= : ID de la session spécifique}
                            {--force : Forcer le retraitement même si déjà traité}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traiter les transactions hiérarchiques pour les sessions de charge terminées';

    /**
     * Execute the console command.
     */
    public function handle(ChargingSessionCompletionService $completionService): int
    {
        $this->info('🚀 Début du traitement des transactions hiérarchiques...');

        try {
            $chargingPointId = $this->option('charging-point');
            $sessionId = $this->option('session');
            $force = $this->option('force');

            if ($sessionId) {
                // Traiter une session spécifique
                $this->processSpecificSession($completionService, $sessionId, $force);
            } elseif ($chargingPointId) {
                // Traiter toutes les sessions d'un point de charge
                $this->processChargingPointSessions($completionService, $chargingPointId, $force);
            } else {
                // Traiter toutes les sessions en attente
                $this->processAllPendingSessions($completionService, $force);
            }

            $this->info('✅ Traitement terminé avec succès');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du traitement: ' . $e->getMessage());
            Log::error('Erreur dans la commande de traitement des transactions hiérarchiques', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Traiter une session spécifique
     */
    protected function processSpecificSession(ChargingSessionCompletionService $completionService, int $sessionId, bool $force): void
    {
        $session = \App\Models\ChargingSession::find($sessionId);
        
        if (!$session) {
            $this->error("❌ Session {$sessionId} non trouvée");
            return;
        }

        $this->info("📋 Traitement de la session {$sessionId}...");

        if (!$force && $session->hierarchical_transaction_processed) {
            $this->warn("⚠️  Session déjà traitée. Utilisez --force pour forcer le retraitement.");
            return;
        }

        $result = $completionService->processSessionCompletion($session);

        if ($result['success']) {
            $this->info("✅ Session {$sessionId} traitée avec succès");
            if (isset($result['data']['amounts'])) {
                $this->line("   💰 Montant opérateur: " . ($result['data']['amounts']['operator'] ?? 0) . " EUR");
                $this->line("   💰 Montant intégrateur: " . ($result['data']['amounts']['integrator'] ?? 0) . " EUR");
            }
        } else {
            $this->error("❌ Échec du traitement de la session {$sessionId}: " . $result['error']);
        }
    }

    /**
     * Traiter toutes les sessions d'un point de charge
     */
    protected function processChargingPointSessions(ChargingSessionCompletionService $completionService, int $chargingPointId, bool $force): void
    {
        $this->info("🔌 Traitement des sessions du point de charge {$chargingPointId}...");

        $sessions = \App\Models\ChargingSession::where('charging_point_id', $chargingPointId)
            ->whereIn('status', ['completed', 'stopped']);

        if (!$force) {
            $sessions->where('hierarchical_transaction_processed', false);
        }

        $sessions = $sessions->get();

        if ($sessions->isEmpty()) {
            $this->warn("⚠️  Aucune session à traiter pour ce point de charge");
            return;
        }

        $this->info("📊 {$sessions->count()} session(s) à traiter");

        $bar = $this->output->createProgressBar($sessions->count());
        $bar->start();

        $processed = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            $result = $completionService->processSessionCompletion($session);
            
            if ($result['success']) {
                $processed++;
            } else {
                $failed++;
                $this->line("\n❌ Échec session {$session->id}: " . $result['error']);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("\n");
        $this->info("✅ Traitement terminé: {$processed} réussies, {$failed} échouées");
    }

    /**
     * Traiter toutes les sessions en attente
     */
    protected function processAllPendingSessions(ChargingSessionCompletionService $completionService, bool $force): void
    {
        $this->info("🌐 Traitement de toutes les sessions en attente...");

        $result = $completionService->processAllPendingSessions();

        $this->info("📊 Résultats du traitement:");
        $this->line("   ✅ Sessions traitées: {$result['processed']}");
        $this->line("   ❌ Sessions échouées: {$result['failed']}");

        if (!empty($result['errors'])) {
            $this->warn("⚠️  Erreurs rencontrées:");
            foreach ($result['errors'] as $error) {
                $this->line("   - Session {$error['session_id']}: {$error['error']}");
            }
        }

        // Afficher les statistiques
        $stats = $completionService->getTransactionStatistics();
        $this->info("📈 Statistiques des transactions hiérarchiques:");
        $this->line("   📊 Total transactions: {$stats['total_transactions']}");
        $this->line("   💰 Montant total: {$stats['total_amount']} EUR");
        $this->line("   👤 Transactions opérateur: {$stats['operator_transactions']}");
        $this->line("   🏢 Transactions intégrateur: {$stats['integrator_transactions']}");
    }
}
