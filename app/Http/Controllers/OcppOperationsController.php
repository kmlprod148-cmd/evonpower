<?php

namespace App\Http\Controllers;

use App\DTO\OCPP\AvailabilityTypeEnum;
use App\DTO\OCPP\ChangeAvailabilityRequestDTO;
use App\DTO\OCPP\LockConnectorRequestDTO;
use App\DTO\OCPP\ResetTypeEnum;
use App\DTO\OCPP\ResetRequestDTO;
use App\DTO\OCPP\UnlockConnectorRequestDTO;
use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\Http\Requests\Ocpp\RemoteStartRequest;
use App\Http\Requests\Ocpp\RemoteStopRequest;
use App\Http\Requests\Ocpp\ResetChargingPointRequest;
use App\Http\Requests\Ocpp\UnlockConnectorRequest;
use App\Http\Responses\ApiEnvelope;
use App\Models\ChargingPoint;
use App\Services\OcppOperationsService;
use App\Services\SteVe\OcppTagResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller BFF pour les opérations OCPP
 * 
 * Expose les endpoints pour les commandes OCPP vers les bornes de recharge.
 * Ce controller agit comme Backend-For-Frontend (BFF) pour le frontend.
 */
class OcppOperationsController extends Controller
{
    public function __construct(
        protected OcppOperationsService $ocppService,
        protected OcppTagResolver $tagResolver,
    ) {
        // Bug #10: $this->middleware() in controller constructors was removed in
        // Laravel 11 — the call was a no-op. Auth is gated at the route level
        // via `auth:sanctum` in routes/api.php. Do not re-add it here.
    }

    // =========================================================================
    // P3: shared envelope helpers — every error response on this controller
    // goes through one of these to keep the wire shape consistent.
    // =========================================================================

    private function notFound(string $msg = 'Point de charge non trouvé'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($msg, 'charge_point_not_found'), 404);
    }

    private function unauthorized(string $msg = 'Non autorisé'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($msg, 'unauthorized'), 403);
    }

    private function unconfigured(string $msg = 'Point de charge non configuré pour SteVe'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($msg, 'charge_point_unconfigured'), 400);
    }

    private function validationFailed(string $msg, array $errors = []): JsonResponse
    {
        return response()->json(ApiEnvelope::validation($msg, $errors), 422);
    }

    private function serverError(string $msg, string $code = 'internal_error'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($msg, $code), 500);
    }

    private function tagUnavailable(string $msg = 'Aucun tag OCPP disponible pour démarrer la session.'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($msg, 'ocpp_tag_unavailable'), 422);
    }

    // =========================================================================
    // CHANGE AVAILABILITY ENDPOINTS
    // =========================================================================

    /**
     * Change la disponibilité d'un connecteur
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/availability
     * 
     * Body: { "type": "Operative" | "Inoperative" }
     */
    public function changeConnectorAvailability(
        Request $request,
        int $chargingPointId,
        int $connectorId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Autorisation
            $this->authorize('update', $chargingPoint);

            // Validation
            $validated = $request->validate([
                'type' => 'required|string|in:Operative,Inoperative,operative,inoperative',
            ]);

            $type = AvailabilityTypeEnum::fromString($validated['type']);

            if (!$type) {
                return $this->validationFailed('Type de disponibilité invalide', ['type' => ['Type de disponibilité invalide']]);
            }

            $response = $this->ocppService->changeAvailabilityForChargingPoint(
                $chargingPoint,
                $type,
                $connectorId
            );

            return response()->json(
                $response->toApiResponse(),
                $response->success ? 200 : 400
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationFailed('Données invalides', $e->errors());
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: changeConnectorAvailability failed', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du changement de disponibilité', 'availability_change_failed');
        }
    }

    /**
     * Active un connecteur (le rend opérationnel)
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/enable
     */
    public function enableConnector(
        Request $request,
        int $chargingPointId,
        int $connectorId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Autorisation
            $this->authorize('update', $chargingPoint);

            $response = $this->ocppService->changeAvailabilityForChargingPoint(
                $chargingPoint,
                AvailabilityTypeEnum::OPERATIVE,
                $connectorId
            );

            return response()->json(
                $response->toApiResponse(),
                $response->success ? 200 : 400
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: enableConnector failed', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de l\'activation du connecteur', 'connector_enable_failed');
        }
    }

    /**
     * Désactive un connecteur (le rend inopérationnel)
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/disable
     */
    public function disableConnector(
        Request $request,
        int $chargingPointId,
        int $connectorId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Autorisation
            $this->authorize('update', $chargingPoint);

            $response = $this->ocppService->changeAvailabilityForChargingPoint(
                $chargingPoint,
                AvailabilityTypeEnum::INOPERATIVE,
                $connectorId
            );

            return response()->json(
                $response->toApiResponse(),
                $response->success ? 200 : 400
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: disableConnector failed', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de la désactivation du connecteur', 'connector_disable_failed');
        }
    }

    /**
     * Change la disponibilité de toute la borne
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/availability
     * 
     * Body: { "type": "Operative" | "Inoperative" }
     */
    public function changeChargePointAvailability(
        Request $request,
        int $chargingPointId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);

            // Autorisation
            $this->authorize('update', $chargingPoint);

            // Validation
            $validated = $request->validate([
                'type' => 'required|string|in:Operative,Inoperative,operative,inoperative',
            ]);

            $type = AvailabilityTypeEnum::fromString($validated['type']);

            if (!$type) {
                return $this->validationFailed('Type de disponibilité invalide', ['type' => ['Type de disponibilité invalide']]);
            }

            // connectorId = 0 signifie toute la borne
            $response = $this->ocppService->changeAvailabilityForChargingPoint(
                $chargingPoint,
                $type,
                0
            );

            return response()->json(
                $response->toApiResponse(),
                $response->success ? 200 : 400
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationFailed('Données invalides', $e->errors());
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: changeChargePointAvailability failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du changement de disponibilité', 'availability_change_failed');
        }
    }

    /**
     * Active toute la borne
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/enable
     */
    public function enableChargePoint(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $response = $this->ocppService->enableChargePoint($chargeBoxId);

            return response()->json(
                $response->toApiResponse(),
                $response->success ? 200 : 400
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: enableChargePoint failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de l\'activation de la borne', 'charge_point_enable_failed');
        }
    }

    /**
     * Désactive toute la borne
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/disable
     */
    public function disableChargePoint(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $response = $this->ocppService->disableChargePoint($chargeBoxId);

            return response()->json(
                $response->toApiResponse(),
                $response->success ? 200 : 400
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: disableChargePoint failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de la désactivation de la borne', 'charge_point_disable_failed');
        }
    }

    // =========================================================================
    // OTHER OCPP OPERATIONS
    // =========================================================================

    /**
     * Réinitialise la borne (legacy - utilise l'ancienne signature)
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/reset
     * 
     * Body: { "hard": true | false }
     */
    public function resetChargePointLegacy(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $hard = $request->boolean('hard', false);
            $result = $this->ocppService->resetChargePoint($chargeBoxId, $hard);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: resetChargePoint failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de la réinitialisation', 'reset_failed');
        }
    }

    /**
     * Déverrouille un connecteur (DTO-based)
     *
     * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/unlock
     */
    public function unlockConnector(
        Request $request,
        int $chargingPointId,
        int $connectorId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $dto = new UnlockConnectorRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
            ]);

            $response = $this->ocppService->unlockConnector($dto);

            return response()->json($response->toApiResponse(), $response->isUnlocked() ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: unlockConnector failed', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du déverrouillage', 'unlock_failed');
        }
    }

    /**
     * Blocks a connector for new sessions via ChangeAvailability(Inoperative).
     *
     * OCPP 1.6 has no physical LockConnector command; this endpoint intentionally
     * exposes the operational lock operators expect in the EVON UI.
     */
    public function lockConnector(
        Request $request,
        int $chargingPointId,
        int $connectorId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $dto = new LockConnectorRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
            ]);

            $response = $this->ocppService->lockConnector($dto);

            return response()->json($response->toApiResponse(), $response->isLocked() ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: lockConnector failed', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du verrouillage', 'lock_failed');
        }
    }

    /**
     * Vide le cache de la borne
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/clear-cache
     */
    public function clearCache(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $result = $this->ocppService->clearCache($chargeBoxId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: clearCache failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du vidage du cache', 'clear_cache_failed');
        }
    }

    // =========================================================================
    // RESET ENDPOINTS
    // =========================================================================

    /**
     * Réinitialise la borne
     * 
     * POST /api/ocpp/charging-points/{chargingPoint}/reset
     * Body: { "type": "Soft" | "Hard" }
     */
    public function resetChargePoint(ResetChargingPointRequest $request, int $chargingPointId): JsonResponse
    {
        try {
            $validated = $request->validated();

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            // Bug #4: feed the DTO the model's chargeBoxId, not the request body.
            $dto = new ResetRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'type'        => $validated['type'],
            ]);

            $response = $this->ocppService->resetChargePoint($dto);

            return response()->json($response->toApiResponse(), $response->isSuccess() ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let Laravel render the standard 422 envelope rather than swallowing this
            // into the generic catch below (which returned HTTP 500).
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: resetChargePoint failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de la réinitialisation', 'reset_failed');
        }
    }

    /**
     * Reset a charging point (alias for resetChargePoint)
     *
     * POST /api/ocpp/charging-points/{chargingPoint}/reset
     * Body: { "type": "Soft" | "Hard" }
     */
    public function reset(ResetChargingPointRequest $request, int $chargingPointId): JsonResponse
    {
        return $this->resetChargePoint($request, $chargingPointId);
    }

    /**
     * Reboots a charging point. Defaults to Soft reset when the body omits type.
     *
     * POST /api/ocpp/charging-points/{chargingPoint}/reboot
     * Body: { "type": "Soft" | "Hard" } optional
     */
    public function reboot(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => ['sometimes', 'string', 'in:Soft,Hard,soft,hard'],
            ]);

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $dto = new ResetRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'type'        => $validated['type'] ?? 'Soft',
            ]);

            $response = $this->ocppService->resetChargePoint($dto);

            return response()->json($response->toApiResponse(), $response->isSuccess() ? 200 : 400);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationFailed('Données invalides', $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: reboot failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du redémarrage', 'reboot_failed');
        }
    }

    // =========================================================================
    // UNLOCK ENDPOINTS
    // =========================================================================
    //
    // OCPP 1.6 has UnlockConnector, but not a physical LockConnector command.
    // This controller exposes "lock" as an operational lock implemented with
    // ChangeAvailability(Inoperative), which prevents new charging sessions.

    /**
     * Déverrouille un connecteur (legacy)
     *
     * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/unlock
     */
    public function unlockConnectorLegacy(
        Request $request,
        int $chargingPointId,
        int $connectorId
    ): JsonResponse {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            $dto = new UnlockConnectorRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
            ]);

            $response = $this->ocppService->unlockConnector($dto);

            return response()->json($response->toApiResponse(), $response->isUnlocked() ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: unlockConnector failed', [
                'chargingPointId' => $chargingPointId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du déverrouillage', 'unlock_failed');
        }
    }

    // =========================================================================
    // REMOTE START/STOP ENDPOINTS
    // =========================================================================

    /**
     * Démarre une session de charge à distance
     *
     * POST /api/ocpp/charging-points/{chargingPoint}/remote-start
     * Body: { "connectorId": 1, "ocppTag": "TAG-001", "idempotencyKey"?: "abc" }
     *
     * Status codes:
     *   200 — SteVe accepted the command
     *   400 — SteVe rejected the command (charger offline, connector faulted, etc.)
     *   404 — charging point row missing locally
     *   409 — a non-terminal ChargingSession already occupies the (cp, connector) slot
     *   422 — request validation failure
     *   503 — SteVe base URL not configured
     */
    public function remoteStart(RemoteStartRequest $request, int $chargingPointId): JsonResponse
    {
        try {
            // Bug #1: FormRequest validation fires before this method body, so empty/bad
            // payloads short-circuit to 422 before any chargeBoxId check.
            $validated = $request->validated();

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            // Apply defaults documented in RemoteStartRequest:
            //   - connectorId → 1 (handled by FormRequest::prepareForValidation)
            //   - ocppTag     → resolved via SteVe /ocppTags lookup (here, after
            //                   validation so we don't make a SteVe round-trip
            //                   for requests that would 422 anyway).
            $connectorId = (int) ($validated['connectorId'] ?? 1);
            $ocppTag     = $this->tagResolver->resolve($validated['ocppTag'] ?? null);

            if ($ocppTag === null) {
                return $this->tagUnavailable();
            }

            $dto = new RemoteStartRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'ocppTag'     => $ocppTag,
            ]);

            $response = $this->ocppService->remoteStart($dto, [
                'idempotencyKey'  => $validated['idempotencyKey'] ?? null,
                'ocppTagResolved' => !isset($validated['ocppTag']) || $validated['ocppTag'] === null,
            ]);

            return response()->json(
                $response->toApiResponse(),
                $this->remoteOperationStatusCode($response->isAccepted(), $response->errorCode),
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationFailed('Données invalides', $e->errors());
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: remoteStart failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors du démarrage à distance', 'remote_start_failed');
        }
    }

    /**
     * Arrête une session de charge à distance
     *
     * POST /api/ocpp/charging-points/{chargingPoint}/remote-stop
     * Body: { "transactionId": <int>, "idempotencyKey"?: "abc" }
     *
     * Status codes mirror remoteStart; 409 is returned when the local
     * ChargingSession matching transactionId is already terminal.
     */
    public function remoteStop(RemoteStopRequest $request, int $chargingPointId): JsonResponse
    {
        try {
            $validated = $request->validated();

            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $this->authorize('update', $chargingPoint);

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            if (!$chargeBoxId) {
                return $this->unconfigured();
            }

            // Bug #4: build the DTO from the model's chargeBoxId (the URL is the source
            // of truth), not from the request body which fromRequest() was reading.
            $dto = new RemoteStopRequestDTO([
                'chargeBoxId'   => $chargeBoxId,
                'transactionId' => $validated['transactionId'] ?? null,
            ]);

            $response = $this->ocppService->remoteStop($dto, [
                'idempotencyKey' => $validated['idempotencyKey'] ?? null,
            ]);

            return response()->json(
                $response->toApiResponse(),
                $this->remoteOperationStatusCode($response->isAccepted(), $response->errorCode),
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized();
        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Bubble up so the exception's render() method emits HTTP 503 (Bug #1.4).
            throw $e;
        } catch (\Exception $e) {
            Log::error('OcppOperationsController: remoteStop failed', [
                'chargingPointId' => $chargingPointId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Erreur lors de l\'arrêt à distance', 'remote_stop_failed');
        }
    }

    /**
     * Map an OCPP remote-{start,stop} outcome to its HTTP status code.
     * Conflict cases (errorCode = session_already_*) get 409 — a duplicate
     * dispatch is semantically different from a charger-side rejection.
     */
    private function remoteOperationStatusCode(bool $accepted, ?string $errorCode): int
    {
        if ($accepted) {
            return 200;
        }
        if (in_array($errorCode, ['session_already_active', 'session_already_terminated', 'charger_not_connected'], true)) {
            return 409;
        }
        return 400;
    }
}
