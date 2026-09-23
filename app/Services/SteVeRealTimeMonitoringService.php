<?php

namespace App\Services;

use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

/**
 * Service de monitoring en temps réel pour SteVe
 * 
 * Ce service gère la surveillance continue des connexions,
 * les notifications en temps réel et le monitoring des performances
 */
class SteVeRealTimeMonitoringService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected array $monitoringConfig;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        
        $this->monitoringConfig = [
            'refresh_interval' => config('steve.monitoring.refresh_interval', 30), // secondes
            'alert_thresholds' => [
                'response_time' => config('steve.monitoring.response_time_threshold', 5000), // ms
                'offline_points' => config('steve.monitoring.offline_points_threshold', 3),
                'error_rate' => config('steve.monitoring.error_rate_threshold', 0.1) // 10%
            ],
            'cache_duration' => config('steve.monitoring.cache_duration', 60) // secondes
        ];
    }

    /**
     * Démarrer le monitoring en temps réel
     */
    public function startRealTimeMonitoring(): array
    {
        try {
            $monitoringData = $this->collectMonitoringData();
            $this->storeMonitoringData($monitoringData);
            $this->checkAlerts($monitoringData);
            
            return [
                'success' => true,
                'data' => $monitoringData,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage du monitoring temps réel', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors du démarrage du monitoring: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Collecter les données de monitoring
     */
    protected function collectMonitoringData(): array
    {
        $startTime = microtime(true);
        
        $data = [
            'server_status' => $this->getServerStatus(),
            'charging_points' => $this->getChargingPointsStatus(),
            'ocpp_connections' => $this->getOcppConnectionsStatus(),
            'system_metrics' => $this->getSystemMetrics(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'alerts' => $this->getActiveAlerts()
        ];

        $endTime = microtime(true);
        $data['collection_time_ms'] = round(($endTime - $startTime) * 1000, 2);

        return $data;
    }

    /**
     * Statut du serveur SteVe
     */
    protected function getServerStatus(): array
    {
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . '/api/v1/health');

            $responseTime = $response->transferStats?->getHandlerStat('total_time') * 1000 ?? 0;
            
            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'response_time_ms' => round($responseTime, 2),
                'response_code' => $response->status(),
                'last_check' => now()->toISOString(),
                'uptime' => $this->getServerUptime()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'error' => $e->getMessage(),
                'last_check' => now()->toISOString()
            ];
        }
    }

    /**
     * Statut des points de charge
     */
    protected function getChargingPointsStatus(): array
    {
        try {
            $chargingPoints = ChargingPoint::with(['businessProfile', 'integrator'])
                ->select([
                    'id', 'name', 'charge_box_id', 'status', 
                    'last_connected_at', 'last_disconnected_at',
                    'business_profile_id', 'integrator_id'
                ])
                ->get();

            $statusCounts = $chargingPoints->groupBy('status')->map->count();
            $onlinePoints = $chargingPoints->where('status', 'online');
            $offlinePoints = $chargingPoints->where('status', 'offline');

            return [
                'total' => $chargingPoints->count(),
                'online' => $onlinePoints->count(),
                'offline' => $offlinePoints->count(),
                'unknown' => $statusCounts->get('unknown', 0),
                'status_distribution' => $statusCounts->toArray(),
                'points' => $chargingPoints->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'name' => $point->name,
                        'charge_box_id' => $point->charge_box_id,
                        'status' => $point->status,
                        'is_online' => $point->status === 'online',
                        'last_connected' => $point->last_connected_at?->format('Y-m-d H:i:s'),
                        'last_disconnected' => $point->last_disconnected_at?->format('Y-m-d H:i:s'),
                        'business_profile' => $point->businessProfile?->name ?? 'N/A',
                        'integrator' => $point->integrator?->name ?? 'N/A',
                        'uptime' => $this->calculatePointUptime($point)
                    ];
                }),
                'recent_connections' => $this->getRecentConnections($chargingPoints),
                'connection_trends' => $this->getConnectionTrends()
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut des points de charge', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => $e->getMessage(),
                'total' => 0,
                'online' => 0,
                'offline' => 0
            ];
        }
    }

    /**
     * Statut des connexions OCPP
     */
    protected function getOcppConnectionsStatus(): array
    {
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . '/api/v1/ocpp/status');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'active',
                    'active_connections' => $data['active_connections'] ?? 0,
                    'total_charge_boxes' => $data['total_charge_boxes'] ?? 0,
                    'protocol_version' => $data['protocol_version'] ?? 'OCPP 1.6',
                    'last_heartbeat' => $data['last_heartbeat'] ?? now()->toISOString(),
                    'connection_quality' => $this->assessConnectionQuality($data)
                ];
            } else {
                return [
                    'status' => 'inactive',
                    'error' => 'OCPP service non disponible',
                    'response_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Métriques système
     */
    protected function getSystemMetrics(): array
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => sys_getloadavg(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_connections' => $this->getDatabaseConnections()
        ];
    }

    /**
     * Métriques de performance
     */
    protected function getPerformanceMetrics(): array
    {
        $cacheKey = 'steve_performance_metrics';
        $cached = Cache::get($cacheKey, []);
        
        $currentMetrics = [
            'response_times' => $this->getResponseTimes(),
            'error_rates' => $this->getErrorRates(),
            'throughput' => $this->getThroughput(),
            'availability' => $this->getAvailability()
        ];

        // Mettre à jour les métriques historiques
        $historicalMetrics = $cached['historical'] ?? [];
        $historicalMetrics[] = [
            'timestamp' => now()->toISOString(),
            'metrics' => $currentMetrics
        ];

        // Garder seulement les 100 dernières entrées
        if (count($historicalMetrics) > 100) {
            $historicalMetrics = array_slice($historicalMetrics, -100);
        }

        $updatedMetrics = [
            'current' => $currentMetrics,
            'historical' => $historicalMetrics,
            'trends' => $this->calculateTrends($historicalMetrics)
        ];

        Cache::put($cacheKey, $updatedMetrics, $this->monitoringConfig['cache_duration']);

        return $updatedMetrics;
    }

    /**
     * Alertes actives
     */
    protected function getActiveAlerts(): array
    {
        $alerts = [];
        
        // Vérifier les seuils de performance
        $performanceData = $this->getPerformanceMetrics();
        
        if (isset($performanceData['current']['response_times']['average'])) {
            $avgResponseTime = $performanceData['current']['response_times']['average'];
            if ($avgResponseTime > $this->monitoringConfig['alert_thresholds']['response_time']) {
                $alerts[] = [
                    'type' => 'performance',
                    'severity' => 'warning',
                    'message' => "Temps de réponse élevé: {$avgResponseTime}ms",
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        // Vérifier le nombre de points hors ligne
        $chargingPoints = $this->getChargingPointsStatus();
        $offlineCount = $chargingPoints['offline'] ?? 0;
        if ($offlineCount > $this->monitoringConfig['alert_thresholds']['offline_points']) {
            $alerts[] = [
                'type' => 'connectivity',
                'severity' => 'critical',
                'message' => "Trop de points hors ligne: {$offlineCount}",
                'timestamp' => now()->toISOString()
            ];
        }

        return $alerts;
    }

    /**
     * Stocker les données de monitoring
     */
    protected function storeMonitoringData(array $data): void
    {
        $cacheKey = 'steve_monitoring_data';
        Cache::put($cacheKey, $data, $this->monitoringConfig['cache_duration']);
        
        // Stocker dans les logs pour analyse
        Log::info('Données de monitoring SteVe collectées', [
            'server_status' => $data['server_status']['status'] ?? 'unknown',
            'charging_points_online' => $data['charging_points']['online'] ?? 0,
            'ocpp_connections' => $data['ocpp_connections']['active_connections'] ?? 0,
            'alerts_count' => count($data['alerts'] ?? [])
        ]);
    }

    /**
     * Vérifier les alertes
     */
    protected function checkAlerts(array $data): void
    {
        $alerts = $data['alerts'] ?? [];
        
        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'critical') {
                // Envoyer notification critique
                $this->sendCriticalAlert($alert);
            } elseif ($alert['severity'] === 'warning') {
                // Envoyer notification d'avertissement
                $this->sendWarningAlert($alert);
            }
        }
    }

    /**
     * Envoyer une alerte critique
     */
    protected function sendCriticalAlert(array $alert): void
    {
        Log::critical('Alerte critique SteVe', $alert);
        
        // Ici vous pouvez ajouter l'envoi d'emails, SMS, etc.
        Event::dispatch('steve.critical.alert', $alert);
    }

    /**
     * Envoyer une alerte d'avertissement
     */
    protected function sendWarningAlert(array $alert): void
    {
        Log::warning('Alerte d\'avertissement SteVe', $alert);
        
        // Ici vous pouvez ajouter l'envoi d'emails, etc.
        Event::dispatch('steve.warning.alert', $alert);
    }

    /**
     * Obtenir le temps de fonctionnement du serveur
     */
    protected function getServerUptime(): string
    {
        try {
            $uptime = shell_exec('uptime');
            return trim($uptime) ?: 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Calculer le temps de fonctionnement d'un point
     */
    protected function calculatePointUptime(ChargingPoint $point): string
    {
        if ($point->status === 'online' && $point->last_connected_at) {
            return $point->last_connected_at->diffForHumans();
        }
        return 'N/A';
    }

    /**
     * Obtenir les connexions récentes
     */
    protected function getRecentConnections($chargingPoints): array
    {
        return $chargingPoints
            ->where('last_connected_at', '>=', now()->subHours(24))
            ->sortByDesc('last_connected_at')
            ->take(10)
            ->map(function ($point) {
                return [
                    'name' => $point->name,
                    'connected_at' => $point->last_connected_at?->format('Y-m-d H:i:s'),
                    'status' => $point->status
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Obtenir les tendances de connexion
     */
    protected function getConnectionTrends(): array
    {
        $cacheKey = 'steve_connection_trends';
        $cached = Cache::get($cacheKey, []);
        
        $currentHour = now()->format('Y-m-d H');
        $trends = $cached[$currentHour] ?? [
            'hour' => $currentHour,
            'connections' => 0,
            'disconnections' => 0,
            'online_points' => 0
        ];
        
        $trends['online_points'] = ChargingPoint::where('status', 'online')->count();
        
        Cache::put($cacheKey, $trends, 3600); // Cache pour 1 heure
        
        return $trends;
    }

    /**
     * Évaluer la qualité de connexion
     */
    protected function assessConnectionQuality(array $data): string
    {
        $activeConnections = $data['active_connections'] ?? 0;
        $totalBoxes = $data['total_charge_boxes'] ?? 1;
        
        $connectionRate = $activeConnections / $totalBoxes;
        
        if ($connectionRate >= 0.9) {
            return 'excellent';
        } elseif ($connectionRate >= 0.7) {
            return 'good';
        } elseif ($connectionRate >= 0.5) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Obtenir l'utilisation CPU
     */
    protected function getCpuUsage(): array
    {
        try {
            $load = sys_getloadavg();
            return [
                'load_1min' => $load[0] ?? 0,
                'load_5min' => $load[1] ?? 0,
                'load_15min' => $load[2] ?? 0
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtenir l'utilisation disque
     */
    protected function getDiskUsage(): array
    {
        try {
            $total = disk_total_space('/');
            $free = disk_free_space('/');
            $used = $total - $free;
            
            return [
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'percentage' => round(($used / $total) * 100, 2)
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtenir les connexions base de données
     */
    protected function getDatabaseConnections(): array
    {
        try {
            $connections = \DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return [
                'active_connections' => $connections[0]->Value ?? 0,
                'max_connections' => \DB::select("SHOW VARIABLES LIKE 'max_connections'")[0]->Value ?? 0
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtenir les temps de réponse
     */
    protected function getResponseTimes(): array
    {
        $cacheKey = 'steve_response_times';
        $cached = Cache::get($cacheKey, []);
        
        $currentTime = now()->toISOString();
        $cached[] = [
            'timestamp' => $currentTime,
            'response_time' => $this->getServerStatus()['response_time_ms'] ?? 0
        ];
        
        // Garder seulement les 50 dernières entrées
        if (count($cached) > 50) {
            $cached = array_slice($cached, -50);
        }
        
        Cache::put($cacheKey, $cached, 300); // Cache pour 5 minutes
        
        $responseTimes = array_column($cached, 'response_time');
        
        return [
            'current' => end($responseTimes) ?: 0,
            'average' => array_sum($responseTimes) / count($responseTimes),
            'min' => min($responseTimes) ?: 0,
            'max' => max($responseTimes) ?: 0
        ];
    }

    /**
     * Obtenir les taux d'erreur
     */
    protected function getErrorRates(): array
    {
        $cacheKey = 'steve_error_rates';
        $cached = Cache::get($cacheKey, ['errors' => 0, 'total' => 0]);
        
        return [
            'current_rate' => $cached['total'] > 0 ? ($cached['errors'] / $cached['total']) : 0,
            'total_errors' => $cached['errors'],
            'total_requests' => $cached['total']
        ];
    }

    /**
     * Obtenir le débit
     */
    protected function getThroughput(): array
    {
        $cacheKey = 'steve_throughput';
        $cached = Cache::get($cacheKey, []);
        
        $currentMinute = now()->format('Y-m-d H:i');
        $cached[$currentMinute] = ($cached[$currentMinute] ?? 0) + 1;
        
        // Nettoyer les anciennes entrées (garder seulement la dernière heure)
        $oneHourAgo = now()->subHour()->format('Y-m-d H:i');
        $cached = array_filter($cached, function($key) use ($oneHourAgo) {
            return $key >= $oneHourAgo;
        }, ARRAY_FILTER_USE_KEY);
        
        Cache::put($cacheKey, $cached, 3600);
        
        return [
            'requests_per_minute' => array_sum($cached),
            'current_minute' => $cached[$currentMinute] ?? 0
        ];
    }

    /**
     * Obtenir la disponibilité
     */
    protected function getAvailability(): array
    {
        $cacheKey = 'steve_availability';
        $cached = Cache::get($cacheKey, ['uptime' => 0, 'downtime' => 0]);
        
        $serverStatus = $this->getServerStatus();
        $isOnline = $serverStatus['status'] === 'online';
        
        if ($isOnline) {
            $cached['uptime']++;
        } else {
            $cached['downtime']++;
        }
        
        Cache::put($cacheKey, $cached, 3600);
        
        $total = $cached['uptime'] + $cached['downtime'];
        $availability = $total > 0 ? ($cached['uptime'] / $total) * 100 : 100;
        
        return [
            'percentage' => round($availability, 2),
            'uptime_checks' => $cached['uptime'],
            'downtime_checks' => $cached['downtime']
        ];
    }

    /**
     * Calculer les tendances
     */
    protected function calculateTrends(array $historicalData): array
    {
        if (count($historicalData) < 2) {
            return ['insufficient_data' => true];
        }
        
        $recent = array_slice($historicalData, -5); // 5 dernières entrées
        $older = array_slice($historicalData, -10, 5); // 5 entrées précédentes
        
        $recentAvg = array_sum(array_column($recent, 'response_time')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'response_time')) / count($older);
        
        $trend = $recentAvg > $olderAvg ? 'increasing' : 'decreasing';
        
        return [
            'trend' => $trend,
            'change_percentage' => $olderAvg > 0 ? round((($recentAvg - $olderAvg) / $olderAvg) * 100, 2) : 0
        ];
    }
}
