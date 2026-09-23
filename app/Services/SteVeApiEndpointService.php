<?php

namespace App\Services;

use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Service complet pour gérer tous les groupes d'endpoints SteVe API
 * 
 * @deprecated Use SteVeHttpClientService instead. This class will be removed in a future version.
 * 
 * Organisé en 5 groupes principaux :
 * 1. Charge Point (ChargeBox) Management
 * 2. Connector / Port Status & Control
 * 3. Transactions & Sessions
 * 4. Configuration & Maintenance Commands
 * 5. Users / Authentication / Tag / Reservation
 */
class SteVeApiEndpointService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected int $maxRetries;
    protected int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->maxRetries = config('steve.retry_attempts', 3);
        $this->retryDelay = config('steve.retry_delay', 1000);
    }

    // ========================================================================
    // GROUPE 1: CHARGE POINT (CHARGEBOX) MANAGEMENT
    // ========================================================================

    /**
     * Créer / Enregistrer un point de charge dans SteVe
     */
    public function createChargePoint(array $data): array
    {
        try {
            Log::info('SteVeApiEndpointService: Creating charge point', ['data' => $data]);

            $response = $this->makeRequest('POST', '/api/v1/charge-points', $data);

            Log::info('SteVeApiEndpointService: Charge point created successfully', [
                'response' => $response
            ]);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Point de charge créé avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to create charge point', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la création du point de charge'
            ];
        }
    }

    /**
     * Lire les détails d'un point de charge (GET /charge-points/{id})
     */
    public function getChargePoint(string $chargePointId): array
    {
        try {
            Log::info('SteVeApiEndpointService: Getting charge point details', [
                'charge_point_id' => $chargePointId
            ]);

            $response = $this->makeRequest('GET', "/api/v1/charge-points/{$chargePointId}");

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Détails du point de charge récupérés avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get charge point', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération du point de charge'
            ];
        }
    }

    /**
     * Mettre à jour les métadonnées et la configuration d'un point de charge
     */
    public function updateChargePoint(string $chargePointId, array $data): array
    {
        try {
            Log::info('SteVeApiEndpointService: Updating charge point', [
                'charge_point_id' => $chargePointId,
                'data' => $data
            ]);

            $response = $this->makeRequest('PUT', "/api/v1/charge-points/{$chargePointId}", $data);

            Log::info('SteVeApiEndpointService: Charge point updated successfully');

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Point de charge mis à jour avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to update charge point', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la mise à jour du point de charge'
            ];
        }
    }

    /**
     * Supprimer / Désenregistrer un point de charge
     */
    public function deleteChargePoint(string $chargePointId): array
    {
        try {
            Log::info('SteVeApiEndpointService: Deleting charge point', [
                'charge_point_id' => $chargePointId
            ]);

            $response = $this->makeRequest('DELETE', "/api/v1/charge-points/{$chargePointId}");

            Log::info('SteVeApiEndpointService: Charge point deleted successfully');

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Point de charge supprimé avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to delete charge point', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la suppression du point de charge'
            ];
        }
    }

    /**
     * Lister tous les points de charge
     */
    public function listChargePoints(array $filters = []): array
    {
        try {
            Log::info('SteVeApiEndpointService: Listing charge points', ['filters' => $filters]);

            $queryString = http_build_query($filters);
            $endpoint = '/api/v1/charge-points' . ($queryString ? "?{$queryString}" : '');
            
            $response = $this->makeRequest('GET', $endpoint);

            return [
                'success' => true,
                'data' => $response,
                'count' => is_array($response) ? count($response) : 0,
                'message' => 'Liste des points de charge récupérée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to list charge points', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération de la liste des points de charge'
            ];
        }
    }

    // ========================================================================
    // GROUPE 2: CONNECTOR / PORT STATUS & CONTROL
    // ========================================================================

    /**
     * Lire le statut du connecteur pour un point de charge donné
     * GET /connectors/status/{chargePointId}
     */
    public function getConnectorStatus(string $chargePointId, ?int $connectorId = null): array
    {
        try {
            $endpoint = $connectorId 
                ? "/api/v1/connectors/status/{$chargePointId}/{$connectorId}"
                : "/api/v1/connectors/status/{$chargePointId}";

            Log::info('SteVeApiEndpointService: Getting connector status', [
                'charge_point_id' => $chargePointId,
                'connector_id' => $connectorId
            ]);

            $response = $this->makeRequest('GET', $endpoint);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Statut du connecteur récupéré avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get connector status', [
                'charge_point_id' => $chargePointId,
                'connector_id' => $connectorId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération du statut du connecteur'
            ];
        }
    }

    /**
     * RemoteStartTransaction — delegates to {@see OcppOperationsService::remoteStart()}.
     *
     * Slice B.1: removed the parallel canonical POST that bypassed the per-CB
     * idempotency lock, ChargingSession state machine, and ChargePointCommand
     * audit. Response shape preserved for SteVeApiEndpointController (reads
     * `success`).
     */
    public function remoteStartTransaction(string $chargePointId, array $params): array
    {
        Log::info('SteVeApiEndpointService: Remote start transaction (delegating)', [
            'charge_box_id' => $chargePointId,
            'connector_id'  => $params['connector_id'] ?? 1,
            'id_tag'        => $params['id_tag']       ?? null,
        ]);

        try {
            $dto = new RemoteStartRequestDTO([
                'chargeBoxId' => $chargePointId,
                'connectorId' => (int) ($params['connector_id'] ?? 1),
                'ocppTag'     => (string) ($params['id_tag'] ?? $params['ocpp_tag'] ?? 'admin'),
            ]);

            $response = app(OcppOperationsService::class)->remoteStart($dto, [
                'source' => 'SteVeApiEndpointService::remoteStartTransaction',
            ]);

            return [
                'success'        => $response->isAccepted(),
                'data'           => $response->toApiResponse()['data'] ?? null,
                'transaction_id' => $response->getTransactionId(),
                'message'        => $response->isAccepted()
                    ? 'Transaction démarrée à distance avec succès'
                    : ('Échec du démarrage à distance: ' . $response->message),
                'error'          => $response->isAccepted() ? null : $response->message,
                'code'           => $response->errorCode,
            ];
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Configuration SteVe manquante',
                'code'    => 'steve_configuration_missing',
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to remote start transaction', [
                'charge_box_id' => $chargePointId,
                'error'         => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Échec du démarrage à distance de la transaction',
            ];
        }
    }

    /**
     * RemoteStopTransaction — delegates to {@see OcppOperationsService::remoteStop()}.
     * transactionId stays in the signature for local state-machine pairing.
     */
    public function remoteStopTransaction(string $chargePointId, string $transactionId): array
    {
        Log::info('SteVeApiEndpointService: Remote stop transaction (delegating)', [
            'charge_box_id'  => $chargePointId,
            'transaction_id' => $transactionId,
        ]);

        try {
            $dto = new RemoteStopRequestDTO([
                'chargeBoxId'   => $chargePointId,
                'transactionId' => (int) $transactionId,
            ]);

            $response = app(OcppOperationsService::class)->remoteStop($dto, [
                'source' => 'SteVeApiEndpointService::remoteStopTransaction',
            ]);

            return [
                'success' => $response->isAccepted(),
                'data'    => $response->toApiResponse()['data'] ?? null,
                'message' => $response->isAccepted()
                    ? 'Transaction arrêtée à distance avec succès'
                    : ('Échec de l\'arrêt à distance: ' . $response->message),
                'error'   => $response->isAccepted() ? null : $response->message,
                'code'    => $response->errorCode,
            ];
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Configuration SteVe manquante',
                'code'    => 'steve_configuration_missing',
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to remote stop transaction', [
                'charge_box_id'  => $chargePointId,
                'transaction_id' => $transactionId,
                'error'          => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'message' => 'Échec de l\'arrêt à distance de la transaction',
            ];
        }
    }

    /**
     * UnlockConnector
     * POST /commands/unlockConnector
     */
    public function unlockConnector(string $chargePointId, int $connectorId): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'connectorId' => $connectorId
            ];

            Log::info('SteVeApiEndpointService: Unlock connector', [
                'charge_point_id' => $chargePointId,
                'connector_id' => $connectorId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/unlockConnector', $payload);

            return [
                'success' => true,
                'data' => $response,
                'status' => $response['status'] ?? null,
                'message' => 'Connecteur débloqué avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to unlock connector', [
                'charge_point_id' => $chargePointId,
                'connector_id' => $connectorId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec du déblocage du connecteur'
            ];
        }
    }

    /**
     * ChangeAvailability
     * POST /commands/changeAvailability
     */
    public function changeAvailability(string $chargePointId, int $connectorId, string $type): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'connectorId' => $connectorId,
                'type' => $type // 'Inoperative' ou 'Operative'
            ];

            Log::info('SteVeApiEndpointService: Change availability', [
                'charge_point_id' => $chargePointId,
                'connector_id' => $connectorId,
                'type' => $type
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/changeAvailability', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Disponibilité modifiée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to change availability', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la modification de la disponibilité'
            ];
        }
    }

    /**
     * ChangeConfiguration
     * POST /commands/changeConfiguration
     */
    public function changeConfiguration(string $chargePointId, string $key, string $value): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'key' => $key,
                'value' => $value
            ];

            Log::info('SteVeApiEndpointService: Change configuration', [
                'charge_point_id' => $chargePointId,
                'key' => $key
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/changeConfiguration', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Configuration modifiée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to change configuration', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la modification de la configuration'
            ];
        }
    }

    /**
     * ClearCache
     * POST /commands/clearCache
     */
    public function clearCache(string $chargePointId): array
    {
        try {
            $payload = ['chargePointId' => $chargePointId];

            Log::info('SteVeApiEndpointService: Clear cache', [
                'charge_point_id' => $chargePointId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/clearCache', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Cache vidé avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to clear cache', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec du vidage du cache'
            ];
        }
    }

    /**
     * Reset
     * POST /commands/reset
     */
    public function reset(string $chargePointId, string $type = 'Soft'): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'type' => $type // 'Soft' ou 'Hard'
            ];

            Log::info('SteVeApiEndpointService: Reset charge point', [
                'charge_point_id' => $chargePointId,
                'type' => $type
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/reset', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Réinitialisation effectuée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to reset', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la réinitialisation'
            ];
        }
    }

    // ========================================================================
    // GROUPE 3: TRANSACTIONS & SESSIONS
    // ========================================================================

    /**
     * Lire la liste des transactions / détails
     * GET /transactions
     */
    public function getTransactions(array $filters = []): array
    {
        try {
            $queryString = http_build_query($filters);
            $endpoint = '/api/v1/transactions' . ($queryString ? "?{$queryString}" : '');

            Log::info('SteVeApiEndpointService: Getting transactions', ['filters' => $filters]);

            $response = $this->makeRequest('GET', $endpoint);

            return [
                'success' => true,
                'data' => $response,
                'count' => is_array($response) ? count($response) : 0,
                'message' => 'Transactions récupérées avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get transactions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération des transactions'
            ];
        }
    }

    /**
     * Obtenir les détails d'une transaction spécifique
     */
    public function getTransaction(string $transactionId): array
    {
        try {
            Log::info('SteVeApiEndpointService: Getting transaction details', [
                'transaction_id' => $transactionId
            ]);

            $response = $this->makeRequest('GET', "/api/v1/transactions/{$transactionId}");

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Détails de la transaction récupérés avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération de la transaction'
            ];
        }
    }

    /**
     * Lire les valeurs de compteur / données de consommation pour une transaction
     */
    public function getMeterValues(string $transactionId, array $filters = []): array
    {
        try {
            $queryString = http_build_query($filters);
            $endpoint = "/api/v1/transactions/{$transactionId}/meter-values" . 
                       ($queryString ? "?{$queryString}" : '');

            Log::info('SteVeApiEndpointService: Getting meter values', [
                'transaction_id' => $transactionId,
                'filters' => $filters
            ]);

            $response = $this->makeRequest('GET', $endpoint);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Valeurs de compteur récupérées avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get meter values', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération des valeurs de compteur'
            ];
        }
    }

    // ========================================================================
    // GROUPE 4: CONFIGURATION & MAINTENANCE COMMANDS
    // ========================================================================

    /**
     * GetDiagnostics
     * POST /commands/getDiagnostics
     */
    public function getDiagnostics(string $chargePointId, array $params = []): array
    {
        try {
            $payload = array_merge([
                'chargePointId' => $chargePointId,
            ], $params);

            Log::info('SteVeApiEndpointService: Get diagnostics', [
                'charge_point_id' => $chargePointId,
                'params' => $params
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/getDiagnostics', $payload);

            return [
                'success' => true,
                'data' => $response,
                'file_name' => $response['fileName'] ?? null,
                'message' => 'Diagnostics récupérés avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get diagnostics', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération des diagnostics'
            ];
        }
    }

    /**
     * GetLog / Récupérer les logs de la station
     * POST /commands/getLog
     */
    public function getLog(string $chargePointId, array $params = []): array
    {
        try {
            $payload = array_merge([
                'chargePointId' => $chargePointId,
            ], $params);

            Log::info('SteVeApiEndpointService: Get log', [
                'charge_point_id' => $chargePointId,
                'params' => $params
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/getLog', $payload);

            return [
                'success' => true,
                'data' => $response,
                'file_name' => $response['fileName'] ?? null,
                'message' => 'Logs récupérés avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get log', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération des logs'
            ];
        }
    }

    /**
     * UpdateFirmware
     * POST /commands/updateFirmware
     */
    public function updateFirmware(string $chargePointId, string $location, ?string $retrieveDate = null): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'location' => $location,
            ];

            if ($retrieveDate) {
                $payload['retrieveDate'] = $retrieveDate;
            }

            Log::info('SteVeApiEndpointService: Update firmware', [
                'charge_point_id' => $chargePointId,
                'location' => $location
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/updateFirmware', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Mise à jour du firmware lancée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to update firmware', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la mise à jour du firmware'
            ];
        }
    }

    /**
     * GetConfiguration
     * POST /commands/getConfiguration
     */
    public function getConfiguration(string $chargePointId, array $keys = []): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
            ];

            if (!empty($keys)) {
                $payload['keys'] = $keys;
            }

            Log::info('SteVeApiEndpointService: Get configuration', [
                'charge_point_id' => $chargePointId,
                'keys' => $keys
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/getConfiguration', $payload);

            return [
                'success' => true,
                'data' => $response,
                'configuration' => $response['configurationKey'] ?? [],
                'message' => 'Configuration récupérée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get configuration', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération de la configuration'
            ];
        }
    }

    /**
     * ClearChargingProfile
     * POST /commands/clearChargingProfile
     */
    public function clearChargingProfile(string $chargePointId, array $params = []): array
    {
        try {
            $payload = array_merge([
                'chargePointId' => $chargePointId,
            ], $params);

            Log::info('SteVeApiEndpointService: Clear charging profile', [
                'charge_point_id' => $chargePointId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/clearChargingProfile', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Profil de charge effacé avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to clear charging profile', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de l\'effacement du profil de charge'
            ];
        }
    }

    /**
     * SetChargingProfile
     * POST /commands/setChargingProfile
     */
    public function setChargingProfile(string $chargePointId, array $chargingProfile): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'chargingProfile' => $chargingProfile
            ];

            Log::info('SteVeApiEndpointService: Set charging profile', [
                'charge_point_id' => $chargePointId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/setChargingProfile', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Profil de charge défini avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to set charging profile', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la définition du profil de charge'
            ];
        }
    }

    /**
     * GetCompositeSchedule
     * POST /commands/getCompositeSchedule
     */
    public function getCompositeSchedule(string $chargePointId, int $connectorId, int $duration, ?string $chargingRateUnit = null): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'connectorId' => $connectorId,
                'duration' => $duration
            ];

            if ($chargingRateUnit) {
                $payload['chargingRateUnit'] = $chargingRateUnit;
            }

            Log::info('SteVeApiEndpointService: Get composite schedule', [
                'charge_point_id' => $chargePointId,
                'connector_id' => $connectorId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/getCompositeSchedule', $payload);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Planning composite récupéré avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get composite schedule', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération du planning composite'
            ];
        }
    }

    // ========================================================================
    // GROUPE 5: USERS / AUTHENTICATION / TAG / RESERVATION
    // ========================================================================

    /**
     * Gestion des utilisateurs et idTags
     * GET /api/v1/users
     */
    public function listUsers(array $filters = []): array
    {
        try {
            $queryString = http_build_query($filters);
            $endpoint = '/api/v1/users' . ($queryString ? "?{$queryString}" : '');

            Log::info('SteVeApiEndpointService: Listing users');

            $response = $this->makeRequest('GET', $endpoint);

            return [
                'success' => true,
                'data' => $response,
                'count' => is_array($response) ? count($response) : 0,
                'message' => 'Utilisateurs récupérés avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to list users', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération des utilisateurs'
            ];
        }
    }

    /**
     * Obtenir les détails d'un utilisateur/idTag
     */
    public function getUser(string $userId): array
    {
        try {
            Log::info('SteVeApiEndpointService: Getting user details', ['user_id' => $userId]);

            $response = $this->makeRequest('GET', "/api/v1/users/{$userId}");

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Détails de l\'utilisateur récupérés avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération de l\'utilisateur'
            ];
        }
    }

    /**
     * Créer un utilisateur/idTag
     */
    public function createUser(array $data): array
    {
        try {
            Log::info('SteVeApiEndpointService: Creating user', ['data' => $data]);

            $response = $this->makeRequest('POST', '/api/v1/users', $data);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Utilisateur créé avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to create user', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la création de l\'utilisateur'
            ];
        }
    }

    /**
     * Mettre à jour un utilisateur/idTag
     */
    public function updateUser(string $userId, array $data): array
    {
        try {
            Log::info('SteVeApiEndpointService: Updating user', [
                'user_id' => $userId,
                'data' => $data
            ]);

            $response = $this->makeRequest('PUT', "/api/v1/users/{$userId}", $data);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Utilisateur mis à jour avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to update user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la mise à jour de l\'utilisateur'
            ];
        }
    }

    /**
     * Supprimer un utilisateur/idTag
     */
    public function deleteUser(string $userId): array
    {
        try {
            Log::info('SteVeApiEndpointService: Deleting user', ['user_id' => $userId]);

            $response = $this->makeRequest('DELETE', "/api/v1/users/{$userId}");

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Utilisateur supprimé avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to delete user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la suppression de l\'utilisateur'
            ];
        }
    }

    /**
     * ReserveNow - Réserver une borne pour une réservation future
     * POST /commands/reserveNow
     */
    public function reserveNow(string $chargePointId, array $params): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'connectorId' => $params['connector_id'] ?? 0, // 0 pour réserver la borne entière
                'expiryDate' => $params['expiry_date'] ?? now()->addHours(1)->toISOString(),
                'idTag' => $params['id_tag'],
                'parentIdTag' => $params['parent_id_tag'] ?? null,
                'reservationId' => $params['reservation_id'] ?? null
            ];

            Log::info('SteVeApiEndpointService: Reserve now', [
                'charge_point_id' => $chargePointId,
                'payload' => $payload
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/reserveNow', $payload);

            return [
                'success' => true,
                'data' => $response,
                'reservation_id' => $response['reservationId'] ?? null,
                'status' => $response['status'] ?? null,
                'message' => 'Réservation effectuée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to reserve now', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la réservation'
            ];
        }
    }

    /**
     * CancelReservation - Annuler une réservation
     * POST /commands/cancelReservation
     */
    public function cancelReservation(string $chargePointId, int $reservationId): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'reservationId' => $reservationId
            ];

            Log::info('SteVeApiEndpointService: Cancel reservation', [
                'charge_point_id' => $chargePointId,
                'reservation_id' => $reservationId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/cancelReservation', $payload);

            return [
                'success' => true,
                'data' => $response,
                'status' => $response['status'] ?? null,
                'message' => 'Réservation annulée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to cancel reservation', [
                'charge_point_id' => $chargePointId,
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de l\'annulation de la réservation'
            ];
        }
    }

    /**
     * LocalListVersion - Obtenir la version de la liste locale
     * POST /commands/getLocalListVersion
     */
    public function getLocalListVersion(string $chargePointId): array
    {
        try {
            $payload = ['chargePointId' => $chargePointId];

            Log::info('SteVeApiEndpointService: Get local list version', [
                'charge_point_id' => $chargePointId
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/getLocalListVersion', $payload);

            return [
                'success' => true,
                'data' => $response,
                'list_version' => $response['listVersion'] ?? null,
                'message' => 'Version de la liste locale récupérée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to get local list version', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de la récupération de la version de la liste locale'
            ];
        }
    }

    /**
     * SendLocalList - Envoyer une liste blanche
     * POST /commands/sendLocalList
     */
    public function sendLocalList(string $chargePointId, int $listVersion, string $updateType, array $localAuthorizationList = []): array
    {
        try {
            $payload = [
                'chargePointId' => $chargePointId,
                'listVersion' => $listVersion,
                'updateType' => $updateType, // 'Full', 'Differential'
                'localAuthorizationList' => $localAuthorizationList
            ];

            Log::info('SteVeApiEndpointService: Send local list', [
                'charge_point_id' => $chargePointId,
                'list_version' => $listVersion,
                'update_type' => $updateType
            ]);

            $response = $this->makeRequest('POST', '/api/v1/commands/sendLocalList', $payload);

            return [
                'success' => true,
                'data' => $response,
                'status' => $response['status'] ?? null,
                'message' => 'Liste locale envoyée avec succès'
            ];
        } catch (Exception $e) {
            Log::error('SteVeApiEndpointService: Failed to send local list', [
                'charge_point_id' => $chargePointId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Échec de l\'envoi de la liste locale'
            ];
        }
    }

    // ========================================================================
    // MÉTHODES UTILITAIRES
    // ========================================================================

    /**
     * Effectuer une requête HTTP avec retry
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $url = $this->baseUrl . $endpoint;
                
                $httpClient = Http::timeout($this->timeout)
                    ->withBasicAuth($this->username, $this->password)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]);

                switch (strtoupper($method)) {
                    case 'GET':
                        $response = $httpClient->get($url);
                        break;
                    case 'POST':
                        $response = $httpClient->post($url, $data);
                        break;
                    case 'PUT':
                        $response = $httpClient->put($url, $data);
                        break;
                    case 'DELETE':
                        $response = $httpClient->delete($url);
                        break;
                    default:
                        throw new Exception("Méthode HTTP non supportée: {$method}");
                }

                if ($response->successful()) {
                    return $response->json();
                } else {
                    $errorMessage = "HTTP {$response->status()}";
                    $contentType = $response->header('Content-Type');
                    if (str_contains($contentType ?? '', 'application/json')) {
                        $errorMessage .= ": " . json_encode($response->json());
                    }
                    throw new Exception($errorMessage);
                }

            } catch (Exception $e) {
                $lastException = $e;
                
                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelay * $attempt;
                    usleep($delay * 1000);
                }
            }
        }

        throw $lastException ?? new Exception('Toutes les tentatives ont échoué');
    }

    /**
     * Obtenir la liste de tous les groupes d'endpoints disponibles
     */
    public function getAvailableEndpointGroups(): array
    {
        return [
            'group_1_charge_point_management' => [
                'name' => 'Charge Point (ChargeBox) Management',
                'endpoints' => [
                    'createChargePoint',
                    'getChargePoint',
                    'updateChargePoint',
                    'deleteChargePoint',
                    'listChargePoints'
                ]
            ],
            'group_2_connector_control' => [
                'name' => 'Connector / Port Status & Control',
                'endpoints' => [
                    'getConnectorStatus',
                    'remoteStartTransaction',
                    'remoteStopTransaction',
                    'unlockConnector',
                    'changeAvailability',
                    'changeConfiguration',
                    'clearCache',
                    'reset'
                ]
            ],
            'group_3_transactions' => [
                'name' => 'Transactions & Sessions',
                'endpoints' => [
                    'getTransactions',
                    'getTransaction',
                    'getMeterValues'
                ]
            ],
            'group_4_maintenance' => [
                'name' => 'Configuration & Maintenance Commands',
                'endpoints' => [
                    'getDiagnostics',
                    'getLog',
                    'updateFirmware',
                    'getConfiguration',
                    'clearChargingProfile',
                    'setChargingProfile',
                    'getCompositeSchedule'
                ]
            ],
            'group_5_users_reservations' => [
                'name' => 'Users / Authentication / Tag / Reservation',
                'endpoints' => [
                    'listUsers',
                    'getUser',
                    'createUser',
                    'updateUser',
                    'deleteUser',
                    'reserveNow',
                    'cancelReservation',
                    'getLocalListVersion',
                    'sendLocalList'
                ]
            ]
        ];
    }
}

