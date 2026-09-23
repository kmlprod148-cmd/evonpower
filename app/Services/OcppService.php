<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Connector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Service de communication directe avec les bornes OCPP (sans SteVe)
 * 
 * Utilisez ce service uniquement pour les bornes connectées directement.
 * Pour les bornes via SteVe, utilisez SteVeHttpClientService.
 */
class OcppService
{
    protected $baseUrl;
    protected $timeout = 30;

    public function __construct()
    {
        $this->baseUrl = config('ocpp.base_url', 'http://localhost:8080');
    }

    /**
     * Envoyer une commande StartTransaction à la borne
     */
    public function startTransaction(ChargingPoint $chargingPoint, Connector $connector, array $data): array
    {
        try {
            $payload = [
                'connectorId' => $connector->connector_id,
                'idTag' => $data['id_tag'] ?? 'anonymous',
                'meterStart' => $connector->meter_value ?? 0,
                'reservationId' => $data['reservation_id'] ?? null,
                'timestamp' => now()->toISOString(),
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'StartTransaction', $payload);

            if ($response['status'] === 'Accepted') {
                Log::info('OCPP StartTransaction successful', [
                    'charging_point_id' => $chargingPoint->id,
                    'connector_id' => $connector->connector_id,
                    'transaction_id' => $response['transactionId'] ?? null
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $response['transactionId'] ?? null,
                    'idTagInfo' => $response['idTagInfo'] ?? null
                ];
            } else {
                throw new \Exception('OCPP StartTransaction failed: ' . ($response['status'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('OCPP StartTransaction error', [
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connector->connector_id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer une commande StopTransaction à la borne
     */
    public function stopTransaction(ChargingPoint $chargingPoint, Connector $connector, array $data): array
    {
        try {
            $payload = [
                'transactionId' => $data['transaction_id'],
                'idTag' => $data['id_tag'] ?? 'anonymous',
                'meterStop' => $connector->meter_value ?? 0,
                'timestamp' => now()->toISOString(),
                'reason' => $data['reason'] ?? 'Remote'
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'StopTransaction', $payload);

            if ($response['status'] === 'Accepted') {
                Log::info('OCPP StopTransaction successful', [
                    'charging_point_id' => $chargingPoint->id,
                    'connector_id' => $connector->connector_id,
                    'transaction_id' => $data['transaction_id']
                ]);

                return [
                    'success' => true,
                    'idTagInfo' => $response['idTagInfo'] ?? null
                ];
            } else {
                throw new \Exception('OCPP StopTransaction failed: ' . ($response['status'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('OCPP StopTransaction error', [
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connector->connector_id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer une commande RemoteStartTransaction à la borne
     */
    public function remoteStartTransaction(ChargingPoint $chargingPoint, Connector $connector, array $data): array
    {
        try {
            $payload = [
                'connectorId' => $connector->connector_id,
                'idTag' => $data['id_tag'] ?? 'anonymous',
                'chargingProfile' => $data['charging_profile'] ?? null
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'RemoteStartTransaction', $payload);

            if ($response['status'] === 'Accepted') {
                Log::info('OCPP RemoteStartTransaction successful', [
                    'charging_point_id' => $chargingPoint->id,
                    'connector_id' => $connector->connector_id
                ]);

                return [
                    'success' => true,
                    'status' => $response['status']
                ];
            } else {
                throw new \Exception('OCPP RemoteStartTransaction failed: ' . ($response['status'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('OCPP RemoteStartTransaction error', [
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connector->connector_id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer une commande RemoteStopTransaction à la borne
     */
    public function remoteStopTransaction(ChargingPoint $chargingPoint, array $data): array
    {
        try {
            $payload = [
                'transactionId' => $data['transaction_id']
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'RemoteStopTransaction', $payload);

            if ($response['status'] === 'Accepted') {
                Log::info('OCPP RemoteStopTransaction successful', [
                    'charging_point_id' => $chargingPoint->id,
                    'transaction_id' => $data['transaction_id']
                ]);

                return [
                    'success' => true,
                    'status' => $response['status']
                ];
            } else {
                throw new \Exception('OCPP RemoteStopTransaction failed: ' . ($response['status'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('OCPP RemoteStopTransaction error', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Obtenir le statut d'un connecteur
     */
    public function getConnectorStatus(ChargingPoint $chargingPoint, Connector $connector): array
    {
        try {
            $payload = [
                'connectorId' => $connector->connector_id
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'GetConnectorStatus', $payload);

            return [
                'success' => true,
                'status' => $response['status'] ?? 'Unknown',
                'errorCode' => $response['errorCode'] ?? null,
                'info' => $response['info'] ?? null,
                'timestamp' => $response['timestamp'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('OCPP GetConnectorStatus error', [
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connector->connector_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer une commande Reset à la borne
     */
    public function reset(ChargingPoint $chargingPoint, string $type = 'Soft'): array
    {
        try {
            $payload = [
                'type' => $type // Soft, Hard
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'Reset', $payload);

            if ($response['status'] === 'Accepted') {
                Log::info('OCPP Reset successful', [
                    'charging_point_id' => $chargingPoint->id,
                    'type' => $type
                ]);

                return [
                    'success' => true,
                    'status' => $response['status']
                ];
            } else {
                throw new \Exception('OCPP Reset failed: ' . ($response['status'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('OCPP Reset error', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer une commande ChangeAvailability à la borne
     */
    public function changeAvailability(ChargingPoint $chargingPoint, Connector $connector, string $type): array
    {
        try {
            $payload = [
                'connectorId' => $connector->connector_id,
                'type' => $type // Operative, Inoperative
            ];

            $response = $this->sendOCPPRequest($chargingPoint, 'ChangeAvailability', $payload);

            if ($response['status'] === 'Accepted') {
                Log::info('OCPP ChangeAvailability successful', [
                    'charging_point_id' => $chargingPoint->id,
                    'connector_id' => $connector->connector_id,
                    'type' => $type
                ]);

                return [
                    'success' => true,
                    'status' => $response['status']
                ];
            } else {
                throw new \Exception('OCPP ChangeAvailability failed: ' . ($response['status'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('OCPP ChangeAvailability error', [
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $connector->connector_id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Envoyer une requête OCPP générique
     */
    protected function sendOCPPRequest(ChargingPoint $chargingPoint, string $action, array $payload): array
    {
        $url = $this->getOCPPEndpoint($chargingPoint);
        
        if (!$url) {
            throw new \Exception('OCPP endpoint not configured for charging point');
        }

        $requestData = [
            'messageTypeId' => 2, // Call
            'messageId' => $this->generateMessageId(),
            'action' => $action,
            'payload' => $payload
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->getAuthorizationHeader($chargingPoint)
                ])
                ->post($url, $requestData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Vérifier si c'est une réponse de succès
                if (isset($responseData['messageTypeId']) && $responseData['messageTypeId'] === 3) {
                    return $responseData['payload'] ?? [];
                } else {
                    throw new \Exception('Invalid OCPP response format');
                }
            } else {
                throw new \Exception('HTTP request failed: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('OCPP request failed', [
                'charging_point_id' => $chargingPoint->id,
                'action' => $action,
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Obtenir l'endpoint OCPP pour une borne
     */
    protected function getOCPPEndpoint(ChargingPoint $chargingPoint): ?string
    {
        // Vérifier d'abord la configuration de la borne
        if ($chargingPoint->ip_address) {
            $protocol = $chargingPoint->communication_protocol ?? 'ocpp16';
            $port = $this->getOCPPPort($protocol);
            
            return "http://{$chargingPoint->ip_address}:{$port}/ocpp";
        }

        // Fallback vers la configuration globale
        return config('ocpp.endpoints.' . ($chargingPoint->id ?? 'default'));
    }

    /**
     * Obtenir le port OCPP selon le protocole
     */
    protected function getOCPPPort(string $protocol): int
    {
        return match($protocol) {
            'ocpp16' => 8080,
            'ocpp20' => 8081,
            default => 8080
        };
    }

    /**
     * Générer un ID de message unique
     */
    protected function generateMessageId(): string
    {
        return 'msg_' . time() . '_' . uniqid();
    }

    /**
     * Obtenir l'en-tête d'autorisation
     */
    protected function getAuthorizationHeader(ChargingPoint $chargingPoint): ?string
    {
        // Si la borne a des identifiants configurés
        if ($chargingPoint->access_code) {
            return 'Basic ' . base64_encode("admin:{$chargingPoint->access_code}");
        }

        // Fallback vers la configuration globale
        $username = config('ocpp.auth.username');
        $password = config('ocpp.auth.password');
        
        if ($username && $password) {
            return 'Basic ' . base64_encode("{$username}:{$password}");
        }

        return null;
    }

    /**
     * Vérifier la connectivité d'une borne
     */
    public function checkConnectivity(ChargingPoint $chargingPoint): array
    {
        try {
            $response = $this->sendOCPPRequest($chargingPoint, 'Heartbeat', []);
            
            return [
                'success' => true,
                'connected' => true,
                'timestamp' => $response['timestamp'] ?? now()->toISOString()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir les informations de configuration d'une borne
     */
    public function getConfiguration(ChargingPoint $chargingPoint): array
    {
        try {
            $response = $this->sendOCPPRequest($chargingPoint, 'GetConfiguration', [
                'key' => [] // Toutes les clés
            ]);

            return [
                'success' => true,
                'configuration' => $response['configurationKey'] ?? []
            ];
        } catch (\Exception $e) {
            Log::error('OCPP GetConfiguration error', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 