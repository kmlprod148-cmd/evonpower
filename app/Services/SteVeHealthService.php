<?php

namespace App\Services;

use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Service unifié pour le monitoring et les tests de santé SteVe
 * 
 * Ce service consolide :
 * - SteVeMonitoringService (monitoring complet)
 * - SteVeConnectionTestService (tests de connexion)
 * - SteVeIntegrationService (tests d'intégration)
 * - SteVeRealTimeMonitoringService (monitoring temps réel)
 * 
 * Fonctionnalités :
 * - Tests de connexion (serveur, auth, OCPP, WebSocket)
 * - Monitoring en temps réel des bornes
 * - Alertes et métriques système
 * - Rapports de santé détaillés
 */
class SteVeHealthService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected string $websocketUrl;
    protected array $endpoints;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->websocketUrl = config('steve.websocket_url', 'ws://158.69.27.239:8180/steve/websocket/CentralSystemService/');
        
        $this->endpoints = [
            'health' => '/api/v1/health',
            'status' => '/api/v1/status',
            'charging_points' => '/api/v1/charging-points',
            'ocpp_status' => '/api/v1/ocpp/status',
            'websocket' => '/steve/websocket/CentralSystemService',
            'soap' => '/steve/services/CentralSystemService',
            'manager' => '/steve/manager'
        ];
    }

    // ========================================================================
    // CONNECTION TESTS
    // ========================================================================

    /**
     * Test complet de connectivité SteVe
     */
    public function testFullConnectivity(): array
    {
        $startTime = microtime(true);
        
        $results = [
            'server_reachability' => $this->testServerReachability(),
            'authentication' => $this->testAuthentication(),
            'web_interface' => $this->testWebInterface(),
            'api_endpoints' => $this->testApiEndpoints(),
            'ocpp_connectivity' => $this->testOcppConnectivity(),
            'websocket_connection' => $this->testWebSocketConnection(),
            'database_connectivity' => $this->testDatabaseConnectivity()
        ];

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $overallStatus = $this->determineOverallStatus($results);

        return [
            'overall_status' => $overallStatus,
            'response_time_ms' => $duration,
            'timestamp' => now()->toISOString(),
            'details' => $results
        ];
    }

    /**
     * Test rapide de connexion (pour health checks)
     */
    public function quickHealthCheck(): array
    {
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . $this->endpoints['health']);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'healthy' => $response->successful(),
                'status' => $response->successful() ? 'online' : 'offline',
                'response_time_ms' => $duration,
                'timestamp' => now()->toISOString()
            ];
        } catch (Exception $e) {
            return [
                'healthy' => false,
                'status' => 'offline',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Backward-compatible health payload used by dashboard consumers.
     */
    public function checkHealth(): array
    {
        $serverStatus = $this->quickHealthCheck();
        $databaseStatus = $this->testDatabaseConnectivity();

        $apiReachable = (bool) ($serverStatus['healthy'] ?? false);
        $databaseConnected = ($databaseStatus['status'] ?? 'error') === 'success';

        return [
            'status' => $serverStatus['status'] ?? 'unknown',
            'message' => $apiReachable
                ? 'SteVe health check successful'
                : ($serverStatus['error'] ?? 'SteVe server is unreachable'),
            'response_time' => $serverStatus['response_time_ms'] ?? null,
            'response_time_ms' => $serverStatus['response_time_ms'] ?? null,
            'api_reachable' => $apiReachable,
            'database_connected' => $databaseConnected,
            'healthy' => $apiReachable && $databaseConnected,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Test de portée du serveur (socket)
     */
    public function testServerReachability(): array
    {
        try {
            $host = parse_url($this->baseUrl, PHP_URL_HOST);
            $port = parse_url($this->baseUrl, PHP_URL_PORT) ?: 80;
            
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if ($connection) {
                fclose($connection);
                return [
                    'status' => 'success',
                    'message' => "Serveur accessible sur {$host}:{$port}",
                    'host' => $host,
                    'port' => $port
                ];
            }

            return [
                'status' => 'error',
                'message' => "Serveur non accessible: {$errstr} ({$errno})",
                'host' => $host,
                'port' => $port
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur de connexion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test d'authentification
     */
    public function testAuthentication(): array
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
            }

            return [
                'status' => 'error',
                'message' => 'Échec de l\'authentification',
                'response_code' => $response->status()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur d\'authentification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test de l'interface web SteVe
     */
    public function testWebInterface(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $this->endpoints['manager']);
            
            return [
                'status' => $response->successful() ? 'success' : 'warning',
                'message' => $response->successful() ? 'Interface web accessible' : 'Interface web répond avec HTTP ' . $response->status(),
                'response_code' => $response->status()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur interface web: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test des endpoints API
     */
    public function testApiEndpoints(): array
    {
        $endpointResults = [];
        
        foreach ($this->endpoints as $name => $endpoint) {
            try {
                $startTime = microtime(true);
                
                $response = Http::timeout(10)
                    ->withBasicAuth($this->username, $this->password)
                    ->get($this->baseUrl . $endpoint);

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                $endpointResults[$name] = [
                    'status' => $response->successful() ? 'success' : 'error',
                    'response_code' => $response->status(),
                    'response_time_ms' => $duration,
                    'message' => $response->successful() ? 'Endpoint accessible' : 'Endpoint non accessible'
                ];
            } catch (Exception $e) {
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
    public function testOcppConnectivity(): array
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
                    'total_charging_points' => $data['total_charging_points'] ?? 0,
                    'protocol_version' => $data['protocol_version'] ?? 'OCPP 1.6'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Connectivité OCPP non disponible'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur OCPP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test de connexion WebSocket
     */
    public function testWebSocketConnection(): array
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . $this->endpoints['websocket']);

            $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $this->baseUrl) 
                . $this->endpoints['websocket'];

            return [
                'status' => $response->successful() ? 'success' : 'warning',
                'message' => $response->successful() ? 'WebSocket endpoint disponible' : 'WebSocket non disponible',
                'websocket_url' => $wsUrl,
                'response_code' => $response->status()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur WebSocket: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test de l'endpoint OCPP SOAP
     */
    public function testOcppSoap(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . $this->endpoints['soap']);
            
            return [
                'status' => $response->successful() ? 'success' : 'warning',
                'message' => $response->successful() ? 'Endpoint OCPP SOAP accessible' : 'Endpoint OCPP SOAP répond avec HTTP ' . $response->status(),
                'response_code' => $response->status()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur endpoint OCPP SOAP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test de connectivité base de données locale
     */
    public function testDatabaseConnectivity(): array
    {
        try {
            $totalPoints = ChargingPoint::count();
            $onlinePoints = ChargingPoint::where('status', 'online')->count();
            
            return [
                'status' => 'success',
                'message' => 'Base de données accessible',
                'total_charging_points' => $totalPoints,
                'online_points' => $onlinePoints,
                'offline_points' => $totalPoints - $onlinePoints
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur base de données: ' . $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // REAL-TIME MONITORING
    // ========================================================================

    /**
     * Obtenir le statut en temps réel (avec cache)
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
            'charging_points' => $this->getChargingPointsStatus(),
            'ocpp_connections' => $this->getOcppConnectionsStatus(),
            'system_health' => $this->getSystemHealth()
        ];

        Cache::put($cacheKey, $status, 60);

        return $status;
    }

    /**
     * Statut du serveur SteVe
     */
    public function getServerStatus(): array
    {
        try {
            $startTime = microtime(true);
            
            $response = Http::timeout(5)
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . $this->endpoints['health']);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $response->successful() ? 'online' : 'offline',
                'response_time_ms' => $duration,
                'last_check' => now()->toISOString()
            ];
        } catch (Exception $e) {
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
    public function getChargingPointsStatus(): array
    {
        try {
            $points = ChargingPoint::with(['businessProfile', 'integrator'])
                ->select(['id', 'name', 'charge_box_id', 'status', 'last_connected_at', 'last_disconnected_at'])
                ->get();

            $summary = [
                'total' => $points->count(),
                'online' => $points->where('status', 'online')->count(),
                'offline' => $points->where('status', 'offline')->count(),
                'unknown' => $points->where('status', 'unknown')->count(),
            ];

            $summary['points'] = $points->map(function ($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'charge_box_id' => $point->charge_box_id,
                    'status' => $point->status,
                    'is_online' => $point->status === 'online',
                    'last_connected' => $point->last_connected_at?->format('Y-m-d H:i:s'),
                    'last_seen' => $point->last_connected_at?->diffForHumans(),
                    'business_profile' => $point->businessProfile?->name ?? 'N/A',
                    'integrator' => $point->integrator?->name ?? 'N/A'
                ];
            });

            return [
                'status' => 'success',
                'data' => $summary
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Statut des connexions OCPP
     */
    public function getOcppConnectionsStatus(): array
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
            }

            return [
                'status' => 'inactive',
                'error' => 'OCPP service non disponible'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Santé du système
     */
    public function getSystemHealth(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_usage_formatted' => $this->formatBytes(memory_get_usage(true)),
            'uptime' => function_exists('sys_getloadavg') ? sys_getloadavg() : null,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }

    // ========================================================================
    // ALERTS & NOTIFICATIONS
    // ========================================================================

    /**
     * Vérifier les alertes actives
     */
    public function checkAlerts(): array
    {
        $alerts = [];
        
        // Vérifier la connexion serveur
        $serverStatus = $this->quickHealthCheck();
        if (!$serverStatus['healthy']) {
            $alerts[] = [
                'type' => 'critical',
                'category' => 'server',
                'message' => 'Serveur SteVe non accessible',
                'timestamp' => now()->toISOString()
            ];
        }

        // Vérifier les bornes hors ligne
        $offlinePoints = ChargingPoint::where('status', 'offline')
            ->where('last_connected_at', '>', now()->subDay())
            ->count();
            
        if ($offlinePoints > 0) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'charging_points',
                'message' => "{$offlinePoints} borne(s) hors ligne récemment",
                'count' => $offlinePoints,
                'timestamp' => now()->toISOString()
            ];
        }

        // Vérifier les bornes sans activité récente
        $inactivePoints = ChargingPoint::where('last_connected_at', '<', now()->subHours(24))
            ->orWhereNull('last_connected_at')
            ->count();
            
        if ($inactivePoints > 0) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'charging_points',
                'message' => "{$inactivePoints} borne(s) sans activité depuis 24h",
                'count' => $inactivePoints,
                'timestamp' => now()->toISOString()
            ];
        }

        return [
            'has_alerts' => count($alerts) > 0,
            'critical_count' => collect($alerts)->where('type', 'critical')->count(),
            'warning_count' => collect($alerts)->where('type', 'warning')->count(),
            'info_count' => collect($alerts)->where('type', 'info')->count(),
            'alerts' => $alerts
        ];
    }

    // ========================================================================
    // REPORTS
    // ========================================================================

    /**
     * Générer un rapport détaillé
     */
    public function getDetailedReport(): string
    {
        $results = $this->testFullConnectivity();
        
        $report = "=== RAPPORT DE SANTÉ STEVE ===\n";
        $report .= "URL: {$this->baseUrl}\n";
        $report .= "Utilisateur: {$this->username}\n";
        $report .= "Timeout: {$this->timeout}s\n";
        $report .= "Généré le: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        $report .= "Statut global: " . strtoupper($results['overall_status']) . "\n";
        $report .= "Temps de réponse: {$results['response_time_ms']}ms\n\n";
        
        $report .= "--- Détails des tests ---\n\n";
        
        foreach ($results['details'] as $test => $result) {
            $status = $result['status'] ?? 'unknown';
            $message = $result['message'] ?? 'N/A';
            
            $icon = match ($status) {
                'success' => '[OK]',
                'warning' => '[WARN]',
                'error' => '[ERROR]',
                'info' => '[INFO]',
                default => '[?]'
            };
            
            $report .= "{$icon} {$test}: {$message}\n";
            
            if (isset($result['response_code'])) {
                $report .= "    Code HTTP: {$result['response_code']}\n";
            }
        }
        
        return $report;
    }

    /**
     * Obtenir les métriques pour un dashboard
     */
    public function getDashboardMetrics(): array
    {
        $chargingPoints = $this->getChargingPointsStatus();
        $serverStatus = $this->quickHealthCheck();
        $alerts = $this->checkAlerts();

        return [
            'server' => [
                'status' => $serverStatus['status'],
                'healthy' => $serverStatus['healthy'],
                'response_time_ms' => $serverStatus['response_time_ms'] ?? null
            ],
            'charging_points' => [
                'total' => $chargingPoints['data']['total'] ?? 0,
                'online' => $chargingPoints['data']['online'] ?? 0,
                'offline' => $chargingPoints['data']['offline'] ?? 0,
                'online_percentage' => $chargingPoints['data']['total'] > 0 
                    ? round(($chargingPoints['data']['online'] / $chargingPoints['data']['total']) * 100, 1)
                    : 0
            ],
            'alerts' => [
                'critical' => $alerts['critical_count'],
                'warning' => $alerts['warning_count'],
                'info' => $alerts['info_count']
            ],
            'timestamp' => now()->toISOString()
        ];
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Déterminer le statut global
     */
    protected function determineOverallStatus(array $results): string
    {
        $errorCount = 0;
        $warningCount = 0;
        $totalTests = 0;

        foreach ($results as $test) {
            if (is_array($test)) {
                if (isset($test['status'])) {
                    $totalTests++;
                    if ($test['status'] === 'error') {
                        $errorCount++;
                    } elseif ($test['status'] === 'warning') {
                        $warningCount++;
                    }
                } else {
                    // Nested results (like api_endpoints)
                    foreach ($test as $subTest) {
                        if (isset($subTest['status'])) {
                            $totalTests++;
                            if ($subTest['status'] === 'error') {
                                $errorCount++;
                            } elseif ($subTest['status'] === 'warning') {
                                $warningCount++;
                            }
                        }
                    }
                }
            }
        }

        if ($errorCount === 0 && $warningCount === 0) {
            return 'healthy';
        } elseif ($errorCount === 0) {
            return 'warning';
        } elseif ($errorCount < $totalTests / 2) {
            return 'degraded';
        } else {
            return 'critical';
        }
    }

    /**
     * Formater les bytes
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Générer l'URL WebSocket pour un chargeur
     */
    public function generateWebSocketUrl(string $chargerId): string
    {
        return $this->websocketUrl . $chargerId;
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
                Log::info('Commande OCPP envoyée', [
                    'charging_point' => $chargingPoint->name,
                    'command' => $command,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'message' => 'Commande envoyée avec succès',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur: ' . $response->body()
            ];
        } catch (Exception $e) {
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
