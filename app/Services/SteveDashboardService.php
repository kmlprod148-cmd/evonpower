<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * Service de gestion du tableau de bord Steve API
 * 
 * Ce service agrège toutes les données nécessaires pour le dashboard
 * en utilisant les services Steve existants et en implémentant une stratégie de cache.
 * 
 * DONNÉES AUTHENTIQUES: Ce service récupère les données réelles depuis:
 * - Base de données locale (ChargingPoint, ChargingSession)
 * - Steve API via SteVeHttpClientService
 * - Steve API via SteVeHealthService
 */
class SteveDashboardService
{
    protected SteVeHttpClientService $httpClient;
    protected SteVeHealthService $healthService;
    protected SteVeSessionService $sessionService;
    protected SteVeMonitoringService $monitoringService;
    
    // Configuration du cache
    protected array $cacheConfig = [
        'summary' => 60,           // 1 minute
        'sessions' => 30,          // 30 secondes
        'active' => 10,            // 10 secondes (temps réel)
        'energy' => 300,           // 5 minutes
        'stations' => 60,          // 1 minute
        'health' => 30,            // 30 secondes
        'performance' => 60,       // 1 minute
    ];

    public function __construct(
        ?SteVeHttpClientService $httpClient = null,
        ?SteVeHealthService $healthService = null,
        ?SteVeSessionService $sessionService = null,
        ?SteVeMonitoringService $monitoringService = null
    ) {
        $this->httpClient = $httpClient ?? new SteVeHttpClientService();
        $this->healthService = $healthService ?? new SteVeHealthService();
        $this->sessionService = $sessionService ?? new SteVeSessionService();
        $this->monitoringService = $monitoringService ?? new SteVeMonitoringService();
    }

    /**
     * Obtenir le résumé complet du dashboard
     * Données authentiques depuis la base de données et Steve API
     */
    public function getDashboardSummary(): array
    {
        $cacheKey = 'steve:dashboard:summary';
        
        return Cache::remember($cacheKey, $this->cacheConfig['summary'], function () {
            return [
                'total_sessions' => $this->getTotalSessions(),
                'active_sessions' => $this->getActiveSessionsCount(),
                'total_energy_kwh' => $this->getTotalEnergyDistributed(),
                'total_stations' => $this->getTotalStations(),
                'operational_stations' => $this->getOperationalStationsCount(),
                'available_stations' => $this->getAvailableStationsCount(),
                'offline_stations' => $this->getOfflineStationsCount(),
                'faulted_stations' => $this->getFaultedStationsCount(),
                'charger_types' => $this->getChargerTypesBreakdown(),
                'network_health' => $this->getNetworkHealth(),
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Obtenir les sessions de recharge (historique)
     * Données authentiques depuis la base de données
     */
    public function getChargingSessions(int $limit = 50): array
    {
        $cacheKey = "steve:dashboard:sessions:{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheConfig['sessions'], function () use ($limit) {
            try {
                $sessions = ChargingSession::with(['chargingPoint', 'user'])
                    ->orderBy('started_at', 'desc')
                    ->limit($limit)
                    ->get();

                return [
                    'sessions' => $sessions->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'charging_point_id' => $session->charging_point_id,
                            'charging_point_name' => $session->chargingPoint?->name ?? 'N/A',
                            'user_id' => $session->user_id,
                            'status' => $session->status,
                            'started_at' => $session->started_at?->toISOString(),
                            'stopped_at' => $session->stopped_at?->toISOString(),
                            'actual_energy' => (float) $session->actual_energy,
                            'actual_cost' => (float) $session->actual_cost,
                            'actual_duration' => $session->actual_duration,
                            'stop_reason' => $session->stop_reason,
                        ];
                    })->toArray(),
                    'total_count' => ChargingSession::count(),
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching sessions', ['error' => $e->getMessage()]);
                return [
                    'sessions' => [],
                    'total_count' => 0,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir les sessions actives (en cours)
     * Données authentiques depuis la base de données + Steve API
     */
    public function getActiveSessions(): array
    {
        $cacheKey = 'steve:dashboard:active';
        
        return Cache::remember($cacheKey, $this->cacheConfig['active'], function () {
            try {
                $activeStatuses = ['active', 'in_progress', 'initiating'];
                
                $sessions = ChargingSession::with(['chargingPoint'])
                    ->whereIn('status', $activeStatuses)
                    ->orWhereNull('stopped_at')
                    ->orderBy('started_at', 'desc')
                    ->get();

                return [
                    'sessions' => $sessions->map(function ($session) {
                        $startTime = $session->started_at;
                        $duration = $startTime ? $startTime->diffInSeconds(now()) : 0;
                        
                        return [
                            'id' => $session->id,
                            'charging_point_id' => $session->charging_point_id,
                            'charging_point_name' => $session->chargingPoint?->name ?? 'N/A',
                            'status' => $session->status,
                            'started_at' => $session->started_at?->toISOString(),
                            'duration_seconds' => $duration,
                            'estimated_energy' => (float) $session->estimated_energy,
                            'meter_start' => (float) $session->meter_start,
                            'ocpp_tag' => $session->ocpp_tag,
                        ];
                    })->toArray(),
                    'count' => $sessions->count(),
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching active sessions', ['error' => $e->getMessage()]);
                return [
                    'sessions' => [],
                    'count' => 0,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir les métriques d'énergie
     * Données authentiques depuis la base de données et Steve API
     */
    public function getEnergyMetrics(): array
    {
        $cacheKey = 'steve:dashboard:energy';
        
        return Cache::remember($cacheKey, $this->cacheConfig['energy'], function () {
            try {
                // Total energy from charging sessions (database)
                $totalEnergy = ChargingSession::where('status', 'completed')
                    ->sum('actual_energy') ?? 0;

                // Today's energy
                $todayEnergy = ChargingSession::where('status', 'completed')
                    ->whereDate('stopped_at', Carbon::today())
                    ->sum('actual_energy') ?? 0;

                // This week's energy
                $weekEnergy = ChargingSession::where('status', 'completed')
                    ->whereBetween('stopped_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                    ->sum('actual_energy') ?? 0;

                // This month's energy
                $monthEnergy = ChargingSession::where('status', 'completed')
                    ->whereBetween('stopped_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                    ->sum('actual_energy') ?? 0;

                // Try to get additional data from Steve API
                $steveEnergy = $this->getSteveEnergyDataFromApi();

                return [
                    'total_kwh' => round($totalEnergy, 2),
                    'today_kwh' => round($todayEnergy, 2),
                    'week_kwh' => round($weekEnergy, 2),
                    'month_kwh' => round($monthEnergy, 2),
                    'steve_data' => $steveEnergy,
                    'data_source' => 'database_steve_api',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching energy metrics', ['error' => $e->getMessage()]);
                return [
                    'total_kwh' => 0,
                    'today_kwh' => 0,
                    'week_kwh' => 0,
                    'month_kwh' => 0,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir les données d'énergie depuis Steve API (authentique)
     */
    protected function getSteveEnergyDataFromApi(): array
    {
        try {
            // Fetch from Steve API directly
            $response = $this->httpClient->getChargePoints([]);
            
            $totalFromSteve = 0;
            $stationCount = 0;
            $stationsData = [];
            
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $point) {
                    $stationCount++;
                    $energy = $point['totalEnergyDelivered'] ?? $point['total_energy_delivered'] ?? $point['energy'] ?? 0;
                    $totalFromSteve += floatval($energy);
                    
                    $stationsData[] = [
                        'id' => $point['id'] ?? $point['chargeBoxId'] ?? null,
                        'name' => $point['name'] ?? $point['chargeBoxId'] ?? 'Unknown',
                        'energy_delivered' => $energy,
                    ];
                }
            }

            return [
                'stations_reporting' => $stationCount,
                'total_kwh' => round($totalFromSteve, 2),
                'stations' => $stationsData,
                'api_reachable' => true,
            ];
        } catch (\Exception $e) {
            Log::debug('SteveDashboard: Could not fetch Steve energy data', ['error' => $e->getMessage()]);
            return [
                'api_reachable' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtenir le statut des stations
     * Données depuis la base de données locale
     */
    public function getStationsStatus(): array
    {
        $cacheKey = 'steve:dashboard:stations';
        
        return Cache::remember($cacheKey, $this->cacheConfig['stations'], function () {
            try {
                $stations = ChargingPoint::with(['group', 'integrator'])
                    ->orderBy('name', 'asc')
                    ->get();

                $statusBreakdown = [
                    'online' => 0,
                    'offline' => 0,
                    'charging' => 0,
                    'available' => 0,
                    'faulted' => 0,
                    'maintenance' => 0,
                    'unknown' => 0,
                ];

                $stationsData = $stations->map(function ($station) use (&$statusBreakdown) {
                    $status = strtolower($station->status ?? 'unknown');
                    
                    // Determine status category
                    if (in_array($status, ['online', 'available', 'ready'])) {
                        $statusBreakdown['available']++;
                        $statusBreakdown['online']++;
                    } elseif (in_array($status, ['charging', 'in_use', 'occupied'])) {
                        $statusBreakdown['charging']++;
                        $statusBreakdown['online']++;
                    } elseif (in_array($status, ['offline', 'disconnected'])) {
                        $statusBreakdown['offline']++;
                    } elseif (in_array($status, ['fault', 'error', 'faulted'])) {
                        $statusBreakdown['faulted']++;
                    } elseif (in_array($status, ['maintenance', 'out_of_service'])) {
                        $statusBreakdown['maintenance']++;
                    } else {
                        $statusBreakdown['unknown']++;
                    }

                    return [
                        'id' => $station->id,
                        'name' => $station->name,
                        'serial_number' => $station->serial_number,
                        'charge_box_id' => $station->charge_box_id,
                        'status' => $station->status,
                        'manufacturer' => $station->manufacturer,
                        'model' => $station->model,
                        'group_name' => $station->group?->name,
                        'integrator_name' => $station->integrator?->name,
                        'total_energy_delivered' => (float) $station->total_energy_delivered,
                        'total_charging_sessions' => $station->total_charging_sessions,
                        'last_connection' => $station->last_connection?->toISOString(),
                        'latitude' => $station->latitude,
                        'longitude' => $station->longitude,
                    ];
                });

                return [
                    'stations' => $stationsData->toArray(),
                    'status_breakdown' => $statusBreakdown,
                    'total_count' => $stations->count(),
                    'data_source' => 'database',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching stations', ['error' => $e->getMessage()]);
                return [
                    'stations' => [],
                    'status_breakdown' => [],
                    'total_count' => 0,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir la répartition par type de chargeur
     * Données depuis la base de données et Steve API
     */
    public function getChargerTypesBreakdown(): array
    {
        $cacheKey = 'steve:dashboard:charger-types';
        
        return Cache::remember($cacheKey, $this->cacheConfig['stations'], function () {
            try {
                // Get charger types from database
                $connectorColumn = Schema::hasColumn('charging_points', 'connector_type')
                    ? 'connector_type'
                    : (Schema::hasColumn('charging_points', 'connectortype') ? 'connectortype' : null);

                $selectExpression = $connectorColumn
                    ? sprintf('COALESCE(%s, "Type 2") as charger_type, COUNT(*) as count', $connectorColumn)
                    : 'COUNT(*) as count, "Type 2" as charger_type';

                $types = ChargingPoint::selectRaw($selectExpression)
                    ->groupBy('charger_type')
                    ->pluck('count', 'charger_type')
                    ->toArray();

                // Try to get from Steve API
                $steveTypes = $this->getSteveChargerTypesFromApi();

                // Merge data
                $merged = array_merge($types, $steveTypes);
                
                // Calculate percentages
                $total = array_sum($merged);
                $result = [];
                foreach ($merged as $type => $count) {
                    $result[$type] = [
                        'count' => $count,
                        'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
                    ];
                }

                return [
                    'types' => $result,
                    'total' => $total,
                    'data_source' => 'database_steve_api',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching charger types', ['error' => $e->getMessage()]);
                return [
                    'types' => [],
                    'total' => 0,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir les types de chargeurs depuis Steve API (authentique)
     */
    protected function getSteveChargerTypesFromApi(): array
    {
        try {
            $response = $this->httpClient->getChargePoints([]);
            
            $types = [];
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $point) {
                    $type = $point['connectorType'] ?? $point['charger_type'] ?? $point['connector_type'] ?? 'Type 2';
                    $types[$type] = ($types[$type] ?? 0) + 1;
                }
            }
            return $types;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtenir les métriques par station
     * Données authentiques depuis la base de données
     */
    public function getPerStationMetrics(): array
    {
        $cacheKey = 'steve:dashboard:station-metrics';
        
        return Cache::remember($cacheKey, $this->cacheConfig['stations'], function () {
            try {
                $stations = ChargingPoint::with(['group'])
                    ->get()
                    ->map(function ($station) {
                        $completedSessions = ChargingSession::where('charging_point_id', $station->id)
                            ->where('status', 'completed')
                            ->get();

                        $totalEnergy = $completedSessions->sum('actual_energy');
                        $totalCost = $completedSessions->sum('actual_cost');
                        $avgEnergy = $completedSessions->count() > 0 
                            ? $totalEnergy / $completedSessions->count() 
                            : 0;

                        return [
                            'station_id' => $station->id,
                            'station_name' => $station->name,
                            'group_name' => $station->group?->name,
                            'total_sessions' => $station->total_charging_sessions ?? $completedSessions->count(),
                            'completed_sessions' => $completedSessions->count(),
                            'total_energy_kwh' => round($totalEnergy, 2),
                            'total_revenue' => round($totalCost, 2),
                            'avg_energy_per_session' => round($avgEnergy, 2),
                            'status' => $station->status,
                            'last_used' => $station->last_used_at?->toISOString(),
                        ];
                    });

                return [
                    'stations' => $stations->toArray(),
                    'data_source' => 'database',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching station metrics', ['error' => $e->getMessage()]);
                return [
                    'stations' => [],
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir la disponibilité des stations
     * Données authentiques depuis la base de données
     */
    public function getAvailabilityStatus(): array
    {
        $cacheKey = 'steve:dashboard:availability';
        
        return Cache::remember($cacheKey, $this->cacheConfig['active'], function () {
            try {
                $activeStatuses = ['active', 'in_progress', 'charging', 'in_use'];
                $availableStatuses = ['available', 'ready', 'online'];
                $offlineStatuses = ['offline', 'disconnected'];
                $faultedStatuses = ['fault', 'error', 'faulted'];

                $totalStations = ChargingPoint::count();
                $chargingCount = ChargingSession::whereIn('status', $activeStatuses)
                    ->whereNull('stopped_at')
                    ->count();
                $availableCount = ChargingPoint::whereIn('status', $availableStatuses)->count();
                $offlineCount = ChargingPoint::whereIn('status', $offlineStatuses)->count();
                $faultedCount = ChargingPoint::whereIn('status', $faultedStatuses)->count();

                // Calculate percentages
                $chargingPercent = $totalStations > 0 ? round(($chargingCount / $totalStations) * 100, 1) : 0;
                $availablePercent = $totalStations > 0 ? round(($availableCount / $totalStations) * 100, 1) : 0;
                $offlinePercent = $totalStations > 0 ? round(($offlineCount / $totalStations) * 100, 1) : 0;
                $faultedPercent = $totalStations > 0 ? round(($faultedCount / $totalStations) * 100, 1) : 0;

                return [
                    'total_stations' => $totalStations,
                    'charging' => [
                        'count' => $chargingCount,
                        'percentage' => $chargingPercent,
                        'label' => 'En charge',
                        'color' => 'blue',
                    ],
                    'available' => [
                        'count' => $availableCount,
                        'percentage' => $availablePercent,
                        'label' => 'Disponible',
                        'color' => 'green',
                    ],
                    'offline' => [
                        'count' => $offlineCount,
                        'percentage' => $offlinePercent,
                        'label' => 'Hors ligne',
                        'color' => 'gray',
                    ],
                    'faulted' => [
                        'count' => $faultedCount,
                        'percentage' => $faultedPercent,
                        'label' => 'En défaut',
                        'color' => 'red',
                    ],
                    'data_source' => 'database',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching availability', ['error' => $e->getMessage()]);
                return [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir la santé du réseau
     * Données authentiques depuis Steve API
     */
    public function getNetworkHealth(): array
    {
        $cacheKey = 'steve:dashboard:health';
        
        return Cache::remember($cacheKey, $this->cacheConfig['health'], function () {
            try {
                $health = $this->healthService->checkHealth();
                
                return [
                    'status' => $health['status'] ?? 'unknown',
                    'message' => $health['message'] ?? '',
                    'response_time_ms' => $health['response_time'] ?? null,
                    'api_reachable' => $health['api_reachable'] ?? false,
                    'database_connected' => $health['database_connected'] ?? false,
                    'data_source' => 'steve_api',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching network health', ['error' => $e->getMessage()]);
                return [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'api_reachable' => false,
                    'data_source' => 'error',
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Obtenir les analytiques de performance
     * Données authentiques depuis la base de données et Steve API
     */
    public function getPerformanceAnalytics(): array
    {
        $cacheKey = 'steve:dashboard:performance';
        
        return Cache::remember($cacheKey, $this->cacheConfig['performance'], function () {
            try {
                // Test API response time
                $startTime = microtime(true);
                try {
                    $this->httpClient->makeRequest('GET', '/manager/v1/health', []);
                    $apiResponseTime = round((microtime(true) - $startTime) * 1000, 2);
                    $apiReachable = true;
                } catch (\Exception $e) {
                    $apiResponseTime = null;
                    $apiReachable = false;
                }

                // Get session statistics from database
                $totalSessions = ChargingSession::count();
                $completedSessions = ChargingSession::where('status', 'completed')->count();
                $failedSessions = ChargingSession::where('status', 'failed')->count();
                $successRate = $totalSessions > 0 
                    ? round(($completedSessions / $totalSessions) * 100, 1) 
                    : 0;

                // Get average session duration
                $avgDuration = ChargingSession::where('status', 'completed')
                    ->avg('actual_duration') ?? 0;

                // Get average energy per session
                $avgEnergy = ChargingSession::where('status', 'completed')
                    ->avg('actual_energy') ?? 0;

                return [
                    'api_response_time_ms' => $apiResponseTime,
                    'api_reachable' => $apiReachable,
                    'total_sessions' => $totalSessions,
                    'completed_sessions' => $completedSessions,
                    'failed_sessions' => $failedSessions,
                    'success_rate' => $successRate,
                    'avg_session_duration_minutes' => round($avgDuration / 60, 1),
                    'avg_energy_kwh' => round($avgEnergy, 2),
                    'data_source' => 'database_steve_api',
                    'timestamp' => now()->toISOString(),
                ];
            } catch (\Exception $e) {
                Log::error('SteveDashboard: Error fetching performance', ['error' => $e->getMessage()]);
                return [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        });
    }

    /**
     * Rafraîchir toutes les données du cache
     */
    public function refreshCache(): array
    {
        Cache::forget('steve:dashboard:summary');
        Cache::forget('steve:dashboard:sessions:50');
        Cache::forget('steve:dashboard:active');
        Cache::forget('steve:dashboard:energy');
        Cache::forget('steve:dashboard:stations');
        Cache::forget('steve:dashboard:charger-types');
        Cache::forget('steve:dashboard:station-metrics');
        Cache::forget('steve:dashboard:availability');
        Cache::forget('steve:dashboard:health');
        Cache::forget('steve:dashboard:performance');

        return [
            'success' => true,
            'message' => 'Cache rafraîchi avec succès',
            'timestamp' => now()->toISOString(),
        ];
    }

    // ============================================
    // Méthodes helper privées - Données authentiques
    // ============================================

    /**
     * Obtenir le nombre total de sessions (depuis la base de données)
     */
    protected function getTotalSessions(): int
    {
        return ChargingSession::count();
    }

    /**
     * Obtenir le nombre de sessions actives (depuis la base de données)
     */
    protected function getActiveSessionsCount(): int
    {
        return ChargingSession::whereNull('stopped_at')
            ->whereIn('status', ['active', 'in_progress', 'initiating'])
            ->count();
    }

    /**
     * Obtenir l'énergie totale distribuée (depuis la base de données)
     */
    protected function getTotalEnergyDistributed(): float
    {
        return round(ChargingSession::where('status', 'completed')->sum('actual_energy') ?? 0, 2);
    }

    /**
     * Obtenir le nombre total de stations (depuis la base de données)
     */
    protected function getTotalStations(): int
    {
        return ChargingPoint::count();
    }

    /**
     * Obtenir le nombre de stations opérationnelles (depuis la base de données)
     */
    protected function getOperationalStationsCount(): int
    {
        return ChargingPoint::whereIn('status', ['online', 'available', 'ready', 'charging', 'in_use'])->count();
    }

    /**
     * Obtenir le nombre de stations disponibles (depuis la base de données)
     */
    protected function getAvailableStationsCount(): int
    {
        return ChargingPoint::whereIn('status', ['available', 'ready', 'online'])->count();
    }

    /**
     * Obtenir le nombre de stations hors ligne (depuis la base de données)
     */
    protected function getOfflineStationsCount(): int
    {
        return ChargingPoint::whereIn('status', ['offline', 'disconnected'])->count();
    }

    /**
     * Obtenir le nombre de stations en défaut (depuis la base de données)
     */
    protected function getFaultedStationsCount(): int
    {
        return ChargingPoint::whereIn('status', ['fault', 'error', 'faulted'])->count();
    }
}
