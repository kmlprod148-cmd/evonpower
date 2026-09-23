<?php

namespace App\Console\Commands;

use App\Services\ChargingSessionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorChargingSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charging:monitor
                            {--dry-run : Afficher ce qui serait fait sans exécuter}
                            {--force : Forcer l\'arrêt même pour les sessions récentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorer les sessions de recharge actives et arrêter celles qui ont expiré';

    /**
     * Execute the console command.
     */
    public function handle(ChargingSessionManager $sessionManager)
    {
        $this->info('🔍 Démarrage du monitoring des sessions de recharge...');
        $this->newLine();

        try {
            // Mode dry-run : afficher seulement ce qui serait fait
            if ($this->option('dry-run')) {
                $this->warn('⚠️  MODE DRY-RUN - Aucune action ne sera exécutée');
                $this->newLine();
            }

            // Vérifier et arrêter les sessions expirées
            $result = $this->option('dry-run') 
                ? $this->checkSessionsDryRun() 
                : $sessionManager->checkAndStopExpiredSessions();

            if (!$result['success']) {
                $this->error('❌ Erreur lors de la vérification des sessions');
                $this->error($result['error'] ?? 'Erreur inconnue');
                return self::FAILURE;
            }

            $checked = $result['checked'] ?? 0;
            $stopped = $result['stopped'] ?? [];

            $this->info("✅ Vérification terminée:");
            $this->line("   - Sessions actives vérifiées: {$checked}");
            $this->line("   - Sessions arrêtées: " . count($stopped));

            if (count($stopped) > 0) {
                $this->newLine();
                $this->info('📋 Détails des sessions arrêtées:');
                $this->newLine();

                $tableData = [];
                foreach ($stopped as $session) {
                    $tableData[] = [
                        $session['session_id'],
                        $session['reservation_id'] ?? 'N/A',
                        $this->formatStopReason($session['reason'] ?? 'unknown')
                    ];
                }

                $this->table(
                    ['Session ID', 'Réservation ID', 'Raison'],
                    $tableData
                );
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception lors du monitoring:');
            $this->error($e->getMessage());
            
            Log::error('MonitorChargingSessions: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Vérifier les sessions en mode dry-run (sans les arrêter)
     */
    protected function checkSessionsDryRun(): array
    {
        try {
            $activeSessions = \App\Models\ChargingSession::where('status', 'active')
                ->with(['reservation', 'chargingPoint'])
                ->get();

            $wouldStop = [];

            foreach ($activeSessions as $session) {
                $shouldStop = false;
                $stopReason = '';

                // Vérifier si la période de réservation est terminée
                if ($session->reservation && $session->reservation->end_time) {
                    if (now()->isAfter($session->reservation->end_time)) {
                        $shouldStop = true;
                        $stopReason = 'reservation_period_ended';
                    }
                }

                // Vérifier le crédit pour le mode postpayé
                if ($session->payment_mode === 'postpaid' && $session->reservation) {
                    $user = $session->reservation->user;
                    $wallet = $user->getOrCreateWallet();
                    $minThreshold = $session->reservation->min_threshold ?? 5.00;

                    if (!$wallet->hasSufficientBalance($minThreshold)) {
                        $shouldStop = true;
                        $stopReason = 'insufficient_credit';
                    }
                }

                // Vérifier la durée maximale
                $maxDuration = $session->estimated_duration ?? 480;
                $sessionDuration = now()->diffInMinutes($session->started_at);
                
                if ($sessionDuration > ($maxDuration * 1.5)) {
                    $shouldStop = true;
                    $stopReason = 'max_duration_exceeded';
                }

                if ($shouldStop) {
                    $wouldStop[] = [
                        'session_id' => $session->id,
                        'reservation_id' => $session->reservation_id,
                        'reason' => $stopReason,
                        'duration' => $sessionDuration
                    ];
                }
            }

            return [
                'success' => true,
                'checked' => $activeSessions->count(),
                'stopped' => $wouldStop
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formater la raison d'arrêt pour l'affichage
     */
    protected function formatStopReason(string $reason): string
    {
        return match($reason) {
            'reservation_period_ended' => '⏰ Période de réservation terminée',
            'insufficient_credit' => '💳 Crédit insuffisant',
            'max_duration_exceeded' => '⚠️  Durée maximale dépassée',
            'manual' => '✋ Arrêt manuel',
            default => '❓ ' . $reason
        };
    }
}
