<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Services\Reservations\ReservationChargingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Service unifié pour la gestion des sessions de recharge SteVe
 * 
 * Ce service consolide :
 * - SteVeChargingTriggerService (déclenchement de charge)
 * - SteVeAutoStopService (arrêt automatique)
 * - SteVeRetryService (gestion des retries)
 * 
 * Fonctionnalités :
 * - Déclenchement de sessions de charge après paiement
 * - Arrêt automatique basé sur les limites (temps/kWh)
 * - Gestion des retries avec backoff exponentiel
 * - Monitoring des sessions actives
 */
class SteVeSessionService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected int $maxRetries;
    protected int $retryDelay;
    protected array $retryableErrors;
    protected array $nonRetryableErrors;
    protected array $endpoints;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->maxRetries = config('steve.commands.retry_attempts', 3);
        $this->retryDelay = config('steve.commands.retry_delay', 5);
        
        $this->endpoints = [
            'charging_sessions' => '/api/v1/charging-sessions',
            'stop_session' => '/api/v1/charging-sessions/{id}/stop',
            'session_status' => '/api/v1/charging-sessions/{id}',
            'meter_values' => '/api/v1/charging-sessions/{id}/meter-values',
            'remote_start' => '/api/v1/ocpp/remote-start',
            'remote_stop' => '/api/v1/ocpp/remote-stop'
        ];
        
        $this->retryableErrors = [
            'Connection refused', 'Connection timeout', 'Server unavailable',
            'Network error', 'Temporary failure', 'Service temporarily unavailable',
            'Gateway timeout', 'Request timeout'
        ];
        
        $this->nonRetryableErrors = [
            'Authentication failed', 'Invalid credentials', 'Access denied',
            'Forbidden', 'Not found', 'Invalid request', 'Bad request', 'Unauthorized'
        ];
    }

    // ========================================================================
    // CHARGING SESSION TRIGGER
    // ========================================================================

    /**
     * Déclencher une session de charge après paiement.
     *
     * Slice B.2: this used to ad-hoc POST `/api/v1/ocpp/remote-start` and write
     * reservation columns that don't exist (`charging_started_at`,
     * `steve_session_id`). It now delegates to ReservationChargingService which
     * owns the canonical OCPP dispatch + the real reservation state machine
     * (`actual_start_time`, `status = ACTIVE`, `charging_session_id`).
     */
    public function triggerChargingAfterPayment(Reservation $reservation, array $chargingData = []): array
    {
        return app(ReservationChargingService::class)->start($reservation, [
            'connector_id'    => $chargingData['connector_id']    ?? null,
            'ocpp_tag'        => $chargingData['id_tag']          ?? $chargingData['ocpp_tag'] ?? null,
            'idempotency_key' => $chargingData['idempotency_key'] ?? null,
        ]);
    }

    /**
     * Arrêter une session de charge (delegates to ReservationChargingService).
     */
    public function stopCharging(Reservation $reservation, string $reason = 'manual'): array
    {
        return app(ReservationChargingService::class)->stop($reservation, $reason);
    }

    /**
     * Vérifier le statut d'une session de charge
     */
    public function checkChargingStatus(Reservation $reservation): array
    {
        try {
            $sessionId = $reservation->steve_session_id;
            
            if (!$sessionId) {
                return [
                    'success' => false,
                    'error' => 'Aucune session SteVe associée'
                ];
            }

            $endpoint = str_replace('{id}', $sessionId, $this->endpoints['session_status']);
            $response = $this->makeRequest('GET', $endpoint);

            if ($response['success']) {
                return [
                    'success' => true,
                    'status' => $response['data']['status'] ?? 'unknown',
                    'energy_consumed' => $response['data']['energy_consumed'] ?? 0,
                    'duration' => $response['data']['duration'] ?? 0,
                    'data' => $response['data']
                ];
            }

            return $response;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // AUTO-STOP FUNCTIONALITY
    // ========================================================================

    /**
     * Vérifier et arrêter les sessions expirées
     */
    public function checkAndStopExpiredSessions(): array
    {
        try {
            Log::info('SteVeSessionService: Vérification des sessions expirées');

            $activeSessions = $this->getActiveChargingSessions();
            
            if (empty($activeSessions)) {
                return [
                    'success' => true,
                    'message' => 'Aucune session active',
                    'sessions_checked' => 0,
                    'sessions_stopped' => 0
                ];
            }

            $sessionsChecked = 0;
            $sessionsStopped = 0;
            $results = [];

            foreach ($activeSessions as $session) {
                $sessionsChecked++;
                $shouldStop = $this->shouldStopSession($session);
                
                if ($shouldStop['should_stop']) {
                    $stopResult = $this->stopSessionById($session['id'], $shouldStop['reason']);
                    
                    if ($stopResult['success']) {
                        $sessionsStopped++;
                    }
                    
                    $results[] = [
                        'session_id' => $session['id'],
                        'action' => $stopResult['success'] ? 'stopped' : 'stop_failed',
                        'reason' => $shouldStop['reason'],
                        'result' => $stopResult
                    ];
                } else {
                    $results[] = [
                        'session_id' => $session['id'],
                        'action' => 'continue',
                        'remaining' => $shouldStop['remaining'] ?? null
                    ];
                }
            }

            Log::info('SteVeSessionService: Vérification terminée', [
                'sessions_checked' => $sessionsChecked,
                'sessions_stopped' => $sessionsStopped
            ]);

            return [
                'success' => true,
                'sessions_checked' => $sessionsChecked,
                'sessions_stopped' => $sessionsStopped,
                'results' => $results
            ];

        } catch (Exception $e) {
            Log::error('SteVeSessionService: Erreur vérification sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer les sessions de charge actives
     */
    public function getActiveChargingSessions(): array
    {
        try {
            $response = $this->makeRequest('GET', $this->endpoints['charging_sessions']);

            if ($response['success'] && is_array($response['data'])) {
                return array_filter($response['data'], function($session) {
                    return isset($session['status']) && 
                           in_array($session['status'], ['active', 'charging', 'in_progress']);
                });
            }

            return [];
        } catch (Exception $e) {
            Log::error('SteVeSessionService: Erreur récupération sessions', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Déterminer si une session doit être arrêtée
     */
    protected function shouldStopSession(array $session): array
    {
        $reservationId = $session['reservation_id'] ?? null;
        
        if (!$reservationId) {
            return ['should_stop' => false, 'reason' => 'no_reservation'];
        }

        $reservation = Reservation::find($reservationId);
        
        if (!$reservation) {
            return ['should_stop' => false, 'reason' => 'reservation_not_found'];
        }

        // Vérifier la limite de temps
        if ($reservation->max_charging_minutes) {
            $startTime = Carbon::parse($session['start_time'] ?? $reservation->charging_started_at);
            $elapsedMinutes = $startTime->diffInMinutes(now());
            
            if ($elapsedMinutes >= $reservation->max_charging_minutes) {
                return [
                    'should_stop' => true,
                    'reason' => 'time_limit_reached',
                    'elapsed' => $elapsedMinutes,
                    'limit' => $reservation->max_charging_minutes
                ];
            }
            
            $remaining = $reservation->max_charging_minutes - $elapsedMinutes;
        }

        // Vérifier la limite d'énergie
        if ($reservation->max_kwh) {
            $energyConsumed = $session['energy_consumed'] ?? 0;
            
            if ($energyConsumed >= $reservation->max_kwh) {
                return [
                    'should_stop' => true,
                    'reason' => 'energy_limit_reached',
                    'consumed' => $energyConsumed,
                    'limit' => $reservation->max_kwh
                ];
            }
            
            $remaining = $reservation->max_kwh - $energyConsumed;
        }

        // Vérifier si le paiement prépayé est épuisé
        if ($reservation->prepaid_amount && $reservation->cost_so_far >= $reservation->prepaid_amount) {
            return [
                'should_stop' => true,
                'reason' => 'prepaid_exhausted',
                'spent' => $reservation->cost_so_far,
                'prepaid' => $reservation->prepaid_amount
            ];
        }

        return [
            'should_stop' => false,
            'remaining' => $remaining ?? null
        ];
    }

    /**
     * Arrêter une session par ID
     */
    protected function stopSessionById(string $sessionId, string $reason): array
    {
        try {
            $endpoint = str_replace('{id}', $sessionId, $this->endpoints['stop_session']);
            $response = $this->makeRequest('POST', $endpoint, ['reason' => $reason]);

            if ($response['success']) {
                Log::info('SteVeSessionService: Session arrêtée', [
                    'session_id' => $sessionId,
                    'reason' => $reason
                ]);
            }

            return $response;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // RETRY MECHANISM
    // ========================================================================

    /**
     * Exécuter une opération avec retry automatique
     */
    public function executeWithRetry(callable $operation, array $context = []): array
    {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                Log::debug('SteVeSessionService: Tentative', [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'context' => $context
                ]);

                $result = $operation();
                
                if ($result['success']) {
                    return $result;
                }
                
                $lastError = $result['error'] ?? 'Erreur inconnue';
                
                if ($this->isNonRetryableError($lastError)) {
                    Log::warning('SteVeSessionService: Erreur non-retryable', [
                        'error' => $lastError,
                        'attempt' => $attempt
                    ]);
                    return $result;
                }
                
                if ($attempt >= $this->maxRetries) {
                    break;
                }
                
                $delay = $this->calculateRetryDelay($attempt);
                Log::debug('SteVeSessionService: Attente avant retry', [
                    'delay' => $delay,
                    'attempt' => $attempt
                ]);
                
                sleep($delay);
                
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                
                Log::error('SteVeSessionService: Exception', [
                    'attempt' => $attempt,
                    'error' => $lastError
                ]);
                
                if ($this->isNonRetryableError($lastError) || $attempt >= $this->maxRetries) {
                    return [
                        'success' => false,
                        'error' => $lastError,
                        'attempts' => $attempt
                    ];
                }
                
                sleep($this->calculateRetryDelay($attempt));
            }
        }
        
        return [
            'success' => false,
            'error' => "Échec après {$this->maxRetries} tentatives: {$lastError}",
            'attempts' => $attempt,
            'context' => $context
        ];
    }

    /**
     * Vérifier si une erreur est non-retryable
     */
    protected function isNonRetryableError(string $error): bool
    {
        foreach ($this->nonRetryableErrors as $pattern) {
            if (stripos($error, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculer le délai de retry avec backoff exponentiel
     */
    protected function calculateRetryDelay(int $attempt): int
    {
        $baseDelay = $this->retryDelay * pow(2, $attempt - 1);
        $jitter = rand(0, 1000) / 1000;
        return (int) ($baseDelay + $jitter);
    }

    // ========================================================================
    // HTTP CLIENT
    // ========================================================================

    /**
     * Effectuer une requête HTTP
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            $httpClient = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]);

            $response = match (strtoupper($method)) {
                'GET' => $httpClient->get($url),
                'POST' => $httpClient->post($url, $data),
                'PUT' => $httpClient->put($url, $data),
                'DELETE' => $httpClient->delete($url),
                default => throw new Exception("Méthode HTTP non supportée: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json() ?? []
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Vérifier la connectivité avec retry
     */
    public function checkConnectivityWithRetry(): array
    {
        return $this->executeWithRetry(function() {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . '/api/v1/health');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => 'connected'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'SteVe non accessible (HTTP ' . $response->status() . ')'
            ];
        }, ['operation' => 'connectivity_check']);
    }

    /**
     * Obtenir les statistiques de configuration
     */
    public function getRetryStats(): array
    {
        return [
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'timeout' => $this->timeout,
            'retryable_errors' => $this->retryableErrors,
            'non_retryable_errors' => $this->nonRetryableErrors
        ];
    }

    /**
     * Mettre en cache le statut de connectivité
     */
    public function cacheConnectivityStatus(array $status): void
    {
        Cache::put('steve_session_connectivity', $status, 60);
    }

    /**
     * Récupérer le statut de connectivité depuis le cache
     */
    public function getCachedConnectivityStatus(): ?array
    {
        return Cache::get('steve_session_connectivity');
    }
}
