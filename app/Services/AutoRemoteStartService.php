<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\Connector;
use App\Models\OcppTag;
use App\Models\AutoRemoteStartLog;
use App\Services\OcppTagRemoteOperationsService;
use App\Services\OcppBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

/**
 * Service d'automatisation pour RemoteStartTransaction
 * 
 * Ce service gère le démarrage automatique des transactions OCPP basé sur:
 * - Réservations approuvées et payées
 * - Horaires de début programmés
 * - Disponibilité des connecteurs
 * - Solde utilisateur suffisant
 * - Conditions de démarrage automatique configurées
 * 
 * Fonctionnalités:
 * - Détection automatique des réservations prêtes
 * - Démarrage automatique avec retry
 * - Monitoring et logging complet
 * - Gestion des erreurs et rollback
 * - Support multi-connecteur
 * - Validation complète avant démarrage
 */
class AutoRemoteStartService
{
    protected OcppTagRemoteOperationsService $ocppService;
    protected OcppBusinessService $businessService;
    protected int $maxRetryAttempts;
    protected int $retryDelaySeconds;
    protected bool $autoStartEnabled;
    protected int $startWindowMinutes;
    protected int $gracePeriodMinutes;

    public function __construct(
        OcppTagRemoteOperationsService $ocppService,
        OcppBusinessService $businessService
    ) {
        $this->ocppService = $ocppService;
        $this->businessService = $businessService;
        
        // Charger la configuration
        $this->maxRetryAttempts = config('auto-remote-start.max_retry_attempts', 3);
        $this->retryDelaySeconds = config('auto-remote-start.retry_delay_seconds', 30);
        $this->autoStartEnabled = config('auto-remote-start.enabled', true);
        $this->startWindowMinutes = config('auto-remote-start.start_window_minutes', 15);
        $this->gracePeriodMinutes = config('auto-remote-start.grace_period_minutes', 5);
    }

    /**
     * Traiter toutes les réservations éligibles pour démarrage automatique
     * 
     * @return array Statistiques du traitement
     */
    public function processAllEligibleReservations(): array
    {
        $startTime = microtime(true);
        
        if (!$this->autoStartEnabled) {
            return [
                'success' => false,
                'message' => 'Le démarrage automatique est désactivé dans la configuration',
                'processed' => 0,
                'started' => 0,
                'failed' => 0,
                'skipped' => 0
            ];
        }

        Log::info('AutoRemoteStart: Début du traitement des réservations éligibles');

        // Récupérer les réservations éligibles
        $eligibleReservations = $this->getEligibleReservations();
        
        $stats = [
            'processed' => count($eligibleReservations),
            'started' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        foreach ($eligibleReservations as $reservation) {
            try {
                $result = $this->processReservation($reservation);
                
                if ($result['success']) {
                    $stats['started']++;
                    Log::info('AutoRemoteStart: Transaction démarrée automatiquement', [
                        'reservation_id' => $reservation->id,
                        'charging_point_id' => $reservation->charging_point_id,
                        'user_id' => $reservation->user_id
                    ]);
                } elseif ($result['skipped']) {
                    $stats['skipped']++;
                } else {
                    $stats['failed']++;
                    $stats['errors'][] = [
                        'reservation_id' => $reservation->id,
                        'error' => $result['message']
                    ];
                }
            } catch (Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('AutoRemoteStart: Exception lors du traitement', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info('AutoRemoteStart: Traitement terminé', [
            'stats' => $stats,
            'duration_ms' => $duration
        ]);

        return array_merge($stats, [
            'success' => true,
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Traiter une réservation spécifique pour démarrage automatique
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function processReservation(Reservation $reservation): array
    {
        $startTime = microtime(true);

        $eligibility = $this->checkReservationEligibility($reservation);
        
        if (!$eligibility['eligible']) {
            $this->appendReservationOcppLog($reservation, 'skipped', [
                'reason' => $eligibility['reason'],
                'code' => $eligibility['code'] ?? null,
            ]);
            $this->logAutoStart($reservation, 'skipped', [
                'message' => $eligibility['reason'],
                'eligibility_data' => $eligibility,
            ]);

            return [
                'success' => false,
                'skipped' => true,
                'message' => $eligibility['reason'],
                'reservation_id' => $reservation->id
            ];
        }

        // Récupérer le lock pour éviter les doublons
        $lockKey = "auto_start_reservation_{$reservation->id}";
        $lock = Cache::lock($lockKey, 120); // 2 minutes

        if (!$lock->get()) {
            return [
                'success' => false,
                'skipped' => true,
                'message' => 'Une autre instance traite déjà cette réservation',
                'reservation_id' => $reservation->id
            ];
        }

        try {
            $reservation->refresh();

            $eligibility = $this->checkReservationEligibility($reservation);
            if (!$eligibility['eligible']) {
                $lock->release();

                $this->appendReservationOcppLog($reservation, 'skipped', [
                    'reason' => $eligibility['reason'],
                    'code' => $eligibility['code'] ?? null,
                ]);
                $this->logAutoStart($reservation, 'skipped', [
                    'message' => $eligibility['reason'],
                    'eligibility_data' => $eligibility,
                ]);

                return [
                    'success' => false,
                    'skipped' => true,
                    'message' => $eligibility['reason'],
                    'reservation_id' => $reservation->id
                ];
            }

            $reservation->update([
                'session_initiation_status' => 'processing',
                'last_error' => null,
                'last_error_at' => null,
            ]);

            $result = $this->startTransactionWithRetry($reservation);

            $lock->release();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                $reservation->update([
                    'status' => 'active',
                    'confirmed_at' => $reservation->confirmed_at ?? now(),
                    'session_initiated_at' => now(),
                    'session_initiation_status' => 'success',
                    'charging_session_id' => $result['charging_session_id'] ?? $reservation->charging_session_id,
                    'actual_start_time' => now(),
                    'last_error' => null,
                    'last_error_at' => null,
                ]);

                $this->appendReservationOcppLog($reservation, 'success', [
                    'message' => 'Remote start accepted by SteVe',
                    'result' => $result,
                    'duration_ms' => $duration,
                ]);
                $this->logAutoStart($reservation, 'success', array_merge($result, [
                    'duration_ms' => $duration,
                    'eligibility_data' => $eligibility,
                ]));

                return [
                    'success' => true,
                    'message' => 'Transaction démarrée automatiquement avec succès',
                    'reservation_id' => $reservation->id,
                    'transaction_data' => $result['data'] ?? null,
                    'duration_ms' => $duration
                ];
            } else {
                $failureMessage = $result['message'] ?? 'Échec du démarrage automatique';

                $reservation->update([
                    'session_initiation_status' => 'failed',
                    'last_error' => $failureMessage,
                    'last_error_at' => now(),
                ]);

                $this->appendReservationOcppLog($reservation, 'failed', [
                    'message' => $failureMessage,
                    'result' => $result,
                    'duration_ms' => $duration,
                ]);
                $this->logAutoStart($reservation, 'failed', array_merge($result, [
                    'message' => $failureMessage,
                    'duration_ms' => $duration,
                    'eligibility_data' => $eligibility,
                ]));

                return [
                    'success' => false,
                    'skipped' => false,
                    'message' => $failureMessage,
                    'reservation_id' => $reservation->id,
                    'error' => $result['error'] ?? null,
                    'duration_ms' => $duration
                ];
            }

        } catch (Exception $e) {
            $lock->release();

            $reservation->update([
                'session_initiation_status' => 'failed',
                'last_error' => $e->getMessage(),
                'last_error_at' => now(),
            ]);

            $this->appendReservationOcppLog($reservation, 'error', [
                'message' => $e->getMessage(),
            ]);
            $this->logAutoStart($reservation, 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            throw $e;
        }
    }

    /**
     * Récupérer toutes les réservations éligibles pour démarrage automatique
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getEligibleReservations()
    {
        $now = now();
        $startWindow = $now->copy()->addMinutes($this->startWindowMinutes);
        $gracePeriod = $now->copy()->subMinutes($this->gracePeriodMinutes);

        return Reservation::with(['chargingPoint', 'connector', 'user', 'ocppTag'])
            ->where(function ($query) use ($now, $startWindow, $gracePeriod) {
                // Réservations dont l'heure de début est dans la fenêtre de démarrage
                $query->whereBetween('start_time', [$gracePeriod, $startWindow])
                    // OU réservations déjà passées mais non démarrées (avec période de grâce)
                    ->orWhere(function ($q) use ($now, $gracePeriod) {
                        $q->where('start_time', '<=', $now)
                          ->where('start_time', '>=', $gracePeriod);
                    });
            })
            // Statuts éligibles
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            // Paiement validé (case-insensitive)
            ->where(function ($query) {
                $query->whereIn('payment_status', ['PAID', 'PAYE', 'paid', 'paye']);
            })
            // Pas encore active
            ->whereNotIn('status', ['active', 'completed', 'cancelled'])
            // Pas de session de charge déjà créée
            ->whereDoesntHave('chargingSessions', function ($query) {
                $query->whereIn('status', ['active', 'in_progress']);
            })
            ->orderBy('start_time', 'asc')
            ->limit(50) // Limiter pour éviter la surcharge
            ->get();
    }

    /**
     * Vérifier l'éligibilité d'une réservation pour démarrage automatique
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function checkReservationEligibility(Reservation $reservation): array
    {
        // 1. Vérifier que la réservation est approuvée
        if (!$reservation->isApproved()) {
            return [
                'eligible' => false,
                'reason' => 'Réservation non approuvée ou paiement non validé',
                'code' => 'NOT_APPROVED'
            ];
        }

        // 2. Vérifier que l'heure de début est appropriée
        if (!$this->isStartTimeAppropriate($reservation)) {
            return [
                'eligible' => false,
                'reason' => 'Heure de début non appropriée',
                'code' => 'INVALID_START_TIME'
            ];
        }

        // 3. Vérifier que le point de charge existe et est en ligne
        if (!$reservation->chargingPoint) {
            return [
                'eligible' => false,
                'reason' => 'Point de charge introuvable',
                'code' => 'CHARGING_POINT_NOT_FOUND'
            ];
        }

        if (!$reservation->chargingPoint->isOnline()) {
            return [
                'eligible' => false,
                'reason' => 'Point de charge hors ligne',
                'code' => 'CHARGING_POINT_OFFLINE'
            ];
        }

        // 4. Vérifier que le connecteur existe et est disponible
        if (!$reservation->connector) {
            return [
                'eligible' => false,
                'reason' => 'Connecteur introuvable',
                'code' => 'CONNECTOR_NOT_FOUND'
            ];
        }

        // Vérifier la disponibilité du connecteur via Steve
        $connectorAvailable = $this->isConnectorAvailable($reservation);
        if (!$connectorAvailable) {
            return [
                'eligible' => false,
                'reason' => 'Connecteur non disponible',
                'code' => 'CONNECTOR_NOT_AVAILABLE'
            ];
        }

        // 5. Vérifier que l'utilisateur a un tag OCPP valide
        $ocppTag = $reservation->getOcppTag();
        if (!$ocppTag) {
            return [
                'eligible' => false,
                'reason' => 'Aucun tag OCPP disponible pour l\'utilisateur',
                'code' => 'NO_OCPP_TAG'
            ];
        }

        // Valider le tag OCPP via Steve
        $tagValidation = $this->ocppService->validateOcppTag($ocppTag->ocpp_tag);
        if (!$tagValidation['valid']) {
            return [
                'eligible' => false,
                'reason' => 'Tag OCPP invalide: ' . $tagValidation['message'],
                'code' => 'INVALID_OCPP_TAG'
            ];
        }

        // 6. Vérifier le solde utilisateur (si mode postpaid)
        if ($reservation->isPostpaid() && !$reservation->userHasSufficientBalance()) {
            return [
                'eligible' => false,
                'reason' => 'Solde utilisateur insuffisant',
                'code' => 'INSUFFICIENT_BALANCE'
            ];
        }

        // 7. Vérifier qu'il n'y a pas déjà une session active
        $hasActiveSession = ChargingSession::where('reservation_id', $reservation->id)
            ->whereIn('status', ['active', 'in_progress'])
            ->exists();

        if ($hasActiveSession) {
            return [
                'eligible' => false,
                'reason' => 'Session de charge déjà active',
                'code' => 'SESSION_ALREADY_ACTIVE'
            ];
        }

        // 8. Vérifier les tentatives précédentes (éviter les boucles infinies)
        $recentFailures = $this->getRecentFailureCount($reservation);
        if ($recentFailures >= $this->maxRetryAttempts) {
            return [
                'eligible' => false,
                'reason' => 'Nombre maximum de tentatives atteint',
                'code' => 'MAX_RETRIES_REACHED'
            ];
        }

        // Toutes les vérifications passées
        return [
            'eligible' => true,
            'reason' => 'Réservation éligible pour démarrage automatique',
            'code' => 'ELIGIBLE',
            'ocpp_tag' => $ocppTag->ocpp_tag,
            'connector_id' => $reservation->connector->connector_id
        ];
    }

    /**
     * Vérifier si l'heure de début est appropriée
     * 
     * @param Reservation $reservation
     * @return bool
     */
    protected function isStartTimeAppropriate(Reservation $reservation): bool
    {
        if (!$reservation->start_time) {
            return false;
        }

        $now = now();
        $startTime = Carbon::parse($reservation->start_time);
        
        // Période de grâce avant l'heure de début
        $gracePeriodStart = $startTime->copy()->subMinutes($this->gracePeriodMinutes);
        
        // Fenêtre de démarrage après l'heure prévue
        $startWindow = $startTime->copy()->addMinutes($this->startWindowMinutes);

        // L'heure actuelle doit être dans la fenêtre [début - grâce, début + fenêtre]
        return $now->between($gracePeriodStart, $startWindow);
    }

    /**
     * Vérifier si le connecteur est disponible
     * 
     * @param Reservation $reservation
     * @return bool
     */
    protected function isConnectorAvailable(Reservation $reservation): bool
    {
        try {
            $chargeBoxId = $reservation->chargingPoint->charge_box_id 
                ?? $reservation->chargingPoint->steve_charging_point_id;
            
            $connectorId = $reservation->connector->connector_id;

            // Utiliser le service métier pour vérifier la disponibilité
            return $this->businessService->isConnectorAvailable($chargeBoxId, $connectorId);

        } catch (Exception $e) {
            Log::error('AutoRemoteStart: Erreur vérification disponibilité connecteur', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Démarrer une transaction avec mécanisme de retry
     * 
     * @param Reservation $reservation
     * @return array
     */
    protected function startTransactionWithRetry(Reservation $reservation): array
    {
        $ocppTag = $reservation->getOcppTag();
        $connectorId = $reservation->connector->connector_id;
        $chargingPoint = $reservation->chargingPoint;

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetryAttempts) {
            $attempt++;

            try {
                Log::info('AutoRemoteStart: Tentative de démarrage', [
                    'reservation_id' => $reservation->id,
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxRetryAttempts,
                    'charging_point_id' => $chargingPoint->id,
                    'connector_id' => $connectorId,
                    'ocpp_tag' => $ocppTag->ocpp_tag
                ]);

                // Appeler le service OCPP pour démarrer la transaction
                $result = $this->ocppService->remoteStartTransaction(
                    $chargingPoint,
                    $ocppTag->ocpp_tag,
                    $connectorId,
                    [
                        'reservation_id' => $reservation->id,
                        'auto_started' => true
                    ]
                );

                if ($result['success']) {
                    Log::info('AutoRemoteStart: Transaction démarrée avec succès', [
                        'reservation_id' => $reservation->id,
                        'attempt' => $attempt,
                        'result' => $result
                    ]);

                    $chargingSession = $this->createChargingSession($reservation, $result);
                    $result['attempt'] = $attempt;
                    $result['charging_session_id'] = $chargingSession?->id;
                    $result['charging_session_uuid'] = $chargingSession?->session_id;
                    $result['steve_transaction_id'] = $chargingSession?->steve_transaction_id;

                    return $result;
                }

                $lastError = $result['message'] ?? 'Erreur inconnue';
                Log::warning('AutoRemoteStart: Tentative échouée', [
                    'reservation_id' => $reservation->id,
                    'attempt' => $attempt,
                    'error' => $lastError
                ]);

            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::error('AutoRemoteStart: Exception lors de la tentative', [
                    'reservation_id' => $reservation->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Attendre avant la prochaine tentative (sauf si c'était la dernière)
            if ($attempt < $this->maxRetryAttempts) {
                sleep($this->retryDelaySeconds);
            }
        }

        // Toutes les tentatives ont échoué
        return [
            'success' => false,
            'message' => "Échec après {$attempt} tentatives",
            'error' => $lastError,
            'attempts' => $attempt,
        ];
    }

    /**
     * Créer une session de charge pour la réservation
     * 
     * @param Reservation $reservation
     * @param array $startResult
     * @return ChargingSession|null
     */
    protected function createChargingSession(Reservation $reservation, array $startResult): ?ChargingSession
    {
        try {
            $transactionId = $startResult['data']['transaction']['id']
                ?? $startResult['transaction_id']
                ?? null;
            $ocppTag = $reservation->getOcppTag();

            $session = ChargingSession::create([
                'session_id' => (string) Str::uuid(),
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'charging_point_id' => $reservation->charging_point_id,
                'steve_transaction_id' => $transactionId ? (string) $transactionId : null,
                'connector_id' => $reservation->connector?->connector_id ?? $reservation->connector_id ?? 1,
                'ocpp_tag' => $ocppTag?->ocpp_tag,
                'payment_status' => 'paid',
                'status' => ChargingSession::STATUS_ACTIVE,
                'started_at' => now(),
                'payment_mode' => $reservation->payment_mode ?: ChargingSession::PAYMENT_MODE_PREPAID,
                'prepaid_amount' => $reservation->prepaid_amount,
                'estimated_cost' => $reservation->estimated_cost,
                'estimated_energy' => $reservation->estimated_energy,
                'estimated_duration' => $reservation->estimated_duration,
                'steve_response' => $startResult['data'] ?? null,
                'metadata' => [
                    'auto_start_result' => $startResult,
                    'auto_started' => true,
                    'auto_started_at' => now()->toISOString(),
                ],
            ]);

            Log::info('AutoRemoteStart: Session de charge créée', [
                'reservation_id' => $reservation->id,
                'session_id' => $session->id,
                'transaction_id' => $transactionId
            ]);

            return $session;

        } catch (Exception $e) {
            Log::error('AutoRemoteStart: Erreur création session de charge', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtenir le nombre d'échecs récents pour une réservation
     * 
     * @param Reservation $reservation
     * @return int
     */
    protected function getRecentFailureCount(Reservation $reservation): int
    {
        $cacheKey = "auto_start_failures_{$reservation->id}";
        return (int) Cache::get($cacheKey, 0);
    }

    /**
     * Incrémenter le compteur d'échecs
     * 
     * @param Reservation $reservation
     * @return void
     */
    protected function incrementFailureCount(Reservation $reservation): void
    {
        $cacheKey = "auto_start_failures_{$reservation->id}";
        $count = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, 3600); // 1 heure
    }

    /**
     * Réinitialiser le compteur d'échecs
     * 
     * @param Reservation $reservation
     * @return void
     */
    protected function resetFailureCount(Reservation $reservation): void
    {
        $cacheKey = "auto_start_failures_{$reservation->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Logger une tentative de démarrage automatique
     * 
     * @param Reservation $reservation
     * @param string $status
     * @param array $data
     * @return void
     */
    protected function logAutoStart(Reservation $reservation, string $status, array $data = []): void
    {
        try {
            AutoRemoteStartLog::create([
                'reservation_id' => $reservation->id,
                'charging_point_id' => $reservation->charging_point_id,
                'user_id' => $reservation->user_id,
                'connector_id' => $reservation->connector_id,
                'status' => in_array($status, ['success', 'failed', 'skipped', 'error', 'retry'], true)
                    ? $status
                    : 'error',
                'code' => $data['code'] ?? null,
                'message' => $data['message'] ?? null,
                'attempt_number' => (int) ($data['attempt'] ?? $data['attempt_number'] ?? 1),
                'max_attempts' => $this->maxRetryAttempts,
                'is_final_attempt' => (bool) ($data['is_final_attempt']
                    ?? (($data['attempt'] ?? $data['attempts'] ?? 1) >= $this->maxRetryAttempts)),
                'auto_started' => true,
                'ocpp_tag' => $reservation->getOcppTag()?->ocpp_tag,
                'connector_number' => $reservation->connector?->connector_id,
                'charge_box_id' => $reservation->chargingPoint?->charge_box_id
                    ?? $reservation->chargingPoint?->steve_charging_point_id,
                'transaction_id' => $data['steve_transaction_id']
                    ?? $data['transaction_id']
                    ?? data_get($data, 'data.transaction.id'),
                'start_time' => $reservation->start_time,
                'processed_at' => now(),
                'processing_duration_ms' => isset($data['duration_ms']) ? (int) round($data['duration_ms']) : null,
                'validation_checks' => $data['validation_checks'] ?? null,
                'eligibility_data' => $data['eligibility_data'] ?? null,
                'ocpp_response' => $data['data'] ?? $data['ocpp_response'] ?? null,
                'ocpp_status_code' => $data['status_code'] ?? null,
                'ocpp_status' => data_get($data, 'data.status') ?? $data['ocpp_status'] ?? null,
                'error_message' => $data['error'] ?? (($status !== 'success') ? ($data['message'] ?? null) : null),
                'error_trace' => $data['trace'] ?? null,
                'error_type' => $status === 'error' ? 'technical' : ($status === 'failed' ? 'business' : null),
                'metadata' => ['result' => $data],
                'triggered_by' => $data['triggered_by'] ?? 'event',
                'processing_mode' => config('auto-remote-start.use_queue', true) ? 'queue' : 'sync',
                'server_hostname' => gethostname() ?: null,
                'requires_manual_action' => in_array($status, ['failed', 'error'], true),
            ]);

            if ($status === 'success') {
                $this->resetFailureCount($reservation);
            } elseif ($status === 'failed') {
                $this->incrementFailureCount($reservation);
            }

        } catch (Exception $e) {
            Log::error('AutoRemoteStart: Erreur lors du logging', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function appendReservationOcppLog(Reservation $reservation, string $status, array $payload = []): void
    {
        try {
            $reservation->refresh();

            $operationsLog = is_array($reservation->ocpp_operations_log)
                ? $reservation->ocpp_operations_log
                : [];

            $operationsLog[] = array_merge([
                'status' => $status,
                'timestamp' => now()->toIso8601String(),
            ], $payload);

            $reservation->update([
                'ocpp_operations_log' => $operationsLog,
            ]);
        } catch (Exception $e) {
            Log::warning('AutoRemoteStart: impossible de mettre à jour le journal OCPP de la réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtenir les statistiques des démarrages automatiques
     * 
     * @param array $filters
     * @return array
     */
    public function getAutoStartStats(array $filters = []): array
    {
        $query = DB::table('auto_remote_start_logs');

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['charging_point_id'])) {
            $query->where('charging_point_id', $filters['charging_point_id']);
        }

        $total = $query->count();
        $success = (clone $query)->where('status', 'success')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $errors = (clone $query)->where('status', 'error')->count();

        $successRate = $total > 0 ? round(($success / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
            'success_rate' => $successRate,
            'filters' => $filters
        ];
    }

    /**
     * Forcer le démarrage d'une réservation spécifique (bypass des vérifications temporelles)
     * 
     * @param int $reservationId
     * @return array
     */
    public function forceStartReservation(int $reservationId): array
    {
        $reservation = Reservation::with(['chargingPoint', 'connector', 'user', 'ocppTag'])
            ->findOrFail($reservationId);

        // Désactiver temporairement la vérification du temps
        $originalGracePeriod = $this->gracePeriodMinutes;
        $originalStartWindow = $this->startWindowMinutes;
        
        $this->gracePeriodMinutes = 1440; // 24 heures
        $this->startWindowMinutes = 1440; // 24 heures

        try {
            $result = $this->processReservation($reservation);
            
            // Restaurer les paramètres
            $this->gracePeriodMinutes = $originalGracePeriod;
            $this->startWindowMinutes = $originalStartWindow;
            
            return $result;

        } catch (Exception $e) {
            // Restaurer les paramètres même en cas d'erreur
            $this->gracePeriodMinutes = $originalGracePeriod;
            $this->startWindowMinutes = $originalStartWindow;
            
            throw $e;
        }
    }

    /**
     * Vérifier la santé du système de démarrage automatique
     * 
     * @return array
     */
    public function healthCheck(): array
    {
        $issues = [];
        $warnings = [];

        // 1. Vérifier que le service est activé
        if (!$this->autoStartEnabled) {
            $warnings[] = 'Le démarrage automatique est désactivé';
        }

        // 2. Vérifier les services dépendants
        try {
            $this->ocppService->getConfig();
        } catch (Exception $e) {
            $issues[] = 'Service OCPP indisponible: ' . $e->getMessage();
        }

        // 3. Vérifier la connectivité à Steve
        try {
            $tags = $this->ocppService->listOcppTags(['limit' => 1]);
            if (!$tags['success']) {
                $issues[] = 'Impossible de se connecter à l\'API Steve';
            }
        } catch (Exception $e) {
            $issues[] = 'Erreur de connexion Steve: ' . $e->getMessage();
        }

        // 4. Vérifier la table de logs
        try {
            DB::table('auto_remote_start_logs')->limit(1)->get();
        } catch (Exception $e) {
            $issues[] = 'Table auto_remote_start_logs inaccessible: ' . $e->getMessage();
        }

        // 5. Vérifier les réservations bloquées
        $blockedReservations = $this->getBlockedReservations();
        if (count($blockedReservations) > 0) {
            $warnings[] = count($blockedReservations) . ' réservation(s) bloquée(s) avec échecs répétés';
        }

        $isHealthy = count($issues) === 0;

        return [
            'healthy' => $isHealthy,
            'status' => $isHealthy ? 'OK' : 'DEGRADED',
            'issues' => $issues,
            'warnings' => $warnings,
            'config' => [
                'enabled' => $this->autoStartEnabled,
                'max_retry_attempts' => $this->maxRetryAttempts,
                'retry_delay_seconds' => $this->retryDelaySeconds,
                'start_window_minutes' => $this->startWindowMinutes,
                'grace_period_minutes' => $this->gracePeriodMinutes
            ],
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Obtenir les réservations bloquées (échecs multiples)
     * 
     * @return array
     */
    protected function getBlockedReservations(): array
    {
        $blocked = [];
        
        $recentReservations = Reservation::whereIn('status', ['confirmed', 'pending_confirmation'])
            ->where(function ($query) {
                $query->whereIn('payment_status', ['PAID', 'PAYE', 'paid', 'paye']);
            })
            ->where('start_time', '<=', now())
            ->where('start_time', '>=', now()->subHours(24))
            ->get();

        foreach ($recentReservations as $reservation) {
            $failures = $this->getRecentFailureCount($reservation);
            if ($failures >= $this->maxRetryAttempts) {
                $blocked[] = [
                    'reservation_id' => $reservation->id,
                    'failures' => $failures,
                    'user_id' => $reservation->user_id,
                    'charging_point_id' => $reservation->charging_point_id
                ];
            }
        }

        return $blocked;
    }
}

