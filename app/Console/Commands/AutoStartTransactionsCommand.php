<?php

namespace App\Console\Commands;

use App\Services\AutoRemoteStartService;
use App\Jobs\AutoStartTransactionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Commande pour traiter automatiquement les démarrages de transactions OCPP
 * 
 * Cette commande est exécutée par le scheduler Laravel pour:
 * - Détecter les réservations prêtes à démarrer
 * - Lancer les jobs de démarrage automatique
 * - Fournir des statistiques en temps réel
 * - Permettre l'exécution manuelle pour tests
 */
class AutoStartTransactionsCommand extends Command
{
    /**
     * Nom et signature de la commande
     *
     * @var string
     */
    protected $signature = 'ocpp:auto-start-transactions
                            {--queue : Mettre les démarrages en queue au lieu de les exécuter directement}
                            {--force-reservation= : Forcer le démarrage d\'une réservation spécifique (ID)}
                            {--dry-run : Afficher ce qui serait fait sans exécuter}
                            {--stats : Afficher uniquement les statistiques}
                            {--health : Vérifier la santé du système}';

    /**
     * Description de la commande
     *
     * @var string
     */
    protected $description = 'Traite automatiquement les démarrages de transactions OCPP pour les réservations éligibles';

    /**
     * Service de démarrage automatique
     *
     * @var AutoRemoteStartService
     */
    protected $autoStartService;

    /**
     * Créer une nouvelle instance de la commande
     *
     * @param AutoRemoteStartService $autoStartService
     * @return void
     */
    public function __construct(AutoRemoteStartService $autoStartService)
    {
        parent::__construct();
        $this->autoStartService = $autoStartService;
    }

    /**
     * Exécuter la commande
     *
     * @return int
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('   🚗⚡ Auto Start Transactions OCPP');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        try {
            // Option: Health Check
            if ($this->option('health')) {
                return $this->handleHealthCheck();
            }

            // Option: Statistiques uniquement
            if ($this->option('stats')) {
                return $this->handleStats();
            }

            // Option: Forcer une réservation spécifique
            if ($this->option('force-reservation')) {
                return $this->handleForceReservation();
            }

            // Traitement normal
            return $this->handleNormalProcessing();

        } catch (Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            Log::error('AutoStartTransactionsCommand: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Gérer le traitement normal des réservations
     *
     * @return int
     */
    protected function handleNormalProcessing(): int
    {
        $this->info('🔍 Recherche des réservations éligibles...');
        $this->newLine();

        // Mode dry-run: afficher sans exécuter
        if ($this->option('dry-run')) {
            $this->warn('⚠️  MODE DRY-RUN: Aucune action ne sera exécutée');
            $this->newLine();
        }

        // Traiter les réservations
        if ($this->option('queue')) {
            // Mode queue: dispatcher les jobs
            $result = $this->processWithQueue();
        } else {
            // Mode synchrone: traiter directement
            $result = $this->autoStartService->processAllEligibleReservations();
        }

        // Afficher les résultats
        $this->displayResults($result);

        // Retourner le code de sortie
        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Traiter les réservations en mode queue
     *
     * @return array
     */
    protected function processWithQueue(): array
    {
        $this->info('📤 Mode Queue: Dispatch des jobs...');
        
        // Récupérer les réservations éligibles
        $eligibleReservations = $this->getEligibleReservationsForDisplay();
        
        if ($this->option('dry-run')) {
            return [
                'success' => true,
                'processed' => count($eligibleReservations),
                'started' => 0,
                'failed' => 0,
                'skipped' => 0,
                'queued' => count($eligibleReservations)
            ];
        }

        $queued = 0;
        foreach ($eligibleReservations as $reservation) {
            try {
                AutoStartTransactionJob::dispatch($reservation['id']);
                $queued++;
                
                $this->line("  ✓ Job en queue pour réservation #{$reservation['id']} - {$reservation['user']}");
            } catch (Exception $e) {
                $this->error("  ✗ Erreur queue réservation #{$reservation['id']}: {$e->getMessage()}");
            }
        }

        return [
            'success' => true,
            'processed' => count($eligibleReservations),
            'queued' => $queued,
            'started' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
    }

    /**
     * Forcer le démarrage d'une réservation spécifique
     *
     * @return int
     */
    protected function handleForceReservation(): int
    {
        $reservationId = (int) $this->option('force-reservation');
        
        $this->warn('⚠️  FORCE START: Réservation #' . $reservationId);
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Mode dry-run: Aucune action exécutée');
            return Command::SUCCESS;
        }

        $this->info('🚀 Démarrage forcé en cours...');

        try {
            if ($this->option('queue')) {
                // Dispatcher le job avec force=true
                AutoStartTransactionJob::dispatch($reservationId, true);
                $this->info('✅ Job de démarrage forcé mis en queue');
            } else {
                // Exécution directe
                $result = $this->autoStartService->forceStartReservation($reservationId);
                
                if ($result['success']) {
                    $this->info('✅ Transaction démarrée avec succès');
                    $this->table(
                        ['Clé', 'Valeur'],
                        [
                            ['Réservation ID', $reservationId],
                            ['Status', 'SUCCESS'],
                            ['Message', $result['message']],
                            ['Durée', $result['duration_ms'] . ' ms']
                        ]
                    );
                } else {
                    $this->error('❌ Échec du démarrage');
                    $this->error('Raison: ' . $result['message']);
                    return Command::FAILURE;
                }
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Afficher les statistiques
     *
     * @return int
     */
    protected function handleStats(): int
    {
        $this->info('📊 Statistiques des démarrages automatiques');
        $this->newLine();

        // Stats des dernières 24h
        $stats24h = $this->autoStartService->getAutoStartStats([
            'date_from' => now()->subHours(24)
        ]);

        // Stats des 7 derniers jours
        $stats7d = $this->autoStartService->getAutoStartStats([
            'date_from' => now()->subDays(7)
        ]);

        // Stats du mois en cours
        $statsMonth = $this->autoStartService->getAutoStartStats([
            'date_from' => now()->startOfMonth()
        ]);

        $this->table(
            ['Période', 'Total', 'Succès', 'Échecs', 'Erreurs', 'Taux succès'],
            [
                [
                    'Dernières 24h',
                    $stats24h['total'],
                    $stats24h['success'],
                    $stats24h['failed'],
                    $stats24h['errors'],
                    $stats24h['success_rate'] . '%'
                ],
                [
                    'Derniers 7 jours',
                    $stats7d['total'],
                    $stats7d['success'],
                    $stats7d['failed'],
                    $stats7d['errors'],
                    $stats7d['success_rate'] . '%'
                ],
                [
                    'Mois en cours',
                    $statsMonth['total'],
                    $statsMonth['success'],
                    $statsMonth['failed'],
                    $statsMonth['errors'],
                    $statsMonth['success_rate'] . '%'
                ]
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Vérifier la santé du système
     *
     * @return int
     */
    protected function handleHealthCheck(): int
    {
        $this->info('🏥 Vérification de la santé du système...');
        $this->newLine();

        $health = $this->autoStartService->healthCheck();

        // Afficher le statut global
        if ($health['healthy']) {
            $this->info('✅ Système en bonne santé');
        } else {
            $this->error('❌ Système dégradé');
        }
        $this->newLine();

        // Afficher la configuration
        $this->info('⚙️  Configuration:');
        foreach ($health['config'] as $key => $value) {
            $this->line("  • {$key}: " . ($value === true ? 'Oui' : ($value === false ? 'Non' : $value)));
        }
        $this->newLine();

        // Afficher les problèmes
        if (!empty($health['issues'])) {
            $this->error('🔴 Problèmes critiques:');
            foreach ($health['issues'] as $issue) {
                $this->error("  • {$issue}");
            }
            $this->newLine();
        }

        // Afficher les avertissements
        if (!empty($health['warnings'])) {
            $this->warn('🟡 Avertissements:');
            foreach ($health['warnings'] as $warning) {
                $this->warn("  • {$warning}");
            }
            $this->newLine();
        }

        if (empty($health['issues']) && empty($health['warnings'])) {
            $this->info('✨ Aucun problème détecté');
        }

        return $health['healthy'] ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Afficher les résultats du traitement
     *
     * @param array $result
     * @return void
     */
    protected function displayResults(array $result): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        $this->info('   📋 Résultats du traitement');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $tableData = [
            ['Réservations traitées', $result['processed']],
            ['Transactions démarrées', $result['started'] ?? 0],
            ['Échecs', $result['failed']],
            ['Ignorées', $result['skipped']],
        ];

        if (isset($result['queued'])) {
            $tableData[] = ['Jobs en queue', $result['queued']];
        }

        if (isset($result['duration_ms'])) {
            $tableData[] = ['Durée totale', $result['duration_ms'] . ' ms'];
        }

        $this->table(['Métrique', 'Valeur'], $tableData);

        // Afficher les erreurs détaillées si présentes
        if (!empty($result['errors'])) {
            $this->newLine();
            $this->error('⚠️  Erreurs détaillées:');
            foreach ($result['errors'] as $error) {
                $this->error("  • Réservation #{$error['reservation_id']}: {$error['error']}");
            }
        }

        // Résumé coloré
        $this->newLine();
        if ($result['started'] > 0) {
            $this->info("✅ {$result['started']} transaction(s) démarrée(s) avec succès");
        }
        if ($result['failed'] > 0) {
            $this->error("❌ {$result['failed']} échec(s)");
        }
        if ($result['skipped'] > 0) {
            $this->warn("⏭️  {$result['skipped']} réservation(s) ignorée(s)");
        }
    }

    /**
     * Récupérer les réservations éligibles pour affichage
     *
     * @return array
     */
    protected function getEligibleReservationsForDisplay(): array
    {
        // Utiliser la même logique que le service
        $now = now();
        $startWindow = $now->copy()->addMinutes(config('auto-remote-start.start_window_minutes', 15));
        $gracePeriod = $now->copy()->subMinutes(config('auto-remote-start.grace_period_minutes', 5));

        $reservations = \App\Models\Reservation::with(['chargingPoint', 'user'])
            ->where(function ($query) use ($now, $startWindow, $gracePeriod) {
                $query->whereBetween('start_time', [$gracePeriod, $startWindow])
                    ->orWhere(function ($q) use ($now, $gracePeriod) {
                        $q->where('start_time', '<=', $now)
                          ->where('start_time', '>=', $gracePeriod);
                    });
            })
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            ->where('payment_status', 'PAID')
            ->whereNotIn('status', ['active', 'completed', 'cancelled'])
            ->orderBy('start_time', 'asc')
            ->limit(50)
            ->get();

        return $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'user' => $reservation->user->name ?? 'N/A',
                'charging_point' => $reservation->chargingPoint->name ?? 'N/A',
                'start_time' => $reservation->start_time->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }
}

