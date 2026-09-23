<?php

namespace App\Services;

use App\Exceptions\SteVeConfigurationException;
use App\DTO\OCPP\AvailabilityStatusEnum;
use App\DTO\OCPP\AvailabilityTypeEnum;
use App\DTO\OCPP\ChangeAvailabilityRequestDTO;
use App\DTO\OCPP\ChangeAvailabilityResponseDTO;
use App\DTO\OCPP\LockConnectorRequestDTO;
use App\DTO\OCPP\LockConnectorResponseDTO;
use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStartResponseDTO;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\DTO\OCPP\RemoteStopResponseDTO;
use App\DTO\OCPP\ResetRequestDTO;
use App\DTO\OCPP\ResetResponseDTO;
use App\DTO\OCPP\UnlockConnectorRequestDTO;
use App\DTO\OCPP\UnlockConnectorResponseDTO;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Connector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;
use Throwable;

/**
 * Service pour les opérations OCPP
 * 
 * Gère les commandes OCPP vers les bornes de recharge via l'API SteVe.
 * Ce service encapsule la logique métier et le mapping des DTOs.
 */
class OcppOperationsService
{
    /**
     * Durée de cache en secondes pour le verrouillage des opérations
     */
    protected const OPERATION_LOCK_TTL = 30;

    public function __construct(
        protected SteVeHttpClientService $steveClient
    ) {}

    // =========================================================================
    // CHANGE AVAILABILITY OPERATIONS
    // =========================================================================

    /**
     * Change la disponibilité d'un connecteur ou de toute la borne
     */
    public function changeAvailability(ChangeAvailabilityRequestDTO $request): ChangeAvailabilityResponseDTO
    {
        // Validation de la requête
        if (!$request->isValid()) {
            $errors = $request->validate();
            return ChangeAvailabilityResponseDTO::error(
                'Données de requête invalides: ' . implode(', ', $errors),
                $request
            );
        }

        // Vérification du verrouillage pour éviter les opérations concurrentes
        $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
        if (!Cache::add($lockKey, true, self::OPERATION_LOCK_TTL)) { // Bug #11: atomic acquire (was Cache::has + Cache::put — TOCTOU race)
            return ChangeAvailabilityResponseDTO::error(
                'Une opération est déjà en cours pour ce connecteur. Veuillez patienter.',
                $request
            );
        }

        try {
            Log::info('OcppOperationsService: Sending ChangeAvailability request', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'type' => $request->type->value,
            ]);

            // Appel à l'API SteVe
            $response = $this->steveClient->changeAvailability(
                $request->chargeBoxId,
                $request->connectorId,
                $request->type->value
            );

            // Mapping de la réponse
            $result = ChangeAvailabilityResponseDTO::fromApiResponse($response, $request);

            Log::info('OcppOperationsService: ChangeAvailability response received', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'success' => $result->success,
                'status' => $result->status?->value,
            ]);

            return $result;

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: ChangeAvailability failed', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'error' => $e->getMessage(),
            ]);

            return ChangeAvailabilityResponseDTO::error(
                'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
                $request
            );

        } finally {
            // Libérer le verrouillage
            Cache::forget($lockKey);
        }
    }

    /**
     * Active un connecteur (le rend opérationnel)
     */
    public function enableConnector(string $chargeBoxId, int $connectorId = 0): ChangeAvailabilityResponseDTO
    {
        $request = ChangeAvailabilityRequestDTO::makeOperative($chargeBoxId, $connectorId);
        return $this->changeAvailability($request);
    }

    /**
     * Désactive un connecteur (le rend inopérationnel)
     */
    public function disableConnector(string $chargeBoxId, int $connectorId = 0): ChangeAvailabilityResponseDTO
    {
        $request = ChangeAvailabilityRequestDTO::makeInoperative($chargeBoxId, $connectorId);
        return $this->changeAvailability($request);
    }

    /**
     * Active toute la borne (tous les connecteurs)
     */
    public function enableChargePoint(string $chargeBoxId): ChangeAvailabilityResponseDTO
    {
        return $this->enableConnector($chargeBoxId, 0);
    }

    /**
     * Désactive toute la borne (tous les connecteurs)
     */
    public function disableChargePoint(string $chargeBoxId): ChangeAvailabilityResponseDTO
    {
        return $this->disableConnector($chargeBoxId, 0);
    }

    /**
     * Change la disponibilité depuis un modèle ChargingPoint
     */
    public function changeAvailabilityForChargingPoint(
        ChargingPoint $chargingPoint,
        AvailabilityTypeEnum $type,
        int $connectorId = 0
    ): ChangeAvailabilityResponseDTO {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return ChangeAvailabilityResponseDTO::error(
                'Point de charge non configuré pour SteVe (charge_box_id manquant)'
            );
        }

        $request = new ChangeAvailabilityRequestDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'type' => $type,
        ]);

        $response = $this->changeAvailability($request);

        // Mettre à jour le statut local si la commande a été acceptée
        if ($response->isAccepted() && $connectorId > 0) {
            $this->updateLocalConnectorStatus($chargingPoint, $connectorId, $type);
        }

        return $response;
    }

    /**
     * Change la disponibilité depuis un modèle Connector
     */
    public function changeAvailabilityForConnector(
        Connector $connector,
        AvailabilityTypeEnum $type
    ): ChangeAvailabilityResponseDTO {
        $chargingPoint = $connector->chargingPoint;

        if (!$chargingPoint) {
            return ChangeAvailabilityResponseDTO::error(
                'Point de charge non trouvé pour ce connecteur'
            );
        }

        return $this->changeAvailabilityForChargingPoint(
            $chargingPoint,
            $type,
            $connector->connector_id
        );
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Met à jour le statut local du connecteur après une commande réussie
     */
    protected function updateLocalConnectorStatus(
        ChargingPoint $chargingPoint,
        int $connectorId,
        AvailabilityTypeEnum $type
    ): void {
        try {
            $connector = $chargingPoint->connectors()
                ->where('connector_id', $connectorId)
                ->first();

            if ($connector) {
                $newStatus = $type->isOperative() 
                    ? Connector::STATUS_AVAILABLE 
                    : Connector::STATUS_UNAVAILABLE;

                $connector->update(['status' => $newStatus]);

                Log::debug('OcppOperationsService: Updated local connector status', [
                    'chargingPointId' => $chargingPoint->id,
                    'connectorId' => $connectorId,
                    'newStatus' => $newStatus,
                ]);
            }
        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::warning('OcppOperationsService: Failed to update local connector status', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Génère la clé de verrouillage pour une opération
     */
    protected function getOperationLockKey(string $chargeBoxId, int $connectorId): string
    {
        return "ocpp_operation_lock:{$chargeBoxId}:{$connectorId}";
    }

    // =========================================================================
    // OTHER OCPP OPERATIONS
    // =========================================================================

    /**
     * Réinitialise la borne (soft ou hard reset)
     */
    // Legacy method - use resetChargePoint(ResetRequestDTO $request) instead
    public function resetChargePointLegacy(string $chargeBoxId, bool $hard = false): array
    {
        try {
            $type = $hard ? 'Hard' : 'Soft';

            Log::info('OcppOperationsService: Sending Reset request', [
                'chargeBoxId' => $chargeBoxId,
                'type' => $type,
            ]);

            $response = $this->steveClient->reset($chargeBoxId, $type);

            return [
                'success' => $response['success'] ?? false,
                'message' => $response['message'] ?? 'Réinitialisation envoyée',
                'data' => $response['data'] ?? null,
            ];

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: Reset failed', [
                'chargeBoxId' => $chargeBoxId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Déverrouille un connecteur
     */
    // Legacy method - use unlockConnector(UnlockConnectorRequestDTO $request) instead
    public function unlockConnectorLegacy(string $chargeBoxId, int $connectorId): array
    {
        try {
            Log::info('OcppOperationsService: Sending UnlockConnector request', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
            ]);

            $response = $this->steveClient->unlockConnector($chargeBoxId, $connectorId);

            return [
                'success' => $response['success'] ?? false,
                'message' => $response['message'] ?? 'Déverrouillage envoyé',
                'data' => $response['data'] ?? null,
            ];

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: UnlockConnector failed', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du déverrouillage: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vide le cache de la borne
     */
    public function clearCache(string $chargeBoxId): array
    {
        try {
            Log::info('OcppOperationsService: Sending ClearCache request', [
                'chargeBoxId' => $chargeBoxId,
            ]);

            $response = $this->steveClient->clearCache($chargeBoxId);

            return [
                'success' => $response['success'] ?? false,
                'message' => $response['message'] ?? 'Cache vidé',
                'data' => $response['data'] ?? null,
            ];

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: ClearCache failed', [
                'chargeBoxId' => $chargeBoxId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du vidage du cache: ' . $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // BATCH OPERATIONS
    // =========================================================================

    /**
     * Change la disponibilité de plusieurs connecteurs
     * 
     * @return array<int, ChangeAvailabilityResponseDTO>
     */
    public function batchChangeAvailability(array $requests): array
    {
        $results = [];

        foreach ($requests as $index => $request) {
            if ($request instanceof ChangeAvailabilityRequestDTO) {
                $results[$index] = $this->changeAvailability($request);
            } elseif (is_array($request)) {
                $dto = new ChangeAvailabilityRequestDTO($request);
                $results[$index] = $this->changeAvailability($dto);
            }
        }

        return $results;
    }

    /**
     * Active tous les connecteurs d'une borne
     * 
     * @return array<int, ChangeAvailabilityResponseDTO>
     */
    public function enableAllConnectors(ChargingPoint $chargingPoint): array
    {
        $results = [];
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return [];
        }

        foreach ($chargingPoint->connectors as $connector) {
            $results[$connector->connector_id] = $this->enableConnector(
                $chargeBoxId,
                $connector->connector_id
            );
        }

        return $results;
    }

    /**
     * Désactive tous les connecteurs d'une borne
     * 
     * @return array<int, ChangeAvailabilityResponseDTO>
     */
    public function disableAllConnectors(ChargingPoint $chargingPoint): array
    {
        $results = [];
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return [];
        }

        foreach ($chargingPoint->connectors as $connector) {
            $results[$connector->connector_id] = $this->disableConnector(
                $chargeBoxId,
                $connector->connector_id
            );
        }

        return $results;
    }

    // =========================================================================
    // RESET OPERATIONS
    // =========================================================================

    /**
     * Reset la borne (Soft ou Hard)
     */
    public function resetChargePoint(ResetRequestDTO $request): ResetResponseDTO
    {
        // Validation de la requête
        if (!$request->isValid()) {
            return ResetResponseDTO::error(
                'Données de requête invalides: ' . implode(', ', $request->validate()),
                $request->chargeBoxId
            );
        }

        // Vérification du verrouillage pour éviter les opérations concurrentes
        $lockKey = $this->getOperationLockKey($request->chargeBoxId, 0);
        if (!Cache::add($lockKey, true, self::OPERATION_LOCK_TTL)) { // Bug #11: atomic acquire (was Cache::has + Cache::put — TOCTOU race)
            return ResetResponseDTO::error(
                'Une opération est déjà en cours pour cette borne. Veuillez patienter.',
                $request->chargeBoxId
            );
        }

        try {
            Log::info('OcppOperationsService: Sending Reset request', [
                'chargeBoxId' => $request->chargeBoxId,
                'type' => $request->type->value,
            ]);

            // Appel à l'API SteVe
            $response = $this->steveClient->reset($request->chargeBoxId, $request->type->value);

            Log::info('OcppOperationsService: Reset response received', [
                'chargeBoxId' => $request->chargeBoxId,
                'success' => $response['success'] ?? false,
            ]);

            return ResetResponseDTO::fromApiResponse($response, $request);

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: Reset failed', [
                'chargeBoxId' => $request->chargeBoxId,
                'error' => $e->getMessage(),
            ]);

            return ResetResponseDTO::error(
                'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
                $request->chargeBoxId
            );

        } finally {
            // Libérer le verrouillage
            Cache::forget($lockKey);
        }
    }

    /**
     * Reset soft (graceful)
     */
    public function softReset(string $chargeBoxId): ResetResponseDTO
    {
        $request = ResetRequestDTO::soft($chargeBoxId);
        return $this->resetChargePoint($request);
    }

    /**
     * Reset hard (immediate)
     */
    public function hardReset(string $chargeBoxId): ResetResponseDTO
    {
        $request = ResetRequestDTO::hard($chargeBoxId);
        return $this->resetChargePoint($request);
    }

    // =========================================================================
    // LOCK/UNLOCK CONNECTOR OPERATIONS
    // =========================================================================

    /**
     * Verrouille un connecteur
     */
    public function lockConnector(LockConnectorRequestDTO $request): LockConnectorResponseDTO
    {
        // Validation de la requête
        if (!$request->isValid()) {
            return LockConnectorResponseDTO::error(
                'Données de requête invalides',
                $request->chargeBoxId,
                $request->connectorId
            );
        }

        // Vérification du verrouillage
        $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
        if (!Cache::add($lockKey, true, self::OPERATION_LOCK_TTL)) { // Bug #11: atomic acquire (was Cache::has + Cache::put — TOCTOU race)
            return LockConnectorResponseDTO::error(
                'Une opération est déjà en cours pour ce connecteur. Veuillez patienter.',
                $request->chargeBoxId,
                $request->connectorId
            );
        }

        try {
            // Lock already acquired above via Cache::add (Bug #11).

            Log::info('OcppOperationsService: Sending LockConnector request', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
            ]);

            // OCPP n'a pas de commande LockConnector directe
            // On utilise ChangeAvailability vers Inoperative comme alternative
            $response = $this->steveClient->changeAvailability(
                $request->chargeBoxId,
                $request->connectorId,
                'Inoperative'
            );

            Log::info('OcppOperationsService: LockConnector response received', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'success' => $response['success'] ?? false,
            ]);

            return LockConnectorResponseDTO::fromApiResponse($response, $request);

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: LockConnector failed', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'error' => $e->getMessage(),
            ]);

            return LockConnectorResponseDTO::error(
                'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
                $request->chargeBoxId,
                $request->connectorId
            );
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Déverrouille un connecteur
     */
    public function unlockConnector(UnlockConnectorRequestDTO $request): UnlockConnectorResponseDTO
    {
        // Validation de la requête
        if (!$request->isValid()) {
            return UnlockConnectorResponseDTO::error(
                'Données de requête invalides',
                $request->chargeBoxId,
                $request->connectorId
            );
        }

        // Vérification du verrouillage
        $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
        if (!Cache::add($lockKey, true, self::OPERATION_LOCK_TTL)) { // Bug #11: atomic acquire (was Cache::has + Cache::put — TOCTOU race)
            return UnlockConnectorResponseDTO::error(
                'Une opération est déjà en cours pour ce connecteur. Veuillez patienter.',
                $request->chargeBoxId,
                $request->connectorId
            );
        }

        try {
            // Lock already acquired above via Cache::add (Bug #11).

            Log::info('OcppOperationsService: Sending UnlockConnector request', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
            ]);

            $response = $this->steveClient->unlockConnector($request->chargeBoxId, $request->connectorId);

            Log::info('OcppOperationsService: UnlockConnector response received', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'success' => $response['success'] ?? false,
            ]);

            return UnlockConnectorResponseDTO::fromApiResponse($response, $request);

        } catch (SteVeConfigurationException $e) {
            // Let this bubble up so the controller / global handler renders HTTP 503
            // (Bug #1.4). The finally block below still releases the lock.
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: UnlockConnector failed', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'error' => $e->getMessage(),
            ]);

            return UnlockConnectorResponseDTO::error(
                'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
                $request->chargeBoxId,
                $request->connectorId
            );
        } finally {
            Cache::forget($lockKey);
        }
    }

    // =========================================================================
    // REMOTE START/STOP OPERATIONS
    // =========================================================================

    /**
     * Démarre une session de charge à distance.
     *
     * Hardened path:
     *   1. Validate DTO (rejects bad payloads with a 4xx-shaped response).
     *   2. Acquire per-(chargeBoxId,connectorId) lock to serialize concurrent
     *      starts on the same connector.
     *   3. Block if a non-terminal ChargingSession already occupies the slot —
     *      prevents double-start when an offer-flow session is mid-flight.
     *   4. Call SteVe.
     *   5. On accepted: promote any matching pending session to ACTIVE and
     *      stash the steve_transaction_id; otherwise log and continue.
     *   6. Record a ChargePointCommand audit row regardless of outcome.
     */
    public function remoteStart(RemoteStartRequestDTO $request, array $context = []): RemoteStartResponseDTO
    {
        if (!$request->isValid()) {
            return RemoteStartResponseDTO::rejected(
                'Données de requête invalides: ' . implode(', ', $request->validate())
            );
        }

        $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
        if (!Cache::add($lockKey, true, self::OPERATION_LOCK_TTL)) {
            return RemoteStartResponseDTO::rejected(
                'Une opération est déjà en cours pour ce connecteur. Veuillez patienter.'
            );
        }

        try {
            // DB-level idempotency guard: if a non-terminal session already exists
            // for this (charge_point, connector) pair, refuse to dispatch a second
            // RemoteStart. The cache lock above only protects within a single
            // process; this catches operators on different workers / API nodes.
            $existing = $this->findActiveSession($request->chargeBoxId, $request->connectorId);
            if ($existing !== null) {
                $this->recordCommandAudit('remote-start', $request->chargeBoxId, [
                    'connectorId' => $request->connectorId,
                    'ocppTag'     => $request->ocppTag,
                    'sessionId'   => $existing->id,
                    'idempotencyKey' => $context['idempotencyKey'] ?? null,
                ], false, ['error' => 'session_already_active']);

                return RemoteStartResponseDTO::conflict(
                    'Une session de recharge est déjà active sur ce connecteur.'
                );
            }

            Log::info('OcppOperationsService: Sending RemoteStart request', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'ocppTag'     => $request->ocppTag,
            ]);

            $response = $this->steveClient->remoteStartTransaction($request->chargeBoxId, [
                'connector_id' => $request->connectorId,
                'id_tag'       => $request->ocppTag,
            ]);

            Log::info('OcppOperationsService: RemoteStart response received', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'success'     => $response['success'] ?? false,
            ]);

            $dto = RemoteStartResponseDTO::fromApiResponse($response, $request);

            if ($dto->isAccepted()) {
                $this->promoteSessionToActive($request, $response);
            }

            $this->recordCommandAudit('remote-start', $request->chargeBoxId, [
                'connectorId'    => $request->connectorId,
                'ocppTag'        => $request->ocppTag,
                'idempotencyKey' => $context['idempotencyKey'] ?? null,
            ], $dto->isAccepted(), $response);

            return $dto;

        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: RemoteStart failed', [
                'chargeBoxId' => $request->chargeBoxId,
                'connectorId' => $request->connectorId,
                'error'       => $e->getMessage(),
            ]);

            $this->recordCommandAudit('remote-start', $request->chargeBoxId, [
                'connectorId' => $request->connectorId,
                'ocppTag'     => $request->ocppTag,
            ], false, ['exception' => $e->getMessage()]);

            return RemoteStartResponseDTO::rejected(
                'Erreur lors de la communication avec la borne: ' . $e->getMessage()
            );
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Arrête une session de charge à distance.
     *
     * Hardened path mirrors remoteStart:
     *   1. Validate DTO.
     *   2. Per-charge-box lock.
     *   3. Resolve any local ChargingSession matching the SteVe transactionId.
     *      If found AND already terminal, return 409-shaped response (no
     *      duplicate stop dispatch). If found AND active, proceed.
     *   4. Call SteVe.
     *   5. On accepted: transition matching session to STOPPED + capture
     *      steve_stop_response. Missing local row is non-fatal (operator may
     *      be stopping an orphan/unmanaged charge).
     *   6. Audit row regardless of outcome.
     */
    public function remoteStop(RemoteStopRequestDTO $request, array $context = []): RemoteStopResponseDTO
    {
        if (!$request->isValid()) {
            return RemoteStopResponseDTO::rejected(
                'Données de requête invalides: ' . implode(', ', $request->validate())
            );
        }

        $lockKey = $this->getOperationLockKey($request->chargeBoxId, 0);
        if (!Cache::add($lockKey, true, self::OPERATION_LOCK_TTL)) {
            return RemoteStopResponseDTO::rejected(
                'Une opération est déjà en cours pour cette borne. Veuillez patienter.'
            );
        }

        try {
            $localSession = $this->findSessionByTransactionId($request->transactionId);

            if ($localSession !== null && $this->isTerminal($localSession->status)) {
                $this->recordCommandAudit('remote-stop', $request->chargeBoxId, [
                    'transactionId'  => $request->transactionId,
                    'sessionId'      => $localSession->id,
                    'idempotencyKey' => $context['idempotencyKey'] ?? null,
                ], false, ['error' => 'session_already_terminated']);

                return RemoteStopResponseDTO::conflict(
                    'Cette session de recharge est déjà terminée.'
                );
            }

            Log::info('OcppOperationsService: Sending RemoteStop request', [
                'chargeBoxId'   => $request->chargeBoxId,
                'transactionId' => $request->transactionId,
                'localSession'  => $localSession?->id,
            ]);

            $response = $this->steveClient->remoteStopTransaction(
                $request->chargeBoxId,
                $request->transactionId
            );

            Log::info('OcppOperationsService: RemoteStop response received', [
                'chargeBoxId' => $request->chargeBoxId,
                'success'     => $response['success'] ?? false,
            ]);

            $dto = RemoteStopResponseDTO::fromApiResponse($response, $request);

            if ($dto->isAccepted() && $localSession !== null) {
                $this->transitionSessionToStopped($localSession, $response);
            }

            $this->recordCommandAudit('remote-stop', $request->chargeBoxId, [
                'transactionId'  => $request->transactionId,
                'sessionId'      => $localSession?->id,
                'idempotencyKey' => $context['idempotencyKey'] ?? null,
            ], $dto->isAccepted(), $response);

            return $dto;

        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('OcppOperationsService: RemoteStop failed', [
                'chargeBoxId' => $request->chargeBoxId,
                'error'       => $e->getMessage(),
            ]);

            $this->recordCommandAudit('remote-stop', $request->chargeBoxId, [
                'transactionId' => $request->transactionId,
            ], false, ['exception' => $e->getMessage()]);

            return RemoteStopResponseDTO::rejected(
                'Erreur lors de la communication avec la borne: ' . $e->getMessage()
            );
        } finally {
            Cache::forget($lockKey);
        }
    }

    // =========================================================================
    // SESSION STATE MACHINE + AUDIT (added in remote-start/stop hardening)
    // =========================================================================

    /**
     * Find an in-flight ChargingSession occupying the given (chargeBoxId, connectorId)
     * that should BLOCK a second RemoteStart. PENDING sessions (offer-flow rows
     * that haven't dispatched yet) are deliberately excluded — those should be
     * promoted by promoteSessionToActive(), not refused.
     *
     * Returns null when the schema isn't migrated or no session is in flight.
     */
    protected function findActiveSession(string $chargeBoxId, int $connectorId): ?ChargingSession
    {
        if (!Schema::hasTable('charging_sessions')) {
            return null;
        }

        $chargingPointId = $this->resolveLocalChargingPointId($chargeBoxId);
        if ($chargingPointId === null) {
            return null;
        }

        return ChargingSession::where('charging_point_id', $chargingPointId)
            ->where('connector_id', $connectorId)
            ->whereIn('status', [
                ChargingSession::STATUS_INITIATING,
                ChargingSession::STATUS_ACTIVE,
                ChargingSession::STATUS_IN_PROGRESS,
            ])
            ->orderByDesc('id')
            ->first();
    }

    protected function findSessionByTransactionId(int $transactionId): ?ChargingSession
    {
        if (!Schema::hasTable('charging_sessions')) {
            return null;
        }

        return ChargingSession::where('steve_transaction_id', (string) $transactionId)
            ->orderByDesc('id')
            ->first();
    }

    protected function isTerminal(?string $status): bool
    {
        return in_array($status, [
            ChargingSession::STATUS_COMPLETED,
            ChargingSession::STATUS_STOPPED,
            ChargingSession::STATUS_FAILED,
            ChargingSession::STATUS_CANCELLED,
            ChargingSession::STATUS_ERROR,
            ChargingSession::STATUS_TIMEOUT,
        ], true);
    }

    /**
     * Promote a pending/initiating session to ACTIVE after SteVe accepts the
     * RemoteStartTransaction. Wrapped in a transaction with a `lockForUpdate`
     * to keep concurrent workers from racing on the same session row.
     */
    protected function promoteSessionToActive(RemoteStartRequestDTO $request, array $response): void
    {
        if (!Schema::hasTable('charging_sessions')) {
            return;
        }

        $chargingPointId = $this->resolveLocalChargingPointId($request->chargeBoxId);
        if ($chargingPointId === null) {
            return;
        }

        $steveTxnId = $this->extractTransactionId($response);

        try {
            DB::transaction(function () use ($request, $chargingPointId, $response, $steveTxnId) {
                $session = ChargingSession::where('charging_point_id', $chargingPointId)
                    ->where('connector_id', $request->connectorId)
                    ->whereIn('status', [
                        ChargingSession::STATUS_PENDING,
                        ChargingSession::STATUS_INITIATING,
                    ])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($session === null) {
                    Log::info('OcppOperationsService: RemoteStart accepted with no pending local session — operator-initiated start.', [
                        'chargeBoxId'  => $request->chargeBoxId,
                        'connectorId'  => $request->connectorId,
                        'steveTxnId'   => $steveTxnId,
                    ]);
                    return;
                }

                $session->status = ChargingSession::STATUS_ACTIVE;
                if (!$session->started_at) {
                    $session->started_at = now();
                }
                if ($steveTxnId !== null) {
                    $session->steve_transaction_id = (string) $steveTxnId;
                }
                $session->ocpp_tag = $request->ocppTag;
                $session->steve_response = $response;
                $session->save();
            });
        } catch (Throwable $e) {
            // State-machine writes must never break the command response — log
            // and continue. Operator already saw the SteVe outcome.
            Log::warning('OcppOperationsService: Failed to promote session to ACTIVE', [
                'chargeBoxId' => $request->chargeBoxId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Public reconciliation entry point — used by external pollers
     * (ReconcileChargingSessionsJob) when SteVe reports a transaction has
     * stopped without EVON having dispatched a remoteStop. Mirrors the
     * post-accepted-stop logic the private remoteStop path takes, but
     * accepts the SteVe transaction GET response shape (with `stopTimestamp`,
     * `stopValue`, `stopReason`) and writes those fields too.
     */
    public function markSessionStoppedFromSteve(ChargingSession $session, array $steveTransactionData): void
    {
        try {
            DB::transaction(function () use ($session, $steveTransactionData) {
                $fresh = ChargingSession::lockForUpdate()->find($session->id);
                if ($fresh === null || $this->isTerminal($fresh->status)) {
                    return;
                }

                $stopTimestamp = $steveTransactionData['stopTimestamp'] ?? null;
                $stopValue     = $steveTransactionData['stopValue']     ?? null;
                $stopReason    = $steveTransactionData['stopReason']    ?? null;

                $fresh->status              = ChargingSession::STATUS_STOPPED;
                $fresh->stopped_at          = $fresh->stopped_at ?: ($stopTimestamp ? \Carbon\Carbon::parse($stopTimestamp) : now());
                $fresh->steve_stop_response = $steveTransactionData;
                $fresh->stop_reason         = $fresh->stop_reason ?: ($stopReason ?? 'steve_reconciled');

                if (Schema::hasColumn('charging_sessions', 'meter_stop') && $stopValue !== null && $fresh->meter_stop === null) {
                    $fresh->meter_stop = (int) $stopValue;
                }

                $fresh->save();
            });
        } catch (Throwable $e) {
            Log::warning('OcppOperationsService: failed to mark session STOPPED from SteVe reconciliation', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    protected function transitionSessionToStopped(ChargingSession $session, array $response): void
    {
        try {
            DB::transaction(function () use ($session, $response) {
                $fresh = ChargingSession::lockForUpdate()->find($session->id);
                if ($fresh === null || $this->isTerminal($fresh->status)) {
                    return;
                }
                $fresh->status = ChargingSession::STATUS_STOPPED;
                $fresh->stopped_at = $fresh->stopped_at ?: now();
                $fresh->steve_stop_response = $response;
                $fresh->stop_reason = $fresh->stop_reason ?: 'remote_stop';
                $fresh->save();
            });
        } catch (Throwable $e) {
            Log::warning('OcppOperationsService: Failed to transition session to STOPPED', [
                'sessionId' => $session->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    protected function extractTransactionId(array $response): ?int
    {
        $candidates = [
            $response['transaction_id'] ?? null,
            $response['data']['transaction']['id'] ?? null,
            $response['data']['transactionId'] ?? null,
            $response['data']['transaction_id'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }
        return null;
    }

    /**
     * Resolve a SteVe chargeBoxId to the local charging_points.id. Tries
     * steve_charging_point_id first (canonical mapping), then charge_box_id.
     */
    protected function resolveLocalChargingPointId(string $chargeBoxId): ?int
    {
        if (!Schema::hasTable('charging_points')) {
            return null;
        }

        $query = ChargingPoint::query();
        if (Schema::hasColumn('charging_points', 'steve_charging_point_id')) {
            $query->where('steve_charging_point_id', $chargeBoxId);
        }
        if (Schema::hasColumn('charging_points', 'charge_box_id')) {
            $query->orWhere('charge_box_id', $chargeBoxId);
        }

        return $query->value('id');
    }

    /**
     * Persist a ChargePointCommand audit row when the migration is in place.
     * Best-effort — schema-less deployments don't fail the command flow.
     */
    protected function recordCommandAudit(string $command, string $chargeBoxId, array $params, bool $success, array $response): void
    {
        if (!Schema::hasTable('charge_point_commands')) {
            return;
        }

        try {
            \App\Models\ChargePointCommand::create([
                'charge_box_id' => $chargeBoxId,
                'command'       => $command,
                'params'        => $params,
                'status'        => $success ? 'accepted' : 'rejected',
                'response'      => $response,
                'triggered_by'  => auth()->id(),
            ]);
        } catch (Throwable $e) {
            Log::warning('OcppOperationsService: Failed to write command audit', [
                'command'     => $command,
                'chargeBoxId' => $chargeBoxId,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
