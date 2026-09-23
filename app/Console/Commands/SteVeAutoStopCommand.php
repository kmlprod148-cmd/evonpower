<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SteVeAutoStopService;
use Illuminate\Support\Facades\Log;

/**
 * Commande Artisan pour l'arrêt automatique des sessions de recharge SETEVE
 * 
 * Cette commande vérifie et arrête automatiquement les sessions qui ont atteint
 * leurs limites de réservation (temps ou énergie)
 */
class SteVeAutoStopCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steve:auto-stop 
                            {--session-id= : Vérifier une session spécifique}
                            {--stats : Afficher les statistiques des sessions}
                            {--dry-run : Mode simulation (ne pas arrêter réellement)}
                            {--verbose : Affichage détaillé}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier et arrêter automatiquement les sessions de recharge SETEVE qui ont atteint leurs limites';

    protected SteVeAutoStopService $autoStopService;

    /**
     * Create a new command instance.
     */
    public function __construct(SteVeAutoStopService $autoStopService)
    {
        parent::__construct();
        $this->autoStopService = $autoStopService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔋 Démarrage du monitoring automatique SETEVE...');
        
        try {
            // Mode simulation
            if ($this->option('dry-run')) {
                $this->warn('⚠️  Mode simulation activé - Aucune session ne sera arrêtée');
            }

            // Vérifier une session spécifique
            if ($sessionId = $this->option('session-id')) {
                return $this->checkSpecificSession($sessionId);
            }

            // Afficher les statistiques
            if ($this->option('stats')) {
                return $this->showStats();
            }

            // Vérification générale des sessions
            return $this->checkAllSessions();

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'exécution: ' . $e->getMessage());
            Log::error('SteVeAutoStopCommand: Erreur lors de l\'exécution', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Vérifier toutes les sessions actives
     */
    protected function checkAllSessions(): int
    {
        $this->info('🔍 Vérification des sessions actives...');
        
        $result = $this->autoStopService->checkAndStopExpiredSessions();
        
        if (!$result['success']) {
            $this->error('❌ Erreur lors de la vérification: ' . $result['error']);
            return Command::FAILURE;
        }

        // Afficher les résultats
        $this->displayResults($result);
        
        return Command::SUCCESS;
    }

    /**
     * Vérifier une session spécifique
     */
    protected function checkSpecificSession(string $sessionId): int
    {
        $this->info("🔍 Vérification de la session: {$sessionId}");
        
        $result = $this->autoStopService->checkSpecificSession($sessionId);
        
        if (!$result['success']) {
            $this->error('❌ Erreur: ' . $result['error']);
            return Command::FAILURE;
        }

        // Afficher le résultat
        if ($result['action'] === 'stopped') {
            $this->warn("🛑 Session arrêtée: {$result['reason']}");
        } else {
            $this->info("✅ Session continue: {$result['reason']}");
            
            if (isset($result['remaining'])) {
                $this->line("📊 Restant: " . json_encode($result['remaining']));
            }
        }
        
        return Command::SUCCESS;
    }

    /**
     * Afficher les statistiques des sessions
     */
    protected function showStats(): int
    {
        $this->info('📊 Statistiques des sessions SETEVE...');
        
        $result = $this->autoStopService->getSessionStats();
        
        if (!$result['success']) {
            $this->error('❌ Erreur lors de la récupération des statistiques: ' . $result['error']);
            return Command::FAILURE;
        }

        $stats = $result['stats'];
        
        // Afficher les statistiques
        $this->line('');
        $this->info('📈 Statistiques des sessions:');
        $this->line("   Total sessions actives: {$stats['total_active_sessions']}");
        $this->line("   Énergie totale délivrée: {$stats['total_energy_delivered']} kWh");
        
        if (!empty($stats['sessions_by_status'])) {
            $this->line('');
            $this->info('📊 Par statut:');
            foreach ($stats['sessions_by_status'] as $status => $count) {
                $this->line("   {$status}: {$count}");
            }
        }
        
        if (!empty($stats['sessions_by_reservation_type'])) {
            $this->line('');
            $this->info('📊 Par type de réservation:');
            foreach ($stats['sessions_by_reservation_type'] as $type => $count) {
                $this->line("   {$type}: {$count}");
            }
        }
        
        return Command::SUCCESS;
    }

    /**
     * Afficher les résultats de la vérification
     */
    protected function displayResults(array $result): void
    {
        $this->line('');
        $this->info("✅ {$result['message']}");
        $this->line("   Sessions vérifiées: {$result['sessions_checked']}");
        $this->line("   Sessions arrêtées: {$result['sessions_stopped']}");
        
        if (!empty($result['results']) && $this->option('verbose')) {
            $this->line('');
            $this->info('📋 Détails des actions:');
            
            foreach ($result['results'] as $action) {
                $sessionId = $action['session_id'];
                $reservationId = $action['reservation_id'] ?? 'N/A';
                $reason = $action['reason'];
                
                switch ($action['action']) {
                    case 'stopped':
                        $this->warn("   🛑 Session {$sessionId} (Réservation {$reservationId}): {$reason}");
                        break;
                    case 'stop_failed':
                        $this->error("   ❌ Échec arrêt Session {$sessionId} (Réservation {$reservationId}): {$reason}");
                        if (isset($action['error'])) {
                            $this->line("      Erreur: {$action['error']}");
                        }
                        break;
                    case 'continue':
                        $this->info("   ✅ Session {$sessionId} (Réservation {$reservationId}): {$reason}");
                        if (isset($action['remaining'])) {
                            $remaining = json_encode($action['remaining']);
                            $this->line("      Restant: {$remaining}");
                        }
                        break;
                }
            }
        }
    }
}
