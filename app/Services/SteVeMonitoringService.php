<?php

namespace App\Services;

use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de monitoring complet pour SteVe (EVON Server)
 * 
 * @deprecated Use SteVeHealthService instead. This class will be removed in a future version.
 * 
 * Ce service gère la surveillance en temps réel des connexions SteVe,
 * les tests de connectivité et le monitoring des bornes OCPP
 */
class SteVeMonitoringService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected array $endpoints;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        
        $this->endpoints = [
            'health' => '/api/v1/health',
            'status' => '/api/v1/status',
            'charging_points' => '/api/v1/charging-points',
            'ocpp_status' => '/api/v1/ocpp/status',
            'websocket' => '/steve/websocket/CentralSystemService',
            'soap' => '/steve/services/CentralSystemService'
        ];
    }

    /**
     * Test complet de connectivité SteVe
     */
    public function testFullConnectivity(): array
    {
        $startTime = microtime(true);
        
        $results = [
            'server_reachability' => $this->testServerReachability(),
            'authentication' => $this->testAuthentication(),
            'api_endpoints' => $this->testApiEndpoints(),
            'ocpp_connectivity' => $this->testOcppConnectivity(),
            'websocket_connection' => $this->testWebSocketConnection(),
            'database_connectivity' => $this->testDatabaseConnectivity(),
            'charging_points_status' => $this->getChargingPointsStatus()
        ];

        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);

        $overallStatus = $this->determineOverallStatus($results);

        return [
            'overall_status' => $overallStatus,
            'response_time_ms' => $responseTime,
            'timestamp' => now()->toISOString(),
            'details' => $results
        ];
    }

    /**
     * Test de la portée du serveur
     */
    protected function testServerReachability(): array
    {
        try {
            $host = parse_url($this->baseUrl, PHP_URL_HOST);
            $port = parse_url($this->baseUrl, PHP_URL_PORT) ?: 80;
            
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if ($connection) {
                fclose($connection);
                return [
                    'status' => 'success',
                    'message' => "Serveur accessible sur $host:$port",
                    'host' => $host,
                    'port' => $port
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => "Serveur non accessible: $errstr ($errno)",
                    'host' => $host,
                    'port' => $port
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur de connexion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test d'authentification
     */
    protected function testAuthentication(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . $this->endpoints['health']);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'Authentification réussie',
                    'response_code' => $response->status()
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Échec de l\'authentification: ' . $response->body(),
                    'response_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur d\'authentification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test des endpoints API
     */
    protected function testApiEndpoints(): array
    {
        $endpointResults = [];
        
        foreach ($this->endpoints as $name => $endpoint) {
            try {
                $response = Http::timeout(10)
                    ->withBasicAuth($this->username, $this->password)
                    ->get($this->baseUrl . $endpoint);

                $endpointResults[$name] = [
                    'status' => $response->successful() ? 'success' : 'error',
                    'response_code' => $response->status(),
                    'response_time_ms' => $response->transferStats?->getHandlerStat('total_time') * 1000 ?? 0,
                    'message' => $response->successful() ? 'Endpoint accessible' : 'Endpoint non accessible'
                ];
            } catch (\Exception $e) {
                $endpointResults[$name] = [
                    'status' => 'error',
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        }

        return $endpointResults;
    }

    /**
     * Test de connectivité OCPP
     */
    protected function testOcppConnectivity(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . $this->endpoints['ocpp_status']);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'success',
                    'message' => 'Connectivité OCPP active',
                    'active_connections' => $data['active_connections'] ?? 0,
                    'total_charging_points' => $data['total_charging_points'] ?? 0
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Connectivité OCPP non disponible: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur OCPP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test de connexion WebSocket
     */
    protected function testWebSocketConnection(): array
    {
        try {
            // Test de la disponibilité du endpoint WebSocket
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/steve/websocket/');

            return [
                'status' => $response->successful() ? 'success' : 'error',
                'message' => $response->successful() ? 'WebSocket endpoint disponible' : 'WebSocket non disponible',
                'response_code' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur WebSocket: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test de connectivité base de données
     */
    protected function testDatabaseConnectivity(): array
    {
        try {
            $chargingPoints = ChargingPoint::count();
            $onlinePoints = ChargingPoint::where('status', 'online')->count();
            
            return [
                'status' => 'success',
                'message' => 'Base de données accessible',
                'total_charging_points' => $chargingPoints,
                'online_points' => $onlinePoints,
                'offline_points' => $chargingPoints - $onlinePoints
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur base de données: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le statut des points de charge
     */
    protected function getChargingPointsStatus(): array
    {
        try {
            $chargingPoints = ChargingPoint::with(['businessProfile', 'integrator'])
                ->select(['id', 'name', 'charge_box_id', 'status', 'last_connected_at', 'last_disconnected_at'])
                ->get();

            $statusSummary = [
                'total' => $chargingPoints->count(),
                'online' => $chargingPoints->where('status', 'online')->count(),
                'offline' => $chargingPoints->where('status', 'offline')->count(),
                'unknown' => $chargingPoints->where('status', 'unknown')->count(),
                'points' => $chargingPoints->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'name' => $point->name,
                        'charge_box_id' => $point->charge_box_id,
                        'status' => $point->status,
                        'last_connected' => $point->last_connected_at?->format('Y-m-d H:i:s'),
                        'business_profile' => $point->businessProfile?->name ?? 'N/A',
                        'integrator' => $point->integrator?->name ?? 'N/A'
                    ];
                })
            ];

            return [
                'status' => 'success',
                'message' => 'Statut des points de charge récupéré',
                'data' => $statusSummary
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Déterminer le statut global
     */
    protected function determineOverallStatus(array $results): string
    {
        $errorCount = 0;
        $totalTests = count($results);

        foreach ($results as $test) {
            if (is_array($test) && isset($test['status'])) {
                if ($test['status'] === 'error') {
                    $errorCount++;
                }
            } elseif (is_array($test)) {
                // Pour les tests avec plusieurs endpoints
                foreach ($test as $endpoint) {
                    if (isset($endpoint['status']) && $endpoint['status'] === 'error') {
                        $errorCount++;
                    }
                }
            }
        }

        if ($errorCount === 0) {
            return 'success';
        } elseif ($errorCount < $totalTests / 2) {
            return 'warning';
        } else {
            return 'error';
        }
    }

    /**
     * Monitoring en temps réel des connexions
     */
    public function getRealTimeStatus(): array
    {
        $cacheKey = 'steve_realtime_status';
        $cached = Cache::get($cacheKey);
        
        if ($cached && Carbon::parse($cached['timestamp'])->diffInSeconds(now()) < 30) {
            return $cached;
        }

        $status = [
            'timestamp' => now()->toISOString(),
            'server_status' => $this->getServerStatus(),
            'charging_points' => $this->getChargingPointsRealTimeStatus(),
            'ocpp_connections' => $this->getOcppConnectionsStatus(),
            'system_health' => $this->getSystemHealth()
        ];

        Cache::put($cacheKey, $status, 60); // Cache pour 1 minute

        return $status;
    }

    /**
     * Statut du serveur
     */
    protected function getServerStatus(): array
    {
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . $this->endpoints['health']);

            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'response_time_ms' => $response->transferStats?->getHandlerStat('total_time') * 1000 ?? 0,
                'last_check' => now()->toISOString()
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
     * Statut en temps réel des points de charge
     */
    protected function getChargingPointsRealTimeStatus(): array
    {
        $points = ChargingPoint::select(['id', 'name', 'charge_box_id', 'status', 'last_connected_at'])
            ->get()
            ->map(function ($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'charge_box_id' => $point->charge_box_id,
                    'status' => $point->status,
                    'last_seen' => $point->last_connected_at?->diffForHumans(),
                    'is_online' => $point->status === 'online'
                ];
            });

        return [
            'total' => $points->count(),
            'online' => $points->where('is_online', true)->count(),
            'offline' => $points->where('is_online', false)->count(),
            'points' => $points
        ];
    }

    /**
     * Statut des connexions OCPP
     */
    protected function getOcppConnectionsStatus(): array
    {
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . $this->endpoints['ocpp_status']);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'active',
                    'active_connections' => $data['active_connections'] ?? 0,
                    'total_charge_boxes' => $data['total_charge_boxes'] ?? 0,
                    'protocol_version' => $data['protocol_version'] ?? 'OCPP 1.6'
                ];
            } else {
                return [
                    'status' => 'inactive',
                    'error' => 'OCPP service non disponible'
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
     * Santé du système
     */
    protected function getSystemHealth(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'uptime' => sys_getloadavg(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }

    /**
     * Envoyer une commande OCPP en temps réel
     */
    public function sendRealTimeCommand(ChargingPoint $chargingPoint, string $command, array $params = []): array
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'command' => $command,
                'parameters' => $params,
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl . '/api/v1/ocpp/command', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log de la commande
                Log::info('Commande OCPP envoyée', [
                    'charging_point' => $chargingPoint->name,
                    'command' => $command,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => 'Commande envoyée avec succès',
                    'data' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de la commande: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erreur commande OCPP', [
                'charging_point' => $chargingPoint->name,
                'command' => $command,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de connexion: ' . $e->getMessage()
            ];
        }
    }
}
