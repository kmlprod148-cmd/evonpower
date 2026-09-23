<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Services\OcppBusinessService;
use App\Jobs\StopChargingSessionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour appliquer les limites de charge
 * 
 * TRÈS IMPORTANT:
 * Cette commande DOIT s'exécuter toutes les 1-2 minutes via le scheduler
 * 
 * RESPONSABILITÉS:
 * - Vérifier toutes les sessions actives
 * - Arrêter celles qui dépassent les limites:
 *   ❌ Balance utilisateur ≤ 0
 *   ❌ Heure de fin de réservation atteinte
 *   ❌ kWh consommés ≥ max_kwh
 *   ❌ Durée ≥ max_minutes
 * 
 * RAPPEL: SteVe NE FAIT PAS ça automatiquement!
 * C'est VOTRE responsabilité de surveiller et d'arrêter.
 */
class EnforceChargingLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charging:enforce-limits
                            {--dry-run : Afficher ce qui serait fait sans exécuter}
                            {--session= : ID de session spécifique à vérifier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applique les limites de charge (balance, temps, kWh) sur les sessions actives';

    protected OcppBusinessService $ocppService;

    /**
     * Create a new command instance.
     */
    public function __construct(OcppBusinessService $ocppService)
    {
        parent::__construct();
        $this->ocppService = $ocppService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $isDryRun = $this->option('dry-run');
        $specificSession = $this->option('session');

        $this->info("═══════════════════════════════════════════════");
        $this->info("  Enforcement des Limites de Charge");
        $this->info("═══════════════════════════════════════════════");
        
        if ($isDryRun) {
            $this->warn("Mode DRY-RUN: Aucune action ne sera exécutée");
        }

        Log::info("EnforceChargingLimits: Début", [
            'dry_run' => $isDryRun,
            'specific_session' => $specificSession,
        ]);

        // 1. Récupérer toutes les sessions actives
        $query = ChargingSession::with([
            'reservation.pricingPlan',
            'user',
            'chargingPoint'
        ])->whereIn('status', [
            ChargingSession::STATUS_ACTIVE,
            ChargingSession::STATUS_PENDING,
            ChargingSession::STATUS_INITIATING,
            ChargingSession::STATUS_IN_PROGRESS,
        ]);

        if ($specificSession) {
            $query->where('id', $specificSession);
        }

        $activeSessions = $query->get();

        $this->info("\n📊 Sessions actives trouvées: {$activeSessions->count()}");

        if ($activeSessions->isEmpty()) {
            $this->info("✅ Aucune session active à vérifier");
            return Command::SUCCESS;
        }

        // 2. Vérifier chaque session
        $sessionsToStop = [];
        
        foreach ($activeSessions as $session) {
            $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🔍 Vérification Session #{$session->id}");
            $userName = $session->user->name ?? 'N/A';
            $chargingPointName = $session->chargingPoint->name ?? 'N/A';
            $this->line("   Utilisateur: {$userName}");
            $this->line("   Borne: {$chargingPointName}");
            $this->line("   Démarré: {$session->started_at}");

            $shouldStop = $this->ocppService->shouldStopSession($session);

            if ($shouldStop['should_stop']) {
                $this->warn("   ⚠️  DOIT ÊTRE ARRÊTÉE");
                
                foreach ($shouldStop['reasons'] as $reason) {
                    $this->error("      • {$reason}");
                }

                $sessionsToStop[] = [
                    'session' => $session,
                    'reasons' => $shouldStop['reasons'],
                ];
            } else {
                $this->info("   ✅ OK - Continue");
                
                // Afficher les métriques actuelles
                $this->displaySessionMetrics($session);
            }
        }

        // 3. Arrêter les sessions qui dépassent les limites
        if (empty($sessionsToStop)) {
            $this->info("\n✅ Toutes les sessions sont dans les limites");
            return Command::SUCCESS;
        }

        $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $stopCount = count($sessionsToStop);
        $this->warn("\n⚡ {$stopCount} session(s) à arrêter");

        foreach ($sessionsToStop as $item) {
            $session = $item['session'];
            $reasons = $item['reasons'];
            $reasonText = implode(', ', $reasons);

            $this->line("\n🛑 Arrêt Session #{$session->id}");
            $this->line("   Raison: {$reasonText}");

            if (!$isDryRun) {
                try {
                    // Dispatcher le job d'arrêt
                    StopChargingSessionJob::dispatch($session->id, $reasonText);
                    
                    $this->info("   ✅ Job d'arrêt dispatché");
                    
                    Log::info("EnforceChargingLimits: Session arrêtée", [
                        'session_id' => $session->id,
                        'reasons' => $reasons,
                    ]);

                } catch (\Exception $e) {
                    $this->error("   ❌ Erreur: {$e->getMessage()}");
                    
                    Log::error("EnforceChargingLimits: Erreur d'arrêt", [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->comment("   [DRY-RUN] Job d'arrêt SERAIT dispatché");
            }
        }

        // 4. Résumé
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("\n📈 RÉSUMÉ");
        $this->line("   • Sessions vérifiées: {$activeSessions->count()}");
        $this->line("   • Sessions arrêtées: " . count($sessionsToStop));
        $this->line("   • Durée: {$duration}ms");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");

        Log::info("EnforceChargingLimits: Terminé", [
            'total_sessions' => $activeSessions->count(),
            'stopped_sessions' => count($sessionsToStop),
            'duration_ms' => $duration,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Afficher les métriques d'une session
     */
    protected function displaySessionMetrics(ChargingSession $session): void
    {
        $reservation = $session->reservation;
        
        // Durée actuelle
        $currentDuration = now()->diffInMinutes($session->started_at);
        $maxDuration = $reservation?->max_minutes ?? '∞';
        
        $this->line("      Durée: {$currentDuration} / {$maxDuration} min");

        // Énergie
        $currentEnergy = $this->ocppService->calculateConsumedEnergy($session);
        $maxEnergy = $reservation?->max_kwh ?? '∞';
        
        $this->line("      Énergie: " . number_format($currentEnergy, 2) . " / {$maxEnergy} kWh");

        // Solde utilisateur
        $balance = $session->user?->balance ?? 0;
        $this->line("      Solde: " . number_format($balance, 2) . " €");

        // Coût estimé actuel
        $estimatedCost = $this->ocppService->calculateSessionCost($session);
        $this->line("      Coût estimé: " . number_format($estimatedCost, 2) . " €");
    }
}

