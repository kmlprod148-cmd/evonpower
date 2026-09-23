<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Helpers\SteveOcppHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service d'intégration avec SteVe (EVON Server)
 * 
 * Ce service gère la connexion et la communication avec le serveur SteVe
 * pour la gestion des bornes de charge OCPP
 */
class SteVeIntegrationService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
    }

    /**
     * Probe the SteVe management server and report whether it answers as
     * expected from this Laravel side.
     *
     * Slice 3 (2026-05-17): trimmed the previous probe set —
     *   testSoapEndpoint()  → hit `/steve/services/CentralSystemService` (a
     *                         SOAP path our REST integration never speaks)
     *   testApiAccess()     → hit `/steve/api` (no such endpoint in 3.9.0)
     *   testWebSocketEndpoint() → returned the WS URL without actually
     *                             connecting, i.e. always "info" status
     * All three lived on as zombie diagnostics — green when SteVe was up,
     * but green even when the management REST API was unreachable. The
     * canonical probe is now a real authenticated GET against the canonical
     * management REST surface.
     */
    public function testConnection(): array
    {
        $results = [
            'connectivity'   => $this->testConnectivity(),
            'authentication' => $this->testAuthentication(),
        ];

        return [
            'overall_status' => $this->determineOverallStatus($results),
            'details'        => $results,
            'timestamp'      => now()->toISOString(),
        ];
    }

    /**
     * Test de connectivité de base
     * 
     * @return array
     */
    public function testConnectivity(): array
    {
        try {
            $host = parse_url($this->baseUrl, PHP_URL_HOST);
            $port = parse_url($this->baseUrl, PHP_URL_PORT) ?: 80;
            
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            if ($connection) {
                fclose($connection);
                return [
                    'status' => 'success',
                    'message' => "Port {$port} accessible sur {$host}",
                    'host' => $host,
                    'port' => $port
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => "Port {$port} non accessible sur {$host}: {$errstr} ({$errno})",
                    'host' => $host,
                    'port' => $port,
                    'error' => $errstr
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur de connectivité: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test d'authentification — probes the canonical management REST surface
     * (`GET /manager/api/v1/chargePoints?…`) with Basic Auth.
     *
     * A 200/204 response is the only signal that the SteVe REST API is both
     * reachable AND happy with our credentials. A 401/403 means creds are
     * wrong; anything else points at SteVe-side issues.
     */
    public function testAuthentication(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->acceptJson()
                ->get($this->restProbeUrl());

            if ($response->successful()) {
                return [
                    'status'    => 'success',
                    'message'   => 'Authentification REST réussie',
                    'http_code' => $response->status(),
                ];
            }

            if (in_array($response->status(), [401, 403], true)) {
                return [
                    'status'    => 'error',
                    'message'   => 'Identifiants SteVe refusés (' . $response->status() . ')',
                    'http_code' => $response->status(),
                ];
            }

            return [
                'status'    => 'warning',
                'message'   => 'API REST répond avec HTTP ' . $response->status(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => 'Erreur d\'authentification: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve the canonical REST probe URL relative to the configured base.
     * Mirrors the path-prefix logic in `SteVeHttpClientService::apiPath()` so
     * deployments using `…/manager`, `…/manager/api/v1`, or a bare host all
     * land on `…/manager/api/v1/chargePoints`.
     */
    protected function restProbeUrl(): string
    {
        $base = rtrim($this->baseUrl, '/');
        $basePath = rtrim(parse_url($base, PHP_URL_PATH) ?: '', '/');

        $prefix = match (true) {
            str_ends_with($basePath, '/manager/api/v1') => '',
            str_ends_with($basePath, '/manager')        => '/api/v1',
            str_ends_with($basePath, '/api/v1')         => '',
            default                                     => '/manager/api/v1',
        };

        return $base . $prefix . '/chargePoints';
    }

    /**
     * Connecte une borne de charge à SteVe
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function connectChargingPoint(ChargingPoint $chargingPoint): array
    {
        try {
            $chargeBoxId = $chargingPoint->charge_box_id;
            
            if (!$chargeBoxId) {
                throw new \Exception('Charge Box ID manquant pour la borne');
            }

            // Vérifier la connectivité SteVe
            $connectionTest = $this->testConnection();
            if ($connectionTest['overall_status'] !== 'success') {
                throw new \Exception('SteVe non accessible: ' . json_encode($connectionTest['details']));
            }

            // Mettre à jour le statut de la borne
            $chargingPoint->update([
                'status' => 'online',
                'last_connected_at' => now(),
                'steve_connection_status' => 'connected'
            ]);

            Log::info('Borne connectée à SteVe', [
                'charging_point_id' => $chargingPoint->id,
                'charge_box_id' => $chargeBoxId,
                'status' => 'connected'
            ]);

            return [
                'success' => true,
                'message' => 'Borne connectée avec succès à SteVe',
                'charge_box_id' => $chargeBoxId,
                'websocket_url' => $this->getWebSocketUrl($chargeBoxId)
            ];

        } catch (\Exception $e) {
            Log::error('Erreur connexion borne à SteVe', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur connexion: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Déconnecte une borne de charge de SteVe
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function disconnectChargingPoint(ChargingPoint $chargingPoint): array
    {
        try {
            $chargingPoint->update([
                'status' => 'offline',
                'last_disconnected_at' => now(),
                'steve_connection_status' => 'disconnected'
            ]);

            Log::info('Borne déconnectée de SteVe', [
                'charging_point_id' => $chargingPoint->id,
                'charge_box_id' => $chargingPoint->charge_box_id
            ]);

            return [
                'success' => true,
                'message' => 'Borne déconnectée avec succès de SteVe'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur déconnexion borne de SteVe', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur déconnexion: ' . $e->getMessage()
            ];
        }
    }

    // sendOcppCommand() was removed in Slice 3 (2026-05-17). It only logged
    // the payload and returned `success:true` — i.e. a silent no-op pretending
    // to dispatch an OCPP message. Real command dispatch lives in
    // `OcppOperationsService` / `SteVeHttpClientService` over the canonical
    // `/manager/api/v1/ocpp/*` surface; callers should use those instead.

    /**
     * Obtient l'URL WebSocket pour une borne
     *
     * @param string $chargeBoxId
     * @return string
     */
    public function getWebSocketUrl(string $chargeBoxId): string
    {
        return SteveOcppHelper::getWebSocketUrl($chargeBoxId);
    }

    /**
     * Récupère l'ID de la borne depuis le périphérique lui-même
     *
     * @param ChargingPoint $chargingPoint
     * @return string
     */
    public function getChargeBoxIdFromDevice(ChargingPoint $chargingPoint): string
    {
        // Priorité: charge_box_id > serial_number > generated ID
        if ($chargingPoint->charge_box_id) {
            return $chargingPoint->charge_box_id;
        }

        if ($chargingPoint->serial_number) {
            return $chargingPoint->serial_number;
        }

        // Générer un ID basé sur le nom et l'ID
        return "BORNE_{$chargingPoint->id}";
    }

    /**
     * Vérifie le statut du serveur SteVe
     *
     * @return array
     */
    public function checkServerStatus(): array
    {
        try {
            $connectivity = $this->testConnectivity();
            $authentication = $this->testAuthentication();

            $isOnline = $connectivity['status'] === 'success' && $authentication['status'] === 'success';

            return [
                'online' => $isOnline,
                'status' => $isOnline ? 'connected' : 'disconnected',
                'connectivity' => $connectivity,
                'authentication' => $authentication,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Erreur vérification statut serveur SteVe', [
                'error' => $e->getMessage()
            ]);

            return [
                'online' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Génère l'URL WebSocket complète pour une borne
     *
     * @param ChargingPoint $chargingPoint
     * @return string
     */
    public function generateWebSocketUrl(ChargingPoint $chargingPoint): string
    {
        $chargeBoxId = $this->getChargeBoxIdFromDevice($chargingPoint);
        return $this->getWebSocketUrl($chargeBoxId);
    }

    /**
     * Obtient la configuration SteVe
     * 
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'base_url'               => $this->baseUrl,
            'rest_probe_url'         => $this->baseUrl !== '' ? $this->restProbeUrl() : null,
            'timeout'                => $this->timeout,
            'websocket_url_template' => $this->getWebSocketUrl('{chargeBoxId}'),
            'protocol'               => 'OCPP 1.6',
            'authentication'         => 'Basic Auth',
        ];
    }

    /**
     * Détermine le statut global de la connexion
     * 
     * @param array $results
     * @return string
     */
    private function determineOverallStatus(array $results): string
    {
        $statuses = array_column($results, 'status');
        
        if (in_array('error', $statuses)) {
            return 'error';
        } elseif (in_array('warning', $statuses)) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Obtient les statistiques de connexion SteVe
     * 
     * @return array
     */
    public function getConnectionStatistics(): array
    {
        $cacheKey = 'steve_connection_stats';
        
        return Cache::remember($cacheKey, 300, function () {
            $connectedPoints = ChargingPoint::where('steve_connection_status', 'connected')->count();
            $totalPoints = ChargingPoint::count();
            
            return [
                'connected_points' => $connectedPoints,
                'total_points' => $totalPoints,
                'connection_rate' => $totalPoints > 0 ? round(($connectedPoints / $totalPoints) * 100, 2) : 0,
                'last_test' => now()->toISOString()
            ];
        });
    }
}
