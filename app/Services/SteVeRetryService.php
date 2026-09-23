<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de retry et gestion d'erreurs pour SETEVE
 * 
 * @deprecated Use SteVeSessionService instead. This class will be removed in a future version.
 * 
 * Ce service gère les tentatives de reconnexion, les retry automatiques
 * et la gestion des erreurs pour l'intégration SETEVE
 */
class SteVeRetryService
{
    protected int $maxRetries;
    protected int $retryDelay;
    protected int $timeout;
    protected array $retryableErrors;
    protected array $nonRetryableErrors;

    public function __construct()
    {
        $this->maxRetries = config('steve.commands.retry_attempts', 3);
        $this->retryDelay = config('steve.commands.retry_delay', 5);
        $this->timeout = config('steve.timeout', 30);
        
        $this->retryableErrors = [
            'Connection refused',
            'Connection timeout',
            'Server unavailable',
            'Network error',
            'Temporary failure',
            'Service temporarily unavailable',
            'Gateway timeout',
            'Request timeout'
        ];
        
        $this->nonRetryableErrors = [
            'Authentication failed',
            'Invalid credentials',
            'Access denied',
            'Forbidden',
            'Not found',
            'Invalid request',
            'Bad request',
            'Unauthorized'
        ];
    }

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
                Log::info('SteVeRetryService: Tentative d\'exécution', [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'context' => $context
                ]);

                $result = $operation();
                
                if ($result['success']) {
                    Log::info('SteVeRetryService: Opération réussie', [
                        'attempt' => $attempt,
                        'context' => $context
                    ]);
                    
                    return $result;
                } else {
                    $lastError = $result['error'] ?? 'Erreur inconnue';
                    
                    // Vérifier si l'erreur est non-retryable
                    if ($this->isNonRetryableError($lastError)) {
                        Log::warning('SteVeRetryService: Erreur non-retryable détectée', [
                            'error' => $lastError,
                            'attempt' => $attempt,
                            'context' => $context
                        ]);
                        
                        return $result;
                    }
                    
                    // Vérifier si on peut encore retry
                    if ($attempt >= $this->maxRetries) {
                        Log::error('SteVeRetryService: Nombre maximum de tentatives atteint', [
                            'attempt' => $attempt,
                            'max_retries' => $this->maxRetries,
                            'last_error' => $lastError,
                            'context' => $context
                        ]);
                        
                        return [
                            'success' => false,
                            'error' => "Échec après {$this->maxRetries} tentatives: {$lastError}",
                            'attempts' => $attempt,
                            'context' => $context
                        ];
                    }
                    
                    // Attendre avant la prochaine tentative
                    $delay = $this->calculateRetryDelay($attempt);
                    Log::info('SteVeRetryService: Attente avant retry', [
                        'delay' => $delay,
                        'attempt' => $attempt,
                        'next_attempt' => $attempt + 1,
                        'context' => $context
                    ]);
                    
                    sleep($delay);
                }
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                
                Log::error('SteVeRetryService: Exception lors de l\'exécution', [
                    'attempt' => $attempt,
                    'error' => $lastError,
                    'context' => $context,
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Vérifier si l'erreur est non-retryable
                if ($this->isNonRetryableError($lastError)) {
                    return [
                        'success' => false,
                        'error' => $lastError,
                        'attempts' => $attempt,
                        'context' => $context
                    ];
                }
                
                // Vérifier si on peut encore retry
                if ($attempt >= $this->maxRetries) {
                    return [
                        'success' => false,
                        'error' => "Exception après {$this->maxRetries} tentatives: {$lastError}",
                        'attempts' => $attempt,
                        'context' => $context
                    ];
                }
                
                // Attendre avant la prochaine tentative
                $delay = $this->calculateRetryDelay($attempt);
                sleep($delay);
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
     * Vérifier si une erreur est retryable
     */
    protected function isRetryableError(string $error): bool
    {
        foreach ($this->retryableErrors as $retryableError) {
            if (stripos($error, $retryableError) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Vérifier si une erreur est non-retryable
     */
    protected function isNonRetryableError(string $error): bool
    {
        foreach ($this->nonRetryableErrors as $nonRetryableError) {
            if (stripos($error, $nonRetryableError) !== false) {
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
        // Backoff exponentiel avec jitter
        $baseDelay = $this->retryDelay * pow(2, $attempt - 1);
        $jitter = rand(0, 1000) / 1000; // Jitter entre 0 et 1 seconde
        
        return (int) ($baseDelay + $jitter);
    }

    /**
     * Vérifier la connectivité SETEVE avec retry
     */
    public function checkConnectivityWithRetry(): array
    {
        return $this->executeWithRetry(function() {
            $baseUrl = config('steve.api_url', '');
            $healthUrl = $baseUrl . '/api/v1/health';
            
            $response = \Illuminate\Support\Facades\Http::timeout($this->timeout)
                ->get($healthUrl);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => 'connected',
                    'response_time' => $response->transferStats?->getHandlerStat('total_time') ?? 0
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'SETEVE non accessible (HTTP ' . $response->status() . ')',
                    'status_code' => $response->status()
                ];
            }
        }, ['operation' => 'connectivity_check']);
    }

    /**
     * Déclencher la recharge avec retry
     */
    public function triggerChargingWithRetry(Reservation $reservation, array $chargingData): array
    {
        return $this->executeWithRetry(function() use ($reservation, $chargingData) {
            $chargingTriggerService = app(SteVeChargingTriggerService::class);
            return $chargingTriggerService->triggerChargingAfterPayment($reservation, $chargingData);
        }, [
            'operation' => 'trigger_charging',
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id
        ]);
    }

    /**
     * Vérifier le statut de recharge avec retry
     */
    public function checkChargingStatusWithRetry(Reservation $reservation): array
    {
        return $this->executeWithRetry(function() use ($reservation) {
            $chargingTriggerService = app(SteVeChargingTriggerService::class);
            return $chargingTriggerService->checkChargingStatus($reservation);
        }, [
            'operation' => 'check_charging_status',
            'reservation_id' => $reservation->id
        ]);
    }

    /**
     * Arrêter la recharge avec retry
     */
    public function stopChargingWithRetry(Reservation $reservation): array
    {
        return $this->executeWithRetry(function() use ($reservation) {
            $chargingTriggerService = app(SteVeChargingTriggerService::class);
            return $chargingTriggerService->stopCharging($reservation);
        }, [
            'operation' => 'stop_charging',
            'reservation_id' => $reservation->id
        ]);
    }

    /**
     * Gérer les erreurs de façon intelligente
     */
    public function handleError(array $error, array $context = []): array
    {
        $errorMessage = $error['error'] ?? 'Erreur inconnue';
        $errorCode = $error['code'] ?? 'unknown';
        
        // Log de l'erreur
        Log::error('SteVeRetryService: Gestion d\'erreur', [
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'context' => $context
        ]);
        
        // Déterminer le type d'erreur et la stratégie de récupération
        if ($this->isRetryableError($errorMessage)) {
            return [
                'should_retry' => true,
                'retry_delay' => $this->calculateRetryDelay(1),
                'error_type' => 'retryable',
                'suggestion' => 'Tentative de reconnexion automatique'
            ];
        } elseif ($this->isNonRetryableError($errorMessage)) {
            return [
                'should_retry' => false,
                'error_type' => 'non_retryable',
                'suggestion' => 'Vérifier la configuration et les permissions'
            ];
        } else {
            return [
                'should_retry' => true,
                'retry_delay' => $this->calculateRetryDelay(1),
                'error_type' => 'unknown',
                'suggestion' => 'Tentative de retry avec délai'
            ];
        }
    }

    /**
     * Mettre en cache le statut de connectivité
     */
    public function cacheConnectivityStatus(array $status): void
    {
        $cacheKey = 'steve_connectivity_status';
        $cacheDuration = config('steve.monitoring.cache_duration', 60);
        
        Cache::put($cacheKey, $status, $cacheDuration);
    }

    /**
     * Récupérer le statut de connectivité depuis le cache
     */
    public function getCachedConnectivityStatus(): ?array
    {
        $cacheKey = 'steve_connectivity_status';
        return Cache::get($cacheKey);
    }

    /**
     * Nettoyer le cache de connectivité
     */
    public function clearConnectivityCache(): void
    {
        $cacheKey = 'steve_connectivity_status';
        Cache::forget($cacheKey);
    }

    /**
     * Obtenir les statistiques de retry
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
     * Mettre à jour la configuration de retry
     */
    public function updateRetryConfig(array $config): void
    {
        if (isset($config['max_retries'])) {
            $this->maxRetries = $config['max_retries'];
        }
        
        if (isset($config['retry_delay'])) {
            $this->retryDelay = $config['retry_delay'];
        }
        
        if (isset($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
        
        Log::info('SteVeRetryService: Configuration de retry mise à jour', [
            'max_retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'timeout' => $this->timeout
        ]);
    }
}
