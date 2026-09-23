<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OCPPCommandService
{
    protected $steveServerUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->steveServerUrl = config('services.ocpp.steve_server_url', 'http://localhost:8080');
        $this->apiKey = config('services.ocpp.api_key');
    }

    /**
     * Déclencher une recharge
     */
    public function startCharging(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'connectorId' => $params['connector_id'] ?? 1,
                'idTag' => $params['id_tag'] ?? 'admin',
                'meterStart' => $params['meter_start'] ?? 0,
                'reservationId' => $params['reservation_id'] ?? null,
                'timestamp' => Carbon::now()->toISOString(),
            ];

            $response = $this->sendOCPPCommand('StartTransaction', $payload);

            if ($response['success']) {
                // Créer une session de recharge
                $session = ChargingSession::create([
                    'charging_point_id' => $chargingPoint->id,
                    'user_id' => $params['user_id'] ?? null,
                    'session_id' => $response['data']['transactionId'] ?? null,
                    'status' => 'active',
                    'start_time' => Carbon::now(),
                    'meter_start' => $params['meter_start'] ?? 0,
                    'connector_id' => $params['connector_id'] ?? 1,
                ]);

                Log::info('Recharge démarrée avec succès', [
                    'charging_point_id' => $chargingPoint->id,
                    'session_id' => $session->id,
                    'response' => $response
                ]);

                return [
                    'success' => true,
                    'session_id' => $session->id,
                    'transaction_id' => $response['data']['transactionId'] ?? null,
                    'message' => 'Recharge démarrée avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Erreur lors du démarrage de la recharge'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du démarrage de la recharge', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors du démarrage de la recharge'
            ];
        }
    }

    /**
     * Arrêter une recharge
     */
    public function stopCharging(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $session = ChargingSession::where('charging_point_id', $chargingPoint->id)
                ->where('status', 'active')
                ->first();

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Aucune session de recharge active trouvée'
                ];
            }

            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'connectorId' => $params['connector_id'] ?? $session->connector_id,
                'transactionId' => $session->session_id,
                'idTag' => $params['id_tag'] ?? 'admin',
                'meterStop' => $params['meter_stop'] ?? 0,
                'timestamp' => Carbon::now()->toISOString(),
                'reason' => $params['reason'] ?? 'Remote'
            ];

            $response = $this->sendOCPPCommand('StopTransaction', $payload);

            if ($response['success']) {
                // Mettre à jour la session
                $session->update([
                    'status' => 'completed',
                    'end_time' => Carbon::now(),
                    'meter_stop' => $params['meter_stop'] ?? 0,
                    'energy_delivered' => ($params['meter_stop'] ?? 0) - $session->meter_start,
                    'duration' => Carbon::now()->diffInMinutes($session->start_time),
                ]);

                Log::info('Recharge arrêtée avec succès', [
                    'charging_point_id' => $chargingPoint->id,
                    'session_id' => $session->id,
                    'response' => $response
                ]);

                return [
                    'success' => true,
                    'session_id' => $session->id,
                    'message' => 'Recharge arrêtée avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Erreur lors de l\'arrêt de la recharge'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'arrêt de la recharge', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de l\'arrêt de la recharge'
            ];
        }
    }

    /**
     * Déblocage de connecteur
     */
    public function unlockConnector(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'connectorId' => $params['connector_id'] ?? 1,
            ];

            $response = $this->sendOCPPCommand('UnlockConnector', $payload);

            Log::info('Déblocage de connecteur', [
                'charging_point_id' => $chargingPoint->id,
                'connector_id' => $params['connector_id'] ?? 1,
                'response' => $response
            ]);

            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Connecteur débloqué avec succès' : ($response['message'] ?? 'Erreur lors du déblocage')
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du déblocage du connecteur', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors du déblocage du connecteur'
            ];
        }
    }

    /**
     * Réinitialisation de la borne
     */
    public function resetChargingPoint(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'type' => $params['type'] ?? 'Hard', // Hard, Soft
            ];

            $response = $this->sendOCPPCommand('Reset', $payload);

            Log::info('Réinitialisation de la borne', [
                'charging_point_id' => $chargingPoint->id,
                'type' => $params['type'] ?? 'Hard',
                'response' => $response
            ]);

            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Borne réinitialisée avec succès' : ($response['message'] ?? 'Erreur lors de la réinitialisation')
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation de la borne', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la réinitialisation'
            ];
        }
    }

    /**
     * Mise à jour des paramètres
     */
    public function updateConfiguration(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'key' => $params['key'],
                'value' => $params['value'],
            ];

            $response = $this->sendOCPPCommand('ChangeConfiguration', $payload);

            Log::info('Mise à jour de configuration', [
                'charging_point_id' => $chargingPoint->id,
                'key' => $params['key'],
                'value' => $params['value'],
                'response' => $response
            ]);

            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Configuration mise à jour avec succès' : ($response['message'] ?? 'Erreur lors de la mise à jour')
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de configuration', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la mise à jour de configuration'
            ];
        }
    }

    /**
     * Diagnostic de la borne
     */
    public function getDiagnostics(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'location' => $params['location'] ?? 'https://example.com/diagnostics',
                'retries' => $params['retries'] ?? 3,
                'retryInterval' => $params['retry_interval'] ?? 180,
                'startTime' => $params['start_time'] ?? Carbon::now()->toISOString(),
                'stopTime' => $params['stop_time'] ?? Carbon::now()->addHours(1)->toISOString(),
            ];

            $response = $this->sendOCPPCommand('GetDiagnostics', $payload);

            Log::info('Diagnostic demandé', [
                'charging_point_id' => $chargingPoint->id,
                'response' => $response
            ]);

            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Diagnostic initié avec succès' : ($response['message'] ?? 'Erreur lors du diagnostic'),
                'file_name' => $response['data']['fileName'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du diagnostic', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors du diagnostic'
            ];
        }
    }

    /**
     * Récupération des logs
     */
    public function getLogs(ChargingPoint $chargingPoint, array $params = [])
    {
        try {
            $payload = [
                'chargeBoxId' => $chargingPoint->charge_box_id,
                'logType' => $params['log_type'] ?? 'DiagnosticsLog',
                'requestId' => $params['request_id'] ?? uniqid(),
                'retries' => $params['retries'] ?? 3,
                'retryInterval' => $params['retry_interval'] ?? 180,
            ];

            $response = $this->sendOCPPCommand('GetLog', $payload);

            Log::info('Récupération des logs demandée', [
                'charging_point_id' => $chargingPoint->id,
                'log_type' => $params['log_type'] ?? 'DiagnosticsLog',
                'response' => $response
            ]);

            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'Récupération des logs initiée' : ($response['message'] ?? 'Erreur lors de la récupération des logs'),
                'request_id' => $params['request_id'] ?? uniqid()
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des logs', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la récupération des logs'
            ];
        }
    }

    /**
     * Envoi d'une commande OCPP au serveur Steve
     */
    protected function sendOCPPCommand(string $command, array $payload)
    {
        try {
            $url = $this->steveServerUrl . '/api/v1/ocpp/' . $command;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            Log::error('Erreur réponse OCPP', [
                'command' => $command,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de communication avec le serveur OCPP',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi de commande OCPP', [
                'command' => $command,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de connexion au serveur OCPP'
            ];
        }
    }

    /**
     * Vérifier le statut de connexion d'une borne
     */
    public function checkConnectionStatus(ChargingPoint $chargingPoint)
    {
        try {
            $url = $this->steveServerUrl . '/api/v1/ocpp/status/' . $chargingPoint->charge_box_id;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                
                // Mettre à jour le statut de la borne
                $chargingPoint->update([
                    'steve_connection_status' => $data,
                    'last_connection' => Carbon::now(),
                ]);

                return [
                    'success' => true,
                    'connected' => $data['connected'] ?? false,
                    'last_seen' => $data['lastSeen'] ?? null,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'connected' => false,
                'message' => 'Impossible de vérifier le statut de connexion'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut de connexion', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'connected' => false,
                'message' => 'Erreur technique lors de la vérification'
            ];
        }
    }
}
