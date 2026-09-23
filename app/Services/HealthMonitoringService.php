<?php

namespace App\Services;

use App\Models\StatusLog;
use App\Models\ChargingPoint;
use App\Services\SteVeApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class HealthMonitoringService
{
    protected $steveApiService;
    protected $cachePrefix = 'health_monitoring_';
    protected $cacheTtl = 300; // 5 minutes

    public function __construct(SteVeApiService $steveApiService)
    {
        $this->steveApiService = $steveApiService;
    }

    /**
     * Obtenir le statut de santé global
     */
    public function getOverallHealthStatus(): array
    {
        $cacheKey = $this->cachePrefix . 'overall_health';
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $apiStats = StatusLog::getEntityStats('api', null, 60);
            $chargerStats = StatusLog::getEntityStats('charger', null, 60);
            
            return [
                'apis' => $apiStats,
                'chargers' => $chargerStats,
                'overall_status' => $this->determineOverallStatus($apiStats, $chargerStats),
                'last_updated' => now()->toISOString()
            ];
        });
    }

    /**
     * Obtenir le statut des chargeurs
     */
    public function getChargerStatus(): array
    {
        $cacheKey = $this->cachePrefix . 'charger_status';
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $currentStatus = StatusLog::getCurrentEntityStatus('charger');
            $metrics = StatusLog::getPerformanceMetrics('charger', 60);
            
            return [
                'chargers' => $currentStatus,
                'metrics' => $metrics,
                'total_chargers' => $currentStatus->count(),
                'online_chargers' => $currentStatus->where('status', 'online')->count(),
                'offline_chargers' => $currentStatus->where('status', 'offline')->count(),
                'degraded_chargers' => $currentStatus->where('status', 'degraded')->count(),
                'uptime_percentage' => $this->calculateUptimePercentage($currentStatus)
            ];
        });
    }

    /**
     * Obtenir le statut des APIs
     */
    public function getApiStatus(): array
    {
        $cacheKey = $this->cachePrefix . 'api_status';
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $currentStatus = StatusLog::getCurrentEntityStatus('api');
            $metrics = StatusLog::getPerformanceMetrics('api', 60);
            
            return [
                'apis' => $currentStatus,
                'metrics' => $metrics,
                'total_apis' => $currentStatus->count(),
                'online_apis' => $currentStatus->where('status', 'online')->count(),
                'offline_apis' => $currentStatus->where('status', 'offline')->count(),
                'degraded_apis' => $currentStatus->where('status', 'degraded')->count(),
                'uptime_percentage' => $this->calculateUptimePercentage($currentStatus)
            ];
        });
    }

    /**
     * Obtenir les alertes de statut
     */
    public function getStatusAlerts(): array
    {
        $cacheKey = $this->cachePrefix . 'status_alerts';
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $alerts = StatusLog::getStatusAlerts(null, 60);
            
            // Grouper les alertes par type
            $groupedAlerts = [
                'critical' => collect($alerts)->where('type', 'critical')->values(),
                'warning' => collect($alerts)->where('type', 'warning')->values()
            ];
            
            return [
                'alerts' => $alerts,
                'grouped_alerts' => $groupedAlerts,
                'total_alerts' => count($alerts),
                'critical_count' => $groupedAlerts['critical']->count(),
                'warning_count' => $groupedAlerts['warning']->count()
            ];
        });
    }

    /**
     * Obtenir l'historique d'une entité
     */
    public function getEntityHistory(string $entityType, $entityId, int $hours = 24): array
    {
        $history = StatusLog::getEntityHistory($entityType, $entityId, $hours);
        
        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'history' => $history,
            'total_checks' => $history->count(),
            'uptime_percentage' => $this->calculateUptimePercentage($history),
            'average_response_time' => $history->where('response_time_ms', '>', 0)->avg('response_time_ms'),
            'last_check' => $history->first(),
            'time_range' => $hours
        ];
    }

    /**
     * Vérifier manuellement la santé d'un chargeur
     */
    public function checkChargerHealth(ChargingPoint $chargingPoint): array
    {
        $startTime = microtime(true);
        $chargerId = $chargingPoint->charge_box_id ?: "BORNE_{$chargingPoint->id}";
        
        try {
            Log::info('HealthMonitoringService: Manual charger health check', [
                'charger_id' => $chargerId,
                'charging_point_id' => $chargingPoint->id
            ]);

            $statusResult = $this->steveApiService->getChargerStatus($chargerId);
            
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            if ($statusResult['success']) {
                $isOnline = $this->determineChargerOnlineStatus($statusResult['status'] ?? []);
                
                $status = $isOnline ? 'online' : 'degraded';
                $healthStatus = $isOnline ? 'healthy' : 'warning';
                
                // Enregistrer le statut
                StatusLog::create([
                    'entity_type' => 'charger',
                    'entity_id' => $chargingPoint->id,
                    'entity_name' => $chargerId,
                    'status' => $status,
                    'health_status' => $healthStatus,
                    'response_time_ms' => $responseTime,
                    'status_code' => 200,
                    'metadata' => [
                        'charging_point_id' => $chargingPoint->id,
                        'manual_check' => true,
                        'status_data' => $statusResult['status']
                    ],
                    'checked_at' => now()
                ]);

                return [
                    'success' => true,
                    'status' => $status,
                    'health_status' => $healthStatus,
                    'response_time_ms' => $responseTime,
                    'charger_id' => $chargerId,
                    'status_data' => $statusResult['status'],
                    'message' => $isOnline ? 'Chargeur en ligne' : 'Chargeur dégradé'
                ];
            } else {
                StatusLog::markOffline(
                    'charger',
                    $chargingPoint->id,
                    $chargerId,
                    $statusResult['error'] ?? 'Unknown error',
                    [
                        'charging_point_id' => $chargingPoint->id,
                        'manual_check' => true
                    ]
                );

                return [
                    'success' => false,
                    'status' => 'offline',
                    'health_status' => 'critical',
                    'response_time_ms' => $responseTime,
                    'charger_id' => $chargerId,
                    'error' => $statusResult['error'],
                    'message' => 'Chargeur hors ligne'
                ];
            }

        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            StatusLog::markOffline(
                'charger',
                $chargingPoint->id,
                $chargerId,
                $e->getMessage(),
                [
                    'charging_point_id' => $chargingPoint->id,
                    'manual_check' => true,
                    'error_type' => get_class($e)
                ]
            );

            return [
                'success' => false,
                'status' => 'offline',
                'health_status' => 'critical',
                'response_time_ms' => $responseTime,
                'charger_id' => $chargerId,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la vérification du chargeur'
            ];
        }
    }

    /**
     * Vérifier manuellement la santé d'une API
     */
    public function checkApiHealth(string $apiName): array
    {
        $apis = [
            'evon' => [
                'name' => 'Evon API',
                'url' => config('app.url') . '/api/health',
                'timeout' => 10
            ],
            'steve' => [
                'name' => 'Steve API',
                'url' => config('steve.api_url', '') . '/api/v1/health',
                'timeout' => 15
            ]
        ];

        if (!isset($apis[$apiName])) {
            return [
                'success' => false,
                'error' => "API inconnue: {$apiName}"
            ];
        }

        $api = $apis[$apiName];
        $startTime = microtime(true);

        try {
            Log::info('HealthMonitoringService: Manual API health check', [
                'api' => $apiName,
                'url' => $api['url']
            ]);

            $response = Http::timeout($api['timeout'])->get($api['url']);
            
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            if ($response->successful()) {
                StatusLog::markOnline(
                    'api',
                    null,
                    $api['name'],
                    $responseTime,
                    [
                        'url' => $api['url'],
                        'status_code' => $response->status(),
                        'manual_check' => true
                    ]
                );

                return [
                    'success' => true,
                    'status' => 'online',
                    'health_status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'api_name' => $api['name'],
                    'status_code' => $response->status(),
                    'message' => 'API en ligne'
                ];
            } else {
                StatusLog::markDegraded(
                    'api',
                    null,
                    $api['name'],
                    $responseTime,
                    "HTTP {$response->status()}: {$response->body()}",
                    [
                        'url' => $api['url'],
                        'status_code' => $response->status(),
                        'manual_check' => true
                    ]
                );

                return [
                    'success' => false,
                    'status' => 'degraded',
                    'health_status' => 'warning',
                    'response_time_ms' => $responseTime,
                    'api_name' => $api['name'],
                    'status_code' => $response->status(),
                    'error' => $response->body(),
                    'message' => 'API dégradée'
                ];
            }

        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            StatusLog::markOffline(
                'api',
                null,
                $api['name'],
                $e->getMessage(),
                [
                    'url' => $api['url'],
                    'manual_check' => true,
                    'error_type' => get_class($e)
                ]
            );

            return [
                'success' => false,
                'status' => 'offline',
                'health_status' => 'critical',
                'response_time_ms' => $responseTime,
                'api_name' => $api['name'],
                'error' => $e->getMessage(),
                'message' => 'API hors ligne'
            ];
        }
    }

    /**
     * Obtenir les métriques de performance
     */
    public function getPerformanceMetrics(string $entityType = null, int $minutes = 60): array
    {
        $metrics = StatusLog::getPerformanceMetrics($entityType, $minutes);
        $alerts = StatusLog::getStatusAlerts($entityType, $minutes);
        
        return [
            'metrics' => $metrics,
            'alerts' => $alerts,
            'entity_type' => $entityType,
            'time_range' => $minutes,
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Nettoyer le cache
     */
    public function clearCache(): void
    {
        $patterns = [
            $this->cachePrefix . 'overall_health',
            $this->cachePrefix . 'charger_status',
            $this->cachePrefix . 'api_status',
            $this->cachePrefix . 'status_alerts'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('HealthMonitoringService: Cache cleared');
    }

    /**
     * Déterminer le statut global
     */
    protected function determineOverallStatus(array $apiStats, array $chargerStats): string
    {
        $apiUptime = $apiStats['uptime_percentage'] ?? 0;
        $chargerUptime = $chargerStats['uptime_percentage'] ?? 0;
        
        if ($apiUptime >= 99 && $chargerUptime >= 95) {
            return 'excellent';
        } elseif ($apiUptime >= 95 && $chargerUptime >= 90) {
            return 'good';
        } elseif ($apiUptime >= 90 && $chargerUptime >= 80) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Calculer le pourcentage de disponibilité
     */
    protected function calculateUptimePercentage($logs): float
    {
        if ($logs->count() === 0) {
            return 0;
        }

        $onlineCount = $logs->where('status', 'online')->count();
        return round(($onlineCount / $logs->count()) * 100, 2);
    }

    /**
     * Déterminer si un chargeur est en ligne
     */
    protected function determineChargerOnlineStatus($statusData): bool
    {
        if (empty($statusData)) {
            return false;
        }

        $statusString = is_array($statusData) ? json_encode($statusData) : (string) $statusData;
        $statusString = strtolower($statusString);

        $onlineStatuses = [
            'available',
            'connected',
            'online',
            'ready',
            'idle',
            'charging',
            'occupied'
        ];

        foreach ($onlineStatuses as $status) {
            if (strpos($statusString, $status) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtenir les statistiques détaillées
     */
    public function getDetailedStats(string $entityType = null, int $minutes = 60): array
    {
        $stats = StatusLog::getEntityStats($entityType, null, $minutes);
        $performance = StatusLog::getPerformanceMetrics($entityType, $minutes);
        $alerts = StatusLog::getStatusAlerts($entityType, $minutes);
        
        return [
            'entity_stats' => $stats,
            'performance_metrics' => $performance,
            'alerts' => $alerts,
            'entity_type' => $entityType,
            'time_range' => $minutes,
            'generated_at' => now()->toISOString()
        ];
    }
}
