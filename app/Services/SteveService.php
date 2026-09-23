<?php

namespace App\Services;

use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SteveService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $user;
    protected string $pass;

    public function __construct(private readonly ?OCPPCommandService $ocpp = null)
    {
        $this->baseUrl = $this->normalizeBaseUrl(config('services.steve.url', '') ?? '');
        $this->apiKey = config('services.steve.key', '') ?? '';
        $this->user = config('services.steve.user', '') ?? '';
        $this->pass = config('services.steve.pass', '') ?? '';
        
        // Log de la configuration pour le débogage
        if (empty($this->user) || empty($this->pass)) {
            Log::warning('SteveService: Authentication not configured', [
                'user_configured' => !empty($this->user),
                'pass_configured' => !empty($this->pass),
                'config_user' => config('services.steve.user'),
                'config_pass' => config('services.steve.pass') ? '***' : null,
            ]);
        }
    }

    /**
     * Normalize the base URL to ensure it contains /steve/api/v1
     * Handles various input formats:
     * - http://host:port -> http://host:port/steve/api/v1
     * - http://host:port/steve -> http://host:port/steve/api/v1
     * - http://host:port/steve/api/v1 -> http://host:port/steve/api/v1 (unchanged)
     */
    protected function normalizeBaseUrl(string $url): string
    {
        $url = rtrim($url, '/');
        
        if (empty($url)) {
            return '';
        }
        
        // Si l'URL contient déjà /steve/api/v1, la retourner telle quelle
        if (preg_match('#/steve/api/v1$#', $url)) {
            return $url;
        }
        
        // Si l'URL contient /api/v1 mais pas /steve, ajouter /steve devant
        if (preg_match('#/api/v1$#', $url) && !str_contains($url, '/steve')) {
            return preg_replace('#/api/v1$#', '/steve/api/v1', $url);
        }
        
        // Si l'URL se termine par /steve, ajouter /api/v1
        if (preg_match('#/steve$#', $url)) {
            return $url . '/api/v1';
        }
        
        // Sinon, ajouter le chemin complet /steve/api/v1
        return $url . '/steve/api/v1';
    }

    /**
     * Get headers for API requests
     */
    protected function headers(): array
    {
        $h = ['Accept' => 'application/json'];
        if ($this->apiKey) {
            $h['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        return $h;
    }

    /**
     * Get HTTP client with authentication (API key or basic auth)
     */
    protected function httpClient()
    {
        $client = Http::timeout(30)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
        
        // Priorité à l'authentification basique si disponible
        if (!empty($this->user) && !empty($this->pass)) {
            return $client->withBasicAuth($this->user, $this->pass);
        }
        
        // IMPORTANT: ne pas tomber sur des credentials par défaut (dangereux en prod).
        // Si user/pass sont absents, on continue sans BasicAuth (et on loggue).
        
        // Sinon, utiliser l'API key si disponible
        if (!empty($this->apiKey)) {
            return $client->withHeaders($this->headers());
        }
        
        // Avertir si aucune authentification n'est configurée
        Log::warning('SteveService: No authentication configured - request may fail');
        
        return $client;
    }

    /**
     * Get all charge points from Steve API.
     * According to Steve API docs: GET /api/v1/chargePoints
     * 
     * @param array $params Optional query parameters (chargeBoxId, description, note, ocppVersion, heartbeatPeriod)
     * @return array|null Array of charge points or null on failure
     */
    public function getChargePoints(array $params = []): ?array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for getChargePoints');
            return null;
        }

        try {
            // L'URL de base devrait déjà contenir /api/v1 selon la config
            $url = rtrim($this->baseUrl, '/') . '/chargePoints';
            
            Log::info('SteveService: Fetching charge points', [
                'url' => $url,
                'params' => $params,
                'auth_configured' => !empty($this->user)
            ]);
            
            $response = $this->httpClient()->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('SteveService: Charge points retrieved successfully', [
                    'count' => is_array($data) ? count($data) : 0,
                    'response_type' => gettype($data)
                ]);
                
                // L'API Steve retourne directement un tableau de charge points
                if (is_array($data)) {
                    return $data;
                }
                
                // Si c'est un objet avec une propriété 'data' ou 'chargePoints'
                if (isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                }
                if (isset($data['chargePoints']) && is_array($data['chargePoints'])) {
                    return $data['chargePoints'];
                }
                
                return [];
            }

            Log::warning('Steve API getChargePoints failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('SteveService getChargePoints error: ' . $e->getMessage(), [
                'url' => isset($url) ? $url : 'N/A',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get a charge point by chargePointPk from Steve API.
     * According to Steve API docs: GET /api/v1/chargePoints/{chargePointPk}
     * 
     * @param int|string $chargePointPk The charge point primary key (chargeBoxPk)
     * @return array|null Response from Steve API or null on failure
     */
    public function getChargePoint($chargePointPk): ?array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for getChargePoint');
            return null;
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/chargePoints/' . $chargePointPk;
            
            Log::info('SteveService: Fetching charge point', [
                'chargePointPk' => $chargePointPk,
                'url' => $url
            ]);
            
            $response = $this->httpClient()->get($url);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('SteveService: Charge point retrieved successfully', [
                    'chargePointPk' => $chargePointPk,
                    'chargeBoxId' => $result['chargeBoxId'] ?? 'N/A'
                ]);
                return $result;
            }

            Log::warning('Steve API getChargePoint failed', [
                'chargePointPk' => $chargePointPk,
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;

        } catch (\Throwable $e) {
            Log::error('SteveService getChargePoint error: ' . $e->getMessage(), [
                'chargePointPk' => $chargePointPk,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create a complete charging point from ChargingPoint model on Steve API.
     * This method extracts all available information from the model and creates it in SteVe.
     * 
     * @param \App\Models\ChargingPoint $chargingPoint The charging point model
     * @return array Response from Steve API with success status and data
     */
    public function createCompleteChargingPointFromModel(\App\Models\ChargingPoint $chargingPoint): array
    {
        // Préparer toutes les données disponibles depuis le modèle
        // Utiliser steve_charging_point_id en priorité, sinon serial_number
        $steveId = $chargingPoint->steve_charging_point_id ?: $chargingPoint->serial_number;
        
        $data = [
            'steve_charging_point_id' => $steveId,
            'chargeBoxId' => $chargingPoint->charge_box_id ?: $chargingPoint->serial_number,
            'serial_number' => $chargingPoint->serial_number,
            'name' => $chargingPoint->name,
            'latitude' => $chargingPoint->latitude,
            'longitude' => $chargingPoint->longitude,
        ];
        
        // Adresse complète (seulement si disponible)
        if (!empty($chargingPoint->address)) {
            $data['address'] = $chargingPoint->address;
            $data['street'] = $chargingPoint->address; // Pour l'objet address de Steve
        }
        if (!empty($chargingPoint->city)) {
            $data['city'] = $chargingPoint->city;
        }
        if (!empty($chargingPoint->postal_code)) {
            $data['postal_code'] = $chargingPoint->postal_code;
            $data['zip_code'] = $chargingPoint->postal_code; // Alias pour compatibilité
        }
        if (!empty($chargingPoint->country)) {
            $data['country'] = $chargingPoint->country;
        }
        // House number (si la colonne existe)
        if (isset($chargingPoint->house_number) && !empty($chargingPoint->house_number)) {
            $data['house_number'] = $chargingPoint->house_number;
        }
        // Admin address (si la colonne existe)
        if (isset($chargingPoint->admin_address) && !empty($chargingPoint->admin_address)) {
            $data['admin_address'] = $chargingPoint->admin_address;
        } else {
            // Construire l'adminAddress à partir de l'adresse complète si admin_address n'est pas défini
            $adminAddressParts = [];
            if (!empty($chargingPoint->address)) {
                $adminAddressParts[] = $chargingPoint->address;
            }
            if (!empty($chargingPoint->city)) {
                $adminAddressParts[] = $chargingPoint->city;
            }
            if (!empty($chargingPoint->postal_code)) {
                $adminAddressParts[] = $chargingPoint->postal_code;
            }
            if (!empty($chargingPoint->country)) {
                $adminAddressParts[] = $chargingPoint->country;
            }
            if (!empty($adminAddressParts)) {
                // Stocker dans admin_address pour qu'il soit envoyé lors de la création
                $data['admin_address'] = implode(', ', $adminAddressParts);
            }
        }
        
        // Informations techniques (seulement si disponibles)
        if (!empty($chargingPoint->manufacturer)) {
            $data['manufacturer'] = $chargingPoint->manufacturer;
        }
        if (!empty($chargingPoint->model)) {
            $data['model'] = $chargingPoint->model;
        }
        if (!empty($chargingPoint->firmware_version)) {
            $data['firmware_version'] = $chargingPoint->firmware_version;
        }
        if (!empty($chargingPoint->ip_address)) {
            $data['ip_address'] = $chargingPoint->ip_address;
            $data['endpointAddress'] = $chargingPoint->ip_address;
        }
        if (!empty($chargingPoint->mac_address)) {
            $data['mac_address'] = $chargingPoint->mac_address;
        }
        if (!empty($chargingPoint->communication_protocol)) {
            $data['communication_protocol'] = $chargingPoint->communication_protocol;
        }
        if (!empty($chargingPoint->power_output)) {
            $data['power_output'] = $chargingPoint->power_output;
        }
        if (!empty($chargingPoint->charge_box_id)) {
            $data['charge_box_id'] = $chargingPoint->charge_box_id;
        }
        
        // Notes et description
        if (!empty($chargingPoint->notes)) {
            $data['notes'] = $chargingPoint->notes;
        }
        if (!empty($chargingPoint->description)) {
            $data['description'] = $chargingPoint->description;
        }
        if (!empty($chargingPoint->installation_notes)) {
            $data['installation_notes'] = $chargingPoint->installation_notes;
        }
        
        // Statut et accessibilité
        if (!empty($chargingPoint->status)) {
            $data['status'] = $chargingPoint->status;
        }
        if (isset($chargingPoint->public_access)) {
            $data['public_access'] = $chargingPoint->public_access;
        }
        if (!empty($chargingPoint->access_type)) {
            $data['access_type'] = $chargingPoint->access_type;
        }
        
        // Timezone
        if (!empty($chargingPoint->timezone)) {
            $data['timezone'] = $chargingPoint->timezone;
        }
        
        // External ID
        if (!empty($chargingPoint->external_id)) {
            $data['external_id'] = $chargingPoint->external_id;
        }
        if (!empty($chargingPoint->evse_id)) {
            $data['evse_id'] = $chargingPoint->evse_id;
        }

        Log::info('SteveService: Creating complete charging point from model', [
            'charging_point_id' => $chargingPoint->id,
            'steve_id' => $steveId,
            'name' => $chargingPoint->name,
            'data_fields_count' => count($data),
            'has_address' => !empty($data['address']),
            'has_city' => !empty($data['city']),
            'has_country' => !empty($data['country']),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'steve_charging_point_id' => $data['steve_charging_point_id'] ?? null,
        ]);

        // Utiliser createChargingPointOnSteve qui gère toutes les informations
        return $this->createChargingPointOnSteve($data);
    }

    /**
     * Create a charge point on Steve API.
     * According to Steve API docs: POST /api/v1/chargePoints
     * 
     * @param array $data Charge point data following Steve API structure
     * @return array Response with success status and created data or error details
     */
    public function createChargePoint(array $data): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for createChargePoint');
            return [
                'success' => false,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration de l\'API Steve manquante'
            ];
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/chargePoints';
            
            // Construire le payload selon la structure de l'API Steve
            $payload = $this->buildChargePointPayload($data);
            
            Log::info('SteveService: Creating charge point on Steve API', [
                'url' => $url,
                'chargeBoxId' => $payload['chargeBoxId'] ?? 'N/A',
                'payload_keys' => array_keys($payload)
            ]);

            $response = $this->httpClient()->post($url, $payload);

            // Selon la doc Steve API: le code 201 indique la création réussie
            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('SteveService: Charge point created successfully', [
                    'chargeBoxId' => $result['chargeBoxId'] ?? 'N/A',
                    'chargeBoxPk' => $result['chargeBoxPk'] ?? 'N/A',
                    'status' => $response->status()
                ]);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'status' => $response->status()
                ];
            }

            // Gérer les erreurs selon la doc Steve API
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? $response->body();
            
            Log::warning('Steve API createChargePoint failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'payload' => $payload
            ]);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => $response->status()
            ];

        } catch (\Throwable $e) {
            Log::error('SteveService createChargePoint error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }
    
    /**
     * Build charge point payload according to Steve API structure
     * 
     * @param array $data Input data
     * @return array Formatted payload for Steve API
     */
    protected function buildChargePointPayload(array $data): array
    {
        $payload = [
            'chargeBoxId' => $data['chargeBoxId'] ?? $data['charge_box_id'] ?? null,
            'description' => $data['description'] ?? $data['name'] ?? null,
            'note' => $data['note'] ?? $data['notes'] ?? null,
        ];
        
        // Address object structure according to Steve API
        if (!empty($data['address']) || !empty($data['city']) || !empty($data['zipCode'])) {
            $payload['address'] = [
                'street' => $data['address'] ?? $data['street'] ?? null,
                'houseNumber' => $data['houseNumber'] ?? $data['house_number'] ?? null,
                'zipCode' => $data['zipCode'] ?? $data['zip_code'] ?? $data['postal_code'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'UNDEFINED',
            ];
            
            // Supprimer les valeurs null de l'objet address
            $payload['address'] = array_filter($payload['address'], fn($v) => $v !== null);
        }
        
        // Admin address (email/username)
        if (!empty($data['adminAddress']) || !empty($data['admin_address'])) {
            $payload['adminAddress'] = $data['adminAddress'] ?? $data['admin_address'];
        }
        
        // Location coordinates
        if (isset($data['locationLatitude']) && isset($data['locationLongitude'])) {
            $payload['locationLatitude'] = (float) $data['locationLatitude'];
            $payload['locationLongitude'] = (float) $data['locationLongitude'];
        } elseif (isset($data['latitude']) && isset($data['longitude'])) {
            $payload['locationLatitude'] = (float) $data['latitude'];
            $payload['locationLongitude'] = (float) $data['longitude'];
        }
        
        // Registration status
        if (!empty($data['registrationStatus'])) {
            $payload['registrationStatus'] = $data['registrationStatus'];
        }
        
        // Insert connector status after transaction message
        if (isset($data['insertConnectorStatusAfterTransactionMsg'])) {
            $payload['insertConnectorStatusAfterTransactionMsg'] = (bool) $data['insertConnectorStatusAfterTransactionMsg'];
        }
        
        // Supprimer les valeurs null du payload principal
        return array_filter($payload, fn($v) => $v !== null);
    }

    /**
     * Send a remote command to a charge point via Steve API.
     * 
     * @param string $chargePointId The Steve charge point ID (e.g., "CP-001")
     * @param string $action The action to perform (start, stop, unlock, reset)
     * @param array $payload Additional payload data (connectorId, type, etc.)
     * @return array|null Response from Steve API or null on failure
     */
    public function sendCommand(string $chargePointId, string $action, array $payload = []): ?array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for sendCommand');
            return null;
        }

        if (empty($chargePointId)) {
            Log::warning('SteveService: chargePointId is required for sendCommand');
            return null;
        }

        try {
            // Essayer plusieurs formats d'endpoints selon la documentation Steve
            $endpoints = [
                "{$this->baseUrl}/commands/{$chargePointId}/{$action}",
                "{$this->baseUrl}/charge-points/{$chargePointId}/commands/{$action}",
                "{$this->baseUrl}/commands/{$action}",
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    Log::info("SteveService: Sending {$action} command", [
                        'endpoint' => $endpoint,
                        'chargePointId' => $chargePointId,
                        'payload' => $payload
                    ]);

                    $response = $this->httpClient()->post($endpoint, $payload);

                    if ($response->successful()) {
                        $result = $response->json();
                        Log::info("SteveService: {$action} command sent successfully", [
                            'endpoint' => $endpoint,
                            'chargePointId' => $chargePointId,
                            'response' => $result
                        ]);
                        return $result;
                    }

                    // Si c'est une erreur 404, essayer le prochain endpoint
                    if ($response->status() === 404) {
                        Log::debug("SteveService: Endpoint not found, trying next", [
                            'endpoint' => $endpoint,
                            'status' => $response->status()
                        ]);
                        continue;
                    }

                    // Pour les autres erreurs, loguer et retourner null
                    Log::warning("Steve API {$action} command failed", [
                        'endpoint' => $endpoint,
                        'chargePointId' => $chargePointId,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'payload' => $payload
                    ]);

                    // Si ce n'est pas une 404, ne pas essayer les autres endpoints
                    return null;

                } catch (\Throwable $e) {
                    Log::debug("SteveService: Error trying endpoint {$endpoint}: " . $e->getMessage());
                    continue;
                }
            }

            Log::warning("SteveService: All endpoints failed for {$action} command", [
                'chargePointId' => $chargePointId,
                'payload' => $payload
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error("SteveService sendCommand error: " . $e->getMessage(), [
                'exception' => get_class($e),
                'chargePointId' => $chargePointId,
                'action' => $action,
                'payload' => $payload,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get charging point details by steve id (or charge box id).
     * Adjust endpoint per Steve API: spec mentions "Get charge point by id" and "Get connector status".
     */
    public function getChargingPoint(string $steveId, float $timeout = 8.0): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured");
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'Steve API URL not configured'];
        }

        // Nettoyer le baseUrl
        $baseUrl = rtrim($this->baseUrl, '/');
        $baseUrl = preg_replace('#/steve/api/v1/?$#', '', $baseUrl);
        $baseUrl = preg_replace('#/api/v1/?$#', '', $baseUrl);
        $baseUrl = rtrim($baseUrl, '/');
        
        // Essayer plusieurs formats d'endpoints
        $endpoints = [
            $baseUrl . '/steve/api/v1/chargePoints/' . $steveId,
            $baseUrl . '/api/v1/chargePoints/' . $steveId,
            $baseUrl . '/chargePoints/' . $steveId,
            $baseUrl . '/steve/api/v1/charge-points/' . $steveId,
            $baseUrl . '/api/v1/charge-points/' . $steveId,
            $baseUrl . '/charge-points/' . $steveId,
        ];

        foreach ($endpoints as $url) {
            try {
                $res = $this->httpClient()->timeout($timeout)->get($url);

                if ($res->successful()) {
                    $body = $res->json();
                    Log::info("SteveService: Charging point retrieved successfully", [
                        'id' => $steveId,
                        'url' => $url,
                        'has_status' => isset($body['status']) || isset($body['availabilityStatus'])
                    ]);
                    return [
                        'ok' => true,
                        'status' => $res->status(),
                        'body' => $body,
                        'raw' => $res->body()
                    ];
                }

                if ($res->status() === 404) {
                    $body = $res->json();
                    // When Steve responds with its own "Not Found" payload (status:"404" in body),
                    // the charge point ID doesn't exist regardless of URL format — stop early.
                    if (is_array($body) && (($body['status'] ?? null) === '404' || isset($body['message']))) {
                        Log::debug("SteveService: Charge point not registered in Steve, stopping", [
                            'id' => $steveId,
                            'url' => $url,
                        ]);
                        break;
                    }
                    Log::debug("SteveService: Endpoint not found, trying next", [
                        'url' => $url,
                        'status' => $res->status()
                    ]);
                    continue;
                }
            } catch (\Throwable $e) {
                Log::debug("SteveService getChargingPoint tried {$url}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService getChargingPoint: all endpoints failed (id: {$steveId})");
        return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'All endpoints failed'];
    }

    /**
     * Get connector status for a charging point (most reliable for online/offline).
     * The spec lists "Connector controller: get connector status of a charge point".
     */
    public function getConnectorStatus(string $steveId, float $timeout = 8.0): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured");
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'Steve API URL not configured'];
        }

        // Essayer plusieurs endpoints possibles selon la documentation Steve
        $endpoints = [
            '/connectors/status/' . $steveId,
            '/charge-points/' . $steveId . '/connectors/status',
            '/charge-points/' . $steveId . '/status',
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            try {
                $res = Http::withHeaders($this->headers())->timeout($timeout)->get($url);
                if ($res->successful()) {
                    return [
                        'ok' => true,
                        'status' => $res->status(),
                        'body' => $res->json(),
                        'raw' => $res->body(),
                        'endpoint' => $endpoint
                    ];
                }

                if ($res->status() === 404) {
                    $body = $res->json();
                    // Steve returned its own "Not Found" response — the ID doesn't exist.
                    // No point trying other URL formats for the same missing resource.
                    if (is_array($body) && (($body['status'] ?? null) === '404' || isset($body['message']))) {
                        Log::debug("SteveService getConnectorStatus: charge point not registered, stopping", [
                            'id' => $steveId,
                            'url' => $url,
                        ]);
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug("SteveService getConnectorStatus tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService getConnectorStatus: all endpoints failed (id: {$steveId})");
        return ['ok' => false, 'status' => null, 'body' => null, 'error' => 'All endpoints failed'];
    }

    // Méthodes existantes pour compatibilité avec OCPP
    public function testConnection(ChargingPoint $chargingPoint): array
    {
        if ($this->ocpp) {
            $status = $this->ocpp->checkConnectionStatus($chargingPoint);
            return [
                'success' => (bool)($status['success'] ?? false),
                'data' => $status['data'] ?? null,
                'message' => $status['message'] ?? null,
            ];
        }
        // Fallback vers l'API REST si OCPP n'est pas disponible
        if ($chargingPoint->steve_charging_point_id) {
            $res = $this->getConnectorStatus($chargingPoint->steve_charging_point_id);
            return [
                'success' => $res['ok'],
                'data' => $res['body'],
                'message' => $res['ok'] ? 'Connected' : ($res['error'] ?? 'Connection failed'),
            ];
        }
        return ['success' => false, 'data' => null, 'message' => 'No Steve ID configured'];
    }

    public function start(ChargingPoint $chargingPoint, array $params = []): array
    {
        if ($this->ocpp) {
            return $this->ocpp->startCharging($chargingPoint, $params);
        }
        return ['success' => false, 'message' => 'OCPP service not available'];
    }

    public function stop(ChargingPoint $chargingPoint, array $params = []): array
    {
        if ($this->ocpp) {
            return $this->ocpp->stopCharging($chargingPoint, $params);
        }
        return ['success' => false, 'message' => 'OCPP service not available'];
    }

    public function status(ChargingPoint $chargingPoint): array
    {
        if ($this->ocpp) {
            return $this->ocpp->checkConnectionStatus($chargingPoint);
        }
        // Fallback vers l'API REST
        if ($chargingPoint->steve_charging_point_id) {
            return $this->getConnectorStatus($chargingPoint->steve_charging_point_id);
        }
        return ['ok' => false, 'message' => 'No Steve ID configured'];
    }

    /**
     * Create a charging point on Steve API
     * 
     * @param array $data Charge point data (must include: steve_charging_point_id, name, latitude, longitude)
     * @return array Response from Steve API with success status and data
     */
    public function createChargingPointOnSteve(array $data): array
    {
        if (empty($this->baseUrl)) {
            Log::error("SteveService: baseUrl not configured for createChargingPointOnSteve", [
                'config_url' => config('services.steve.url'),
                'base_url_property' => $this->baseUrl,
                'data_received' => array_keys($data),
            ]);
            return [
                'ok' => false,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante. Vérifiez SERVICES_STEVE_URL dans votre fichier .env'
            ];
        }

        // Validate required fields
        // chargePointId peut être steve_charging_point_id, chargeBoxId, ou serial_number
        $hasChargePointId = !empty($data['steve_charging_point_id']) 
                         || !empty($data['chargeBoxId']) 
                         || !empty($data['serial_number']);
        
        if (!$hasChargePointId) {
            Log::warning("SteveService: Missing chargePointId (steve_charging_point_id, chargeBoxId, or serial_number)");
            return [
                'ok' => false,
                'error' => "Missing chargePointId",
                'message' => "Champ requis manquant: chargePointId (steve_charging_point_id, chargeBoxId, ou serial_number)"
            ];
        }
        
        if (empty($data['name'])) {
            Log::warning("SteveService: Missing required field for createChargingPointOnSteve: name");
            return [
                'ok' => false,
                'error' => "Missing required field: name",
                'message' => "Champ requis manquant: name"
            ];
        }
        
        if (empty($data['latitude']) || empty($data['longitude'])) {
            Log::warning("SteveService: Missing required fields for createChargingPointOnSteve: latitude or longitude");
            return [
                'ok' => false,
                'error' => "Missing required fields: latitude or longitude",
                'message' => "Champs requis manquants: latitude ou longitude"
            ];
        }

        // Nettoyer le baseUrl pour éviter les duplications
        $baseUrl = rtrim($this->baseUrl, '/');
        $baseUrl = preg_replace('#/steve/api/v1/?$#', '', $baseUrl);
        $baseUrl = preg_replace('#/api/v1/?$#', '', $baseUrl);
        $baseUrl = rtrim($baseUrl, '/');
        
        // Essayer plusieurs formats d'endpoint
        $endpoints = [
            $baseUrl . '/steve/api/v1/chargePoints',
            $baseUrl . '/api/v1/chargePoints',
            $baseUrl . '/chargePoints',
        ];
        
        $lastError = null;
        
        foreach ($endpoints as $url) {
            try {
                // Utiliser steve_charging_point_id ou serial_number comme chargePointId
                $chargePointId = $data['steve_charging_point_id'] ?? $data['chargeBoxId'] ?? $data['serial_number'] ?? null;
                
                if (empty($chargePointId)) {
                    Log::warning("SteveService: Missing chargePointId for createChargingPointOnSteve", [
                        'data_keys' => array_keys($data)
                    ]);
                    $lastError = [
                        'message' => 'Missing chargePointId (steve_charging_point_id, chargeBoxId, or serial_number)',
                        'url' => $url
                    ];
                    continue;
                }
                
                // Construire le payload selon le format exact de l'API Steve
                // L'API Steve attend: chargeBoxId, locationLatitude, locationLongitude, address (objet), note, etc.
                // Arrondir les coordonnées à 8 décimales et les convertir en float propre
                $latitude = round((float) $data['latitude'], 8);
                $longitude = round((float) $data['longitude'], 8);
                
                // Payload minimal avec seulement les champs requis
                $payload = [
                    'chargeBoxId' => $chargePointId, // Identifiant principal requis
                    'locationLatitude' => $latitude,
                    'locationLongitude' => $longitude,
                ];
                
                // Description (optionnel)
                if (!empty($data['description'])) {
                    $payload['description'] = $data['description'];
                }
                
                // Note (l'API Steve utilise "note" et non "notes")
                if (!empty($data['notes'])) {
                    $payload['note'] = $data['notes'];
                } elseif (!empty($data['note'])) {
                    $payload['note'] = $data['note'];
                }
                
                // Admin Address - peut-être que l'API Steve ne l'accepte pas lors de la création
                // On le mettra lors de la mise à jour si nécessaire
                // Ne pas inclure adminAddress dans le payload de création pour éviter l'erreur 400
                
                // Registration Status
                if (!empty($data['registration_status'])) {
                    $payload['registrationStatus'] = $data['registration_status'];
                }
                
                // Insert Connector Status After Transaction Message
                if (isset($data['insert_connector_status_after_transaction_msg'])) {
                    $payload['insertConnectorStatusAfterTransactionMsg'] = (bool) $data['insert_connector_status_after_transaction_msg'];
                }

                // Adresse complète (format objet selon l'API Steve)
                $addressData = [];
                
                // City (Ville)
                if (!empty($data['city'])) {
                    $addressData['city'] = $data['city'];
                }
                
                // Zip Code (Code postal)
                if (!empty($data['postal_code']) || !empty($data['zip_code'])) {
                    $addressData['zipCode'] = $data['postal_code'] ?? $data['zip_code'];
                }
                
                // Country (Pays)
                if (!empty($data['country'])) {
                    $addressData['country'] = $data['country'];
                    // Essayer de déterminer le code pays alpha-2 si disponible
                    if (!empty($data['country_alpha2'])) {
                        $addressData['countryAlpha2OrNull'] = $data['country_alpha2'];
                    }
                }
                
                // Street et House Number
                $street = null;
                $houseNumber = null;
                
                // Si house_number est fourni directement
                if (!empty($data['house_number'])) {
                    $houseNumber = $data['house_number'];
                }
                
                // Parser l'adresse pour extraire street et houseNumber si possible
                if (!empty($data['address']) || !empty($data['street'])) {
                    $address = $data['street'] ?? $data['address'];
                    
                    // Essayer de détecter un numéro de maison au début de l'adresse (ex: "123 Rue Example")
                    if (preg_match('/^(\d+[a-zA-Z]?)\s+(.+)$/', trim($address), $matches)) {
                        // Numéro trouvé au début
                        if (empty($houseNumber)) {
                            $houseNumber = $matches[1];
                        }
                        $street = $matches[2];
                    } elseif (preg_match('/^(.+?)\s+(\d+[a-zA-Z]?)$/', trim($address), $matches)) {
                        // Numéro trouvé à la fin (format européen: "Rue Example 123")
                        if (empty($houseNumber)) {
                            $houseNumber = $matches[2];
                        }
                        $street = $matches[1];
                    } else {
                        // Pas de numéro détecté, utiliser toute l'adresse comme street
                        $street = $address;
                    }
                }
                
                if (!empty($street)) {
                    $addressData['street'] = $street;
                }
                if (!empty($houseNumber)) {
                    $addressData['houseNumber'] = $houseNumber;
                }
                
                // Ajouter l'objet address seulement s'il contient au moins un champ
                // IMPORTANT: L'API Steve peut exiger que l'objet address contienne au moins city et country
                // Si on n'a que street, on ne crée pas l'objet address pour éviter l'erreur 400
                if (!empty($addressData)) {
                    // Ne créer l'objet address que si on a au moins city OU country (pas seulement street)
                    // Car l'API Steve peut rejeter un objet address avec seulement street
                    if (isset($addressData['city']) || isset($addressData['country']) || isset($addressData['zipCode'])) {
                        $payload['address'] = $addressData;
                    } else {
                        // Si on n'a que street, on ne crée pas l'objet address
                        // On peut mettre street directement dans adminAddress si nécessaire
                        Log::debug('SteveService: Skipping address object - only street available, missing city/country', [
                            'address_data' => $addressData
                        ]);
                    }
                }
                
                // Note: L'API Steve peut ne pas accepter ocppProtocol lors de la création initiale
                // On l'enverra lors de la mise à jour technique après la création
                // Ne pas inclure ocppProtocol dans le payload de création pour éviter l'erreur 400

                // S'assurer que les coordonnées sont bien arrondies et formatées correctement
                // Convertir en string puis en float pour forcer un format propre
                if (isset($payload['locationLatitude'])) {
                    $payload['locationLatitude'] = (float) number_format((float) $payload['locationLatitude'], 8, '.', '');
                }
                if (isset($payload['locationLongitude'])) {
                    $payload['locationLongitude'] = (float) number_format((float) $payload['locationLongitude'], 8, '.', '');
                }
                
                Log::info("SteveService: Creating charging point on Steve API", [
                    'url' => $url,
                    'chargeBoxId' => $payload['chargeBoxId'] ?? null,
                    'locationLatitude' => $payload['locationLatitude'] ?? null,
                    'locationLongitude' => $payload['locationLongitude'] ?? null,
                    'has_address' => isset($payload['address']),
                    'has_description' => isset($payload['description']),
                    'has_note' => isset($payload['note']),
                    'has_adminAddress' => isset($payload['adminAddress']),
                    'payload_keys' => array_keys($payload),
                    'payload' => $payload, // Log complet pour debug
                ]);

                // Utiliser les options JSON pour éviter les problèmes de précision
                // JSON_PARTIAL_OUTPUT_ON_ERROR n'existe pas, on utilise juste JSON_UNESCAPED_SLASHES
                // Pour les floats, on les a déjà formatés avec number_format
                $res = $this->httpClient()
                    ->asJson()
                    ->timeout(10.0)
                    ->post($url, $payload);

                if ($res->successful() || in_array($res->status(), [200, 201, 204])) {
                    $responseJson = $res->json();
                    $responseBody = $res->body();
                    
                    // Extraire l'ID retourné par Steve si disponible
                    $steveId = null;
                    if (is_array($responseJson)) {
                        // Steve peut retourner l'ID dans différents champs (selon la documentation, chargeBoxId est retourné)
                        $steveId = $responseJson['chargeBoxId'] 
                                ?? $responseJson['chargeBoxPk'] 
                                ?? $responseJson['id'] 
                                ?? $responseJson['chargePointId']
                                ?? $responseJson['data']['chargeBoxId'] 
                                ?? $responseJson['data']['id'] ?? null;
                    }
                    
                    $responseData = [
                        'ok' => true,
                        'status' => $res->status(),
                        'body' => $responseJson,
                        'raw' => $responseBody,
                        'steve_id' => $steveId, // ID retourné par Steve (peut être différent de celui envoyé)
                    ];
                    
                    Log::info("SteveService: Charging point created successfully on Steve", [
                        'chargeBoxId' => $payload['chargeBoxId'] ?? null,
                        'steve_returned_id' => $steveId,
                        'url' => $url,
                        'response_status' => $res->status(),
                        'response_body' => $responseJson,
                        'payload_fields' => count($payload)
                    ]);
                    
                    return $responseData;
                }

                if ($res->status() === 404) {
                    Log::debug('SteveService: Endpoint not found, trying next', [
                        'url' => $url,
                        'status' => $res->status()
                    ]);
                    $lastError = [
                        'status' => $res->status(),
                        'body' => $res->body(),
                        'url' => $url
                    ];
                    continue;
                }

                $errorBody = $res->body();
                $errorJson = $res->json();
                $errorMessage = null;
                
                // Extraire le message d'erreur de la réponse
                if (is_array($errorJson)) {
                    $errorMessage = $errorJson['message'] 
                                 ?? $errorJson['error'] 
                                 ?? $errorJson['errorMessage']
                                 ?? ($errorJson['errors'] ?? null);
                }
                
                if (empty($errorMessage) && !empty($errorBody)) {
                    $errorMessage = strlen($errorBody) > 500 ? substr($errorBody, 0, 500) . '...' : $errorBody;
                }
                
                Log::error("SteveService: Failed to create charging point on Steve", [
                    'chargeBoxId' => $payload['chargeBoxId'] ?? 'unknown',
                    'status' => $res->status(),
                    'url' => $url,
                    'error_message' => $errorMessage,
                    'response_body' => $errorBody,
                    'response_json' => $errorJson,
                    'payload_sent' => $payload,
                    'payload_keys' => array_keys($payload),
                    'http_status' => $res->status(),
                    'url_tried' => $url
                ]);
                
                $lastError = [
                    'status' => $res->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'message' => $errorMessage ?? "HTTP {$res->status()}",
                    'url' => $url
                ];

            } catch (\Throwable $e) {
                Log::debug("SteveService: Error trying endpoint {$url}: " . $e->getMessage());
                $lastError = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'url' => $url
                ];
                continue;
            }
        }

        // Si tous les endpoints ont échoué
        $errorMessage = $lastError['message'] ?? 'All endpoints failed';
        $chargeBoxId = $data['steve_charging_point_id'] ?? $data['chargeBoxId'] ?? $data['serial_number'] ?? 'unknown';
        $errorDetails = [
            'chargeBoxId' => $chargeBoxId,
            'name' => $data['name'] ?? 'unknown',
            'lastError' => $lastError,
            'tried_endpoints' => count($endpoints),
            'endpoints_tried' => $endpoints,
            'base_url' => $this->baseUrl,
            'has_credentials' => !empty(config('services.steve.user')) && !empty(config('services.steve.pass')),
        ];
        
        Log::error("SteveService createChargingPointOnSteve: All endpoints failed", $errorDetails);
        
        return [
            'ok' => false,
            'status' => $lastError['status'] ?? null,
            'body' => $lastError['json'] ?? $lastError['body'] ?? null,
            'error' => $errorMessage,
            'message' => 'Erreur lors de la création sur Steve: ' . $errorMessage,
            'details' => [
                'http_status' => $lastError['status'] ?? null,
                'error_message' => $errorMessage,
                'url_tried' => $lastError['url'] ?? null,
                'response_body' => $lastError['body'] ?? null,
            ]
        ];
    }

    /**
     * Start a charging session using Steve API remoteStartTransaction command
     * 
     * @param ChargingPoint $cp The charging point to start charging on
     * @param int $connectorId The connector ID (typically 1, 2, etc.)
     * @param string $idTag The RFID tag or user identifier
     * @return array Response from Steve API with success status and data
     */
    public function startCharging(ChargingPoint $cp, int $connectorId, string $idTag): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for startCharging");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($cp->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $cp->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/remoteStartTransaction',
            '/charge-points/' . $cp->steve_charging_point_id . '/remote-start',
            '/charge-points/' . $cp->steve_charging_point_id . '/commands/remoteStartTransaction',
        ];

        $payload = [
            'chargePointId' => $cp->steve_charging_point_id,
            'connectorId' => $connectorId,
            'idTag' => $idTag,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to start charging", [
                    'url' => $url,
                    'chargePointId' => $cp->steve_charging_point_id,
                    'connectorId' => $connectorId,
                    'idTag' => $idTag
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Accepted' : 'Rejected');

                $responseData = [
                    'ok' => $res->successful() && ($status === 'Accepted' || $status === 'accepted'),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                    Log::info("SteveService: Charging started successfully", [
                        'chargePointId' => $cp->steve_charging_point_id,
                        'connectorId' => $connectorId,
                        'idTag' => $idTag,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning("SteveService: Charging start rejected by Steve", [
                            'chargePointId' => $cp->steve_charging_point_id,
                            'connectorId' => $connectorId,
                            'idTag' => $idTag,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: startCharging tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService startCharging tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService startCharging: all endpoints failed", [
            'chargePointId' => $cp->steve_charging_point_id,
            'connectorId' => $connectorId,
            'idTag' => $idTag
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec du démarrage de la recharge : tous les endpoints ont échoué'
        ];
    }

    /**
     * Stop a charging session using Steve API remoteStopTransaction command
     * 
     * @param \App\Models\Transaction $transaction The transaction to stop
     * @return array Response from Steve API with success status and data
     */
    public function stopCharging(\App\Models\Transaction $transaction): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for stopCharging");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Get charging point from transaction
        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint) {
            Log::warning("SteveService: Transaction missing charging point", [
                'transaction_id' => $transaction->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Transaction does not have an associated charging point',
                'message' => 'Transaction sans point de charge associé'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($chargingPoint->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $chargingPoint->id,
                'transaction_id' => $transaction->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Get transaction ID - try multiple possible fields
        $transactionId = $transaction->transaction_id ?? $transaction->session_id ?? (string)$transaction->id;
        
        if (empty($transactionId)) {
            Log::warning("SteveService: Transaction missing transaction_id", [
                'transaction_id' => $transaction->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Transaction does not have a transaction ID',
                'message' => 'Transaction sans identifiant de transaction'
            ];
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/remoteStopTransaction',
            '/charge-points/' . $chargingPoint->steve_charging_point_id . '/remote-stop',
            '/charge-points/' . $chargingPoint->steve_charging_point_id . '/commands/remoteStopTransaction',
        ];

        $payload = [
            'transactionId' => $transactionId,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to stop charging", [
                    'url' => $url,
                    'chargePointId' => $chargingPoint->steve_charging_point_id,
                    'transactionId' => $transactionId
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Accepted' : 'Rejected');

                $responseData = [
                    'ok' => $res->successful() && ($status === 'Accepted' || $status === 'accepted'),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                    Log::info("SteveService: Charging stopped successfully", [
                        'chargePointId' => $chargingPoint->steve_charging_point_id,
                        'transactionId' => $transactionId,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning("SteveService: Charging stop rejected by Steve", [
                            'chargePointId' => $chargingPoint->steve_charging_point_id,
                            'transactionId' => $transactionId,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: stopCharging tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService stopCharging tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService stopCharging: all endpoints failed", [
            'chargePointId' => $chargingPoint->steve_charging_point_id,
            'transactionId' => $transactionId
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec de l\'arrêt de la recharge : tous les endpoints ont échoué'
        ];
    }

    /**
     * Reset a charging point using Steve API reset command
     * 
     * @param ChargingPoint $cp The charging point to reset
     * @param string $type Reset type ('Hard' or 'Soft', defaults to 'Hard')
     * @return array Response from Steve API with success status and data
     */
    public function resetChargingPoint(ChargingPoint $cp, string $type = 'Hard'): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for resetChargingPoint");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($cp->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $cp->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Validate reset type
        if (!in_array($type, ['Hard', 'Soft'])) {
            $type = 'Hard';
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/reset',
            '/charge-points/' . $cp->steve_charging_point_id . '/reset',
            '/charge-points/' . $cp->steve_charging_point_id . '/commands/reset',
        ];

        $payload = [
            'chargePointId' => $cp->steve_charging_point_id,
            'type' => $type,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to reset charging point", [
                    'url' => $url,
                    'chargePointId' => $cp->steve_charging_point_id,
                    'type' => $type
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Accepted' : 'Rejected');

                $responseData = [
                    'ok' => $res->successful() && ($status === 'Accepted' || $status === 'accepted'),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                    Log::info("SteveService: Charging point reset successfully", [
                        'chargePointId' => $cp->steve_charging_point_id,
                        'type' => $type,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning("SteveService: Charging point reset rejected by Steve", [
                            'chargePointId' => $cp->steve_charging_point_id,
                            'type' => $type,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: resetChargingPoint tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService resetChargingPoint tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService resetChargingPoint: all endpoints failed", [
            'chargePointId' => $cp->steve_charging_point_id,
            'type' => $type
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec de la réinitialisation : tous les endpoints ont échoué'
        ];
    }

    /**
     * Unlock a connector using Steve API unlockConnector command
     * 
     * @param ChargingPoint $cp The charging point
     * @param int $connectorId The connector ID to unlock
     * @return array Response from Steve API with success status and data
     */
    public function unlockConnector(ChargingPoint $cp, int $connectorId): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for unlockConnector");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($cp->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $cp->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/unlockConnector',
            '/charge-points/' . $cp->steve_charging_point_id . '/unlock',
            '/charge-points/' . $cp->steve_charging_point_id . '/commands/unlockConnector',
        ];

        $payload = [
            'chargePointId' => $cp->steve_charging_point_id,
            'connectorId' => $connectorId,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to unlock connector", [
                    'url' => $url,
                    'chargePointId' => $cp->steve_charging_point_id,
                    'connectorId' => $connectorId
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Unlocked' : 'NotSupported');

                $responseData = [
                    'ok' => $res->successful() && in_array($status, ['Unlocked', 'unlocked', 'Accepted', 'accepted']),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && in_array($status, ['Unlocked', 'unlocked', 'Accepted', 'accepted'])) {
                    Log::info("SteveService: Connector unlocked successfully", [
                        'chargePointId' => $cp->steve_charging_point_id,
                        'connectorId' => $connectorId,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if (in_array($status, ['NotSupported', 'notSupported', 'Rejected', 'rejected'])) {
                        Log::warning("SteveService: Connector unlock rejected by Steve", [
                            'chargePointId' => $cp->steve_charging_point_id,
                            'connectorId' => $connectorId,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: unlockConnector tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService unlockConnector tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService unlockConnector: all endpoints failed", [
            'chargePointId' => $cp->steve_charging_point_id,
            'connectorId' => $connectorId
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec du déblocage : tous les endpoints ont échoué'
        ];
    }

    /**
     * Update charging point configuration using Steve API changeConfiguration command
     * 
     * @param ChargingPoint $cp The charging point to update
     * @param string $key Configuration key (e.g., 'MaxCurrent', 'HeartbeatInterval')
     * @param string $value Configuration value
     * @return array Response from Steve API with success status and data
     */
    public function updateChargingPointConfig(ChargingPoint $cp, string $key, string $value): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for updateChargingPointConfig");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($cp->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $cp->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Validate key and value
        if (empty($key) || empty($value)) {
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Key and value are required',
                'message' => 'La clé et la valeur sont requises'
            ];
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/changeConfiguration',
            '/charge-points/' . $cp->steve_charging_point_id . '/config',
            '/charge-points/' . $cp->steve_charging_point_id . '/commands/changeConfiguration',
        ];

        $payload = [
            'chargePointId' => $cp->steve_charging_point_id,
            'key' => $key,
            'value' => $value,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to update charging point configuration", [
                    'url' => $url,
                    'chargePointId' => $cp->steve_charging_point_id,
                    'key' => $key,
                    'value' => $value
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Accepted' : 'Rejected');

                $responseData = [
                    'ok' => $res->successful() && ($status === 'Accepted' || $status === 'accepted'),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                    Log::info("SteveService: Charging point configuration updated successfully", [
                        'chargePointId' => $cp->steve_charging_point_id,
                        'key' => $key,
                        'value' => $value,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning("SteveService: Configuration update rejected by Steve", [
                            'chargePointId' => $cp->steve_charging_point_id,
                            'key' => $key,
                            'value' => $value,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: updateChargingPointConfig tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService updateChargingPointConfig tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService updateChargingPointConfig: all endpoints failed", [
            'chargePointId' => $cp->steve_charging_point_id,
            'key' => $key,
            'value' => $value
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec de la mise à jour de configuration : tous les endpoints ont échoué'
        ];
    }

    /**
     * Get diagnostics from charging point using Steve API getDiagnostics command
     * 
     * @param ChargingPoint $cp The charging point to get diagnostics from
     * @param string|null $location Upload location URL (optional)
     * @return array Response from Steve API with success status and data
     */
    public function getDiagnostics(ChargingPoint $cp, ?string $location = null): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for getDiagnostics");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($cp->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $cp->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Build upload location URL if not provided
        if (empty($location)) {
            $location = config('app.url') . '/api/diagnostics/upload';
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/getDiagnostics',
            '/charge-points/' . $cp->steve_charging_point_id . '/diagnostics',
            '/charge-points/' . $cp->steve_charging_point_id . '/commands/getDiagnostics',
        ];

        $payload = [
            'chargePointId' => $cp->steve_charging_point_id,
            'location' => $location,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to get diagnostics", [
                    'url' => $url,
                    'chargePointId' => $cp->steve_charging_point_id,
                    'location' => $location
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Accepted' : 'Rejected');

                $responseData = [
                    'ok' => $res->successful() && ($status === 'Accepted' || $status === 'accepted'),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                    Log::info("SteveService: Diagnostics request sent successfully", [
                        'chargePointId' => $cp->steve_charging_point_id,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning("SteveService: Diagnostics request rejected by Steve", [
                            'chargePointId' => $cp->steve_charging_point_id,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: getDiagnostics tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService getDiagnostics tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService getDiagnostics: all endpoints failed", [
            'chargePointId' => $cp->steve_charging_point_id
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec de la récupération des diagnostics : tous les endpoints ont échoué'
        ];
    }

    /**
     * Get logs from charging point using Steve API getLog command
     * 
     * @param ChargingPoint $cp The charging point to get logs from
     * @param string $logType Log type (default: 'DiagnosticsLog')
     * @return array Response from Steve API with success status and data
     */
    public function getLogs(ChargingPoint $cp, string $logType = 'DiagnosticsLog'): array
    {
        if (empty($this->baseUrl)) {
            Log::warning("SteveService: baseUrl not configured for getLogs");
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration API Steve manquante'
            ];
        }

        // Validate that charging point has a Steve ID
        if (empty($cp->steve_charging_point_id)) {
            Log::warning("SteveService: Charging point missing steve_charging_point_id", [
                'charging_point_id' => $cp->id
            ]);
            return [
                'ok' => false,
                'status' => null,
                'body' => null,
                'error' => 'Charging point does not have a Steve ID configured',
                'message' => 'Point de charge sans identifiant Steve configuré'
            ];
        }

        // Build the API endpoint - trying common endpoints
        $endpoints = [
            '/commands/getLog',
            '/charge-points/' . $cp->steve_charging_point_id . '/logs',
            '/charge-points/' . $cp->steve_charging_point_id . '/commands/getLog',
        ];

        $payload = [
            'chargePointId' => $cp->steve_charging_point_id,
            'logType' => $logType,
        ];

        foreach ($endpoints as $endpoint) {
            $url = $this->baseUrl . $endpoint;
            
            try {
                Log::info("SteveService: Attempting to get logs", [
                    'url' => $url,
                    'chargePointId' => $cp->steve_charging_point_id,
                    'logType' => $logType
                ]);

                $res = Http::withHeaders($this->headers())
                    ->timeout(10.0)
                    ->post($url, $payload);

                $responseJson = $res->json();
                $status = $responseJson['status'] ?? ($res->successful() ? 'Accepted' : 'Rejected');

                $responseData = [
                    'ok' => $res->successful() && ($status === 'Accepted' || $status === 'accepted'),
                    'status' => $res->status(),
                    'body' => $responseJson,
                    'raw' => $res->body(),
                    'steve_status' => $status,
                    'endpoint' => $endpoint
                ];

                if ($res->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                    Log::info("SteveService: Logs request sent successfully", [
                        'chargePointId' => $cp->steve_charging_point_id,
                        'logType' => $logType,
                        'steve_status' => $status
                    ]);
                    return $responseData;
                } else {
                    // If rejected, log but don't try next endpoint (likely same response)
                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning("SteveService: Logs request rejected by Steve", [
                            'chargePointId' => $cp->steve_charging_point_id,
                            'logType' => $logType,
                            'steve_status' => $status,
                            'response' => $responseJson
                        ]);
                        return $responseData;
                    }
                    // For other errors, try next endpoint
                    Log::debug("SteveService: getLogs tried {$endpoint}: HTTP {$res->status()}");
                }

            } catch (\Throwable $e) {
                Log::debug("SteveService getLogs tried {$endpoint}: {$e->getMessage()}");
                continue;
            }
        }

        Log::warning("SteveService getLogs: all endpoints failed", [
            'chargePointId' => $cp->steve_charging_point_id,
            'logType' => $logType
        ]);

        return [
            'ok' => false,
            'status' => null,
            'body' => null,
            'error' => 'All endpoints failed',
            'message' => 'Échec de la récupération des logs : tous les endpoints ont échoué'
        ];
    }

    /**
     * Update all technical information for a charging point on Steve API after creation
     * 
     * @param \App\Models\ChargingPoint $chargingPoint The charging point model
     * @return array Response with success status
     */
    public function updateChargingPointTechnicalInfo(\App\Models\ChargingPoint $chargingPoint): array
    {
        if (empty($chargingPoint->steve_charging_point_id)) {
            Log::warning('SteveService: Cannot update technical info - missing steve_charging_point_id', [
                'charging_point_id' => $chargingPoint->id,
            ]);
            return [
                'ok' => false,
                'error' => 'Missing steve_charging_point_id',
                'message' => 'Point de charge sans identifiant Steve'
            ];
        }

        // Préparer les données techniques à mettre à jour
        $updateData = [];

        // Endpoint Address (IP address)
        if (!empty($chargingPoint->ip_address)) {
            $updateData['endpointAddress'] = $chargingPoint->ip_address;
        }

        // OCPP Protocol (communication protocol)
        if (!empty($chargingPoint->communication_protocol)) {
            // Mapper les valeurs possibles vers le format attendu par Steve (ocpp12, ocpp15, ocpp16)
            $ocppProtocol = strtolower(trim($chargingPoint->communication_protocol));
            
            // Normaliser le format
            if (strpos($ocppProtocol, 'ocpp') === 0) {
                // Format déjà correct (ocpp12, ocpp15, ocpp16)
                $updateData['ocppProtocol'] = $ocppProtocol;
            } elseif (preg_match('/(\d+\.\d+)/', $ocppProtocol, $matches)) {
                // Format 1.2, 1.5, 1.6 -> convertir en ocpp12, ocpp15, ocpp16
                $version = str_replace('.', '', $matches[1]);
                $updateData['ocppProtocol'] = 'ocpp' . $version;
            } elseif (preg_match('/(\d+)/', $ocppProtocol, $matches)) {
                // Format 12, 15, 16 -> convertir en ocpp12, ocpp15, ocpp16
                $updateData['ocppProtocol'] = 'ocpp' . $matches[1];
            } else {
                // Par défaut, utiliser tel quel
                $updateData['ocppProtocol'] = $ocppProtocol;
            }
            
            Log::info('SteveService: OCPP Protocol mapped', [
                'charging_point_id' => $chargingPoint->id,
                'original' => $chargingPoint->communication_protocol,
                'mapped' => $updateData['ocppProtocol'],
            ]);
        } else {
            Log::warning('SteveService: communication_protocol is empty', [
                'charging_point_id' => $chargingPoint->id,
            ]);
        }

        // Charge Point Vendor (manufacturer)
        if (!empty($chargingPoint->manufacturer)) {
            $updateData['chargePointVendor'] = $chargingPoint->manufacturer;
        } else {
            Log::warning('SteveService: manufacturer is empty', [
                'charging_point_id' => $chargingPoint->id,
            ]);
        }

        // Charge Point Model
        if (!empty($chargingPoint->model)) {
            $updateData['chargePointModel'] = $chargingPoint->model;
            Log::info('SteveService: Charge Point Model found', [
                'charging_point_id' => $chargingPoint->id,
                'model' => $chargingPoint->model,
            ]);
        } else {
            Log::warning('SteveService: model is empty', [
                'charging_point_id' => $chargingPoint->id,
            ]);
        }

        // Charge Point Serial Number
        if (!empty($chargingPoint->serial_number)) {
            $updateData['chargePointSerialNumber'] = $chargingPoint->serial_number;
        }

        // Charge Box Serial Number (charge_box_id ou serial_number)
        if (!empty($chargingPoint->charge_box_id)) {
            $updateData['chargeBoxSerialNumber'] = $chargingPoint->charge_box_id;
        } elseif (!empty($chargingPoint->serial_number)) {
            $updateData['chargeBoxSerialNumber'] = $chargingPoint->serial_number;
        }

        // Firmware Version
        if (!empty($chargingPoint->firmware_version)) {
            $updateData['firmwareVersion'] = $chargingPoint->firmware_version;
            Log::info('SteveService: Firmware Version found', [
                'charging_point_id' => $chargingPoint->id,
                'firmware_version' => $chargingPoint->firmware_version,
            ]);
        } else {
            Log::warning('SteveService: firmware_version is empty', [
                'charging_point_id' => $chargingPoint->id,
            ]);
        }

        // Description
        if (!empty($chargingPoint->description)) {
            $updateData['description'] = $chargingPoint->description;
        }

        // Admin Address (peut être l'adresse complète ou une adresse spécifique)
        if (!empty($chargingPoint->admin_address)) {
            $updateData['adminAddress'] = $chargingPoint->admin_address;
        } elseif (!empty($chargingPoint->address)) {
            // Utiliser l'adresse principale comme admin address si admin_address n'est pas défini
            $adminAddressParts = [];
            if (!empty($chargingPoint->address)) {
                $adminAddressParts[] = $chargingPoint->address;
            }
            if (!empty($chargingPoint->city)) {
                $adminAddressParts[] = $chargingPoint->city;
            }
            if (!empty($chargingPoint->postal_code)) {
                $adminAddressParts[] = $chargingPoint->postal_code;
            }
            if (!empty($chargingPoint->country)) {
                $adminAddressParts[] = $chargingPoint->country;
            }
            if (!empty($adminAddressParts)) {
                $updateData['adminAddress'] = implode(', ', $adminAddressParts);
            }
        }

        // Mettre à jour l'adresse complète (objet address)
        $addressData = [];
        if (!empty($chargingPoint->city)) {
            $addressData['city'] = $chargingPoint->city;
        }
        if (!empty($chargingPoint->postal_code)) {
            $addressData['zipCode'] = $chargingPoint->postal_code;
        }
        if (!empty($chargingPoint->country)) {
            $addressData['country'] = $chargingPoint->country;
            // Essayer de déterminer le code pays alpha-2 si disponible
            // TODO: Implémenter une logique pour convertir le nom du pays en code alpha-2
        }
        // Street et House Number
        $street = null;
        $houseNumber = null;
        
        // Si house_number est stocké séparément (si la colonne existe)
        if (isset($chargingPoint->house_number) && !empty($chargingPoint->house_number)) {
            $houseNumber = $chargingPoint->house_number;
        }
        
        // Parser l'adresse pour extraire street et houseNumber si possible
        if (!empty($chargingPoint->address)) {
            $address = $chargingPoint->address;
            
            // Essayer de détecter un numéro de maison au début de l'adresse (ex: "123 Rue Example")
            if (preg_match('/^(\d+[a-zA-Z]?)\s+(.+)$/', trim($address), $matches)) {
                // Numéro trouvé au début
                if (empty($houseNumber)) {
                    $houseNumber = $matches[1];
                }
                $street = $matches[2];
            } elseif (preg_match('/^(.+?)\s+(\d+[a-zA-Z]?)$/', trim($address), $matches)) {
                // Numéro trouvé à la fin (format européen: "Rue Example 123")
                if (empty($houseNumber)) {
                    $houseNumber = $matches[2];
                }
                $street = $matches[1];
            } else {
                // Pas de numéro détecté, utiliser toute l'adresse comme street
                $street = $address;
            }
        }
        
        if (!empty($street)) {
            $addressData['street'] = $street;
        }
        if (!empty($houseNumber)) {
            $addressData['houseNumber'] = $houseNumber;
        }

        // Ajouter l'objet address seulement s'il contient au moins un champ
        if (!empty($addressData)) {
            $updateData['address'] = $addressData;
        }

        // Si aucune donnée à mettre à jour, retourner succès
        if (empty($updateData)) {
            Log::info('SteveService: No technical info to update', [
                'charging_point_id' => $chargingPoint->id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
            ]);
            return [
                'ok' => true,
                'message' => 'No technical info to update'
            ];
        }

        // Log des données à mettre à jour
        Log::info('SteveService: Updating technical info on Steve API', [
            'charging_point_id' => $chargingPoint->id,
            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
            'update_data_keys' => array_keys($updateData),
            'update_data' => $updateData,
        ]);

        // Mettre à jour via l'API Steve
        $result = $this->updateChargePoint($chargingPoint->steve_charging_point_id, $updateData);

        if ($result) {
            Log::info('SteveService: Technical info updated successfully', [
                'charging_point_id' => $chargingPoint->id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                'updated_fields' => array_keys($updateData),
            ]);
            return [
                'ok' => true,
                'updated_fields' => array_keys($updateData),
                'response' => $result
            ];
        } else {
            Log::warning('SteveService: Failed to update technical info', [
                'charging_point_id' => $chargingPoint->id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                'update_data' => $updateData,
            ]);
            return [
                'ok' => false,
                'error' => 'Update failed',
                'message' => 'Échec de la mise à jour des informations techniques'
            ];
        }
    }

    /**
     * Update a charge point on Steve API.
     * 
     * @param int|string $id The charge point ID
     * @param array $data Charge point data to update (endpointAddress, notes, etc.)
     * @return array|null Response from Steve API or null on failure
     */
    /**
     * Update a charge point on Steve API.
     * According to Steve API docs: PUT /api/v1/chargePoints/{chargePointPk}
     * 
     * @param int|string $chargePointPk The charge point primary key
     * @param array $data Charge point data to update
     * @return array Response with success status and updated data or error details
     */
    public function updateChargePoint($chargePointPk, array $data): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for updateChargePoint');
            return [
                'success' => false,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration de l\'API Steve manquante'
            ];
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/chargePoints/' . $chargePointPk;
            
            // Construire le payload selon la structure de l'API Steve
            $payload = $this->buildChargePointPayload($data);
            
            Log::info('SteveService: Updating charge point on Steve API', [
                'chargePointPk' => $chargePointPk,
                'url' => $url,
                'payload_keys' => array_keys($payload)
            ]);

            $response = $this->httpClient()->put($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('SteveService: Charge point updated successfully', [
                    'chargePointPk' => $chargePointPk,
                    'chargeBoxId' => $result['chargeBoxId'] ?? 'N/A',
                    'status' => $response->status()
                ]);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'status' => $response->status()
                ];
            }

            // Gérer les erreurs selon la doc Steve API
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? $response->body();
            
            Log::warning('Steve API updateChargePoint failed', [
                'chargePointPk' => $chargePointPk,
                'status' => $response->status(),
                'error' => $errorMessage
            ]);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => $response->status()
            ];

        } catch (\Throwable $e) {
            Log::error('SteveService updateChargePoint error: ' . $e->getMessage(), [
                'chargePointPk' => $chargePointPk,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }

    /**
     * Delete a charge point from Steve API.
     * According to Steve API docs: DELETE /api/v1/chargePoints/{chargePointPk}
     * WARNING: This is a destructive operation - deletes all related data
     * 
     * @param int|string $chargePointPk The charge point primary key
     * @return array Response with success status and message or error details
     */
    public function deleteChargePoint($chargePointPk): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for deleteChargePoint');
            return [
                'success' => false,
                'error' => 'Steve API URL not configured',
                'message' => 'Configuration de l\'API Steve manquante'
            ];
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/chargePoints/' . $chargePointPk;
            
            Log::info('SteveService: Deleting charge point from Steve API', [
                'chargePointPk' => $chargePointPk,
                'url' => $url
            ]);

            $response = $this->httpClient()->delete($url);

            if ($response->successful()) {
                Log::info('SteveService: Charge point deleted successfully', [
                    'chargePointPk' => $chargePointPk,
                    'status' => $response->status()
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Charge point deleted successfully',
                    'status' => $response->status()
                ];
            }

            // Gérer les erreurs selon la doc Steve API
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? $response->body();
            
            Log::warning('Steve API deleteChargePoint failed', [
                'chargePointPk' => $chargePointPk,
                'status' => $response->status(),
                'error' => $errorMessage
            ]);
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'status' => $response->status()
            ];

        } catch (\Throwable $e) {
            Log::error('SteveService deleteChargePoint error: ' . $e->getMessage(), [
                'chargePointPk' => $chargePointPk,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }
    
    /**
     * Check if a charge point is online (connected to Steve OCPP server)
     * 
     * @param string $chargeBoxId The charge box identifier
     * @return bool True if online (heartbeat < 5 minutes), false otherwise
     */
    public function isChargePointOnline(string $chargeBoxId): bool
    {
        try {
            $chargePoints = $this->getChargePoints(['chargeBoxId' => $chargeBoxId]);
            
            if (empty($chargePoints)) {
                Log::info('SteveService: Charge point not found', ['chargeBoxId' => $chargeBoxId]);
                return false;
            }
            
            $chargePoint = $chargePoints[0];
            $lastHeartbeat = $chargePoint['lastHeartbeatTimestamp'] ?? null;
            
            if (!$lastHeartbeat) {
                Log::info('SteveService: No heartbeat recorded', ['chargeBoxId' => $chargeBoxId]);
                return false;
            }
            
            // Considérer en ligne si heartbeat < 5 minutes
            $heartbeatDate = \Carbon\Carbon::parse($lastHeartbeat);
            $isOnline = $heartbeatDate->gt(now()->subMinutes(5));
            
            Log::info('SteveService: Charge point online check', [
                'chargeBoxId' => $chargeBoxId,
                'lastHeartbeat' => $lastHeartbeat,
                'isOnline' => $isOnline,
                'minutesAgo' => $heartbeatDate->diffInMinutes(now())
            ]);
            
            return $isOnline;
            
        } catch (\Throwable $e) {
            Log::error('SteveService: Error checking if charge point is online', [
                'chargeBoxId' => $chargeBoxId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remote start a charging transaction.
     *
     * Slice B.4: delegates to {@see \App\Services\OcppOperationsService::remoteStart()}
     * — the canonical layer that owns the SteVe HTTP call (canonical
     * `/api/v1/ocpp/remote-start` body shape from Slice A), the per-CB cache
     * lock, the ChargingSession state promotion, and the ChargePointCommand
     * audit row. The 200-line URL-normalisation, multi-endpoint fallback, and
     * elaborate French error catalogue this method used to carry are gone —
     * the canonical layer is the one place those concerns live now.
     *
     * Response shape preserved for existing callers (ChargingSessionManager,
     * SteveChargingPointController) which read `success`, `data.transaction.id`,
     * `error`, and `message`.
     *
     * @return array{success: bool, data?: array, error?: string, message: string, status?: int, code?: string}
     */
    public function remoteStart(string $chargeBoxId, int $connectorId, string $ocppTag): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for remoteStart');
            return [
                'success' => false,
                'error'   => 'Steve API URL not configured',
                'message' => 'Configuration de l\'API Steve manquante',
            ];
        }

        // Best-effort online pre-check kept for diagnostic logging only — it
        // never blocks the dispatch because the canonical OcppOperationsService
        // can handle offline chargers, and the SteVe getChargePoints filter is
        // known to produce false negatives for some deployments.
        $isOnline = $this->isChargePointOnline($chargeBoxId);
        Log::info('SteveService: remoteStart pre-check', [
            'chargeBoxId' => $chargeBoxId,
            'is_online'   => $isOnline,
        ]);

        try {
            $dto = new RemoteStartRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'ocppTag'     => $ocppTag,
            ]);

            /** @var \App\Services\OcppOperationsService $ocppOps */
            $ocppOps = app(\App\Services\OcppOperationsService::class);
            $response = $ocppOps->remoteStart($dto, [
                'source' => 'SteveService::remoteStart',
            ]);

            $isAccepted = $response->isAccepted();

            return [
                'success'  => $isAccepted,
                'data'     => $response->toApiResponse()['data'] ?? [
                    'status'      => $response->status?->value,
                    'message'     => $response->message,
                    'transaction' => $response->transaction,
                ],
                'status'   => $isAccepted ? 200 : 400,
                'endpoint' => '/api/v1/ocpp/remote-start',
                'message'  => $isAccepted
                    ? "✅ Commande Remote Start envoyée avec succès ! Statut: " . ($response->status?->value ?? 'ACCEPTED')
                    : "⚠️ La borne a rejeté la commande. Statut: " . ($response->status?->value ?? 'REJECTED') . ' — ' . $response->message,
            ];
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Configuration SteVe manquante: ' . $e->getMessage(),
                'status'  => 503,
                'code'    => 'steve_configuration_missing',
            ];
        } catch (\Throwable $e) {
            Log::warning('SteveService remoteStart delegate threw', [
                'chargeBoxId' => $chargeBoxId,
                'error'       => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => "❌ Impossible d'envoyer la commande Remote Start: " . $e->getMessage(),
                'status'  => 500,
            ];
        }
    }
    
    /**
     * Remote stop a charging transaction.
     *
     * Slice B.4: delegates to {@see \App\Services\OcppOperationsService::remoteStop()}.
     * Legacy signature (chargeBoxId only) preserved — the canonical layer's
     * transactionId is best-effort resolved from the local charging_sessions
     * row so the state machine can flip the session to STOPPED when SteVe
     * accepts. When no local session is found we still dispatch with txn=0;
     * SteVe resolves the active transaction server-side from chargeBoxId.
     *
     * @return array{success: bool, data?: array, error?: string, message?: string, status?: int}
     */
    public function remoteStop(string $chargeBoxId): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('SteveService: baseUrl not configured for remoteStop');
            return [
                'success' => false,
                'error'   => 'Steve API URL not configured',
                'message' => 'Configuration de l\'API Steve manquante',
            ];
        }

        try {
            $transactionId = $this->resolveActiveTransactionIdForStop($chargeBoxId);

            $dto = new RemoteStopRequestDTO([
                'chargeBoxId'   => $chargeBoxId,
                'transactionId' => $transactionId,
            ]);

            /** @var \App\Services\OcppOperationsService $ocppOps */
            $ocppOps = app(\App\Services\OcppOperationsService::class);
            $response = $ocppOps->remoteStop($dto, [
                'source' => 'SteveService::remoteStop',
            ]);

            $isAccepted = $response->isAccepted();

            return [
                'success' => $isAccepted,
                'data'    => $response->toApiResponse()['data'] ?? [
                    'status'        => $response->status?->value,
                    'message'       => $response->message,
                    'transactionId' => $response->transactionId,
                ],
                'status'  => $isAccepted ? 200 : 400,
                'message' => $isAccepted
                    ? 'Commande Remote Stop envoyée avec succès. Statut: ' . ($response->status?->value ?? 'ACCEPTED')
                    : 'Échec Remote Stop: ' . $response->message,
                'error'   => $isAccepted ? null : $response->message,
            ];
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Configuration SteVe manquante: ' . $e->getMessage(),
                'status'  => 503,
            ];
        } catch (\Throwable $e) {
            Log::warning('SteveService remoteStop delegate threw', [
                'chargeBoxId' => $chargeBoxId,
                'error'       => $e->getMessage(),
            ]);
            return [
                'success'   => false,
                'error'     => $e->getMessage(),
                'message'   => $e->getMessage(),
                'exception' => get_class($e),
                'status'    => 500,
            ];
        }
    }

    /**
     * Best-effort lookup of the active OCPP transaction for a chargeBoxId so
     * the canonical layer can pair the stop dispatch to the right local row.
     * Returns 0 (which downstream treats as "let SteVe pick") when nothing
     * usable is found — keeps legacy fire-and-forget semantics intact.
     */
    private function resolveActiveTransactionIdForStop(string $chargeBoxId): int
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('charging_sessions')
                || !\Illuminate\Support\Facades\Schema::hasTable('charging_points')) {
                return 0;
            }

            $cp = ChargingPoint::query()
                ->where('charge_box_id', $chargeBoxId)
                ->orWhere('steve_charging_point_id', $chargeBoxId)
                ->first();
            if ($cp === null) {
                return 0;
            }

            $session = \App\Models\ChargingSession::query()
                ->where('charging_point_id', $cp->id)
                ->whereIn('status', [
                    \App\Models\ChargingSession::STATUS_ACTIVE,
                    \App\Models\ChargingSession::STATUS_IN_PROGRESS,
                    \App\Models\ChargingSession::STATUS_INITIATING,
                ])
                ->whereNotNull('steve_transaction_id')
                ->orderByDesc('id')
                ->first();

            return $session !== null ? (int) $session->steve_transaction_id : 0;
        } catch (\Throwable $e) {
            Log::debug('SteveService: resolveActiveTransactionIdForStop failed', [
                'chargeBoxId' => $chargeBoxId,
                'error'       => $e->getMessage(),
            ]);
            return 0;
        }
    }
}

