<?php

namespace App\Http\Controllers;

use App\Services\SteVeApiEndpointService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour gérer tous les groupes d'endpoints SteVe API
 */
class SteVeApiEndpointController extends Controller
{
    protected SteVeApiEndpointService $service;

    public function __construct(SteVeApiEndpointService $service)
    {
        $this->service = $service;
    }

    /**
     * Obtenir la liste de tous les groupes d'endpoints disponibles
     */
    public function getAvailableEndpointGroups(): JsonResponse
    {
        try {
            $groups = $this->service->getAvailableEndpointGroups();
            
            return response()->json([
                'success' => true,
                'data' => $groups,
                'message' => 'Groupes d\'endpoints récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('SteVeApiEndpointController: Failed to get endpoint groups', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================================================
    // GROUPE 1: CHARGE POINT MANAGEMENT
    // ========================================================================

    /**
     * Créer un point de charge
     * POST /api/steve/charge-points
     */
    public function createChargePoint(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chargeBoxId' => 'required|string',
            'ocppProtocol' => 'required|string|in:ocpp12,ocpp15,ocpp16',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->createChargePoint($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Obtenir les détails d'un point de charge
     * GET /api/steve/charge-points/{id}
     */
    public function getChargePoint(string $id): JsonResponse
    {
        $result = $this->service->getChargePoint($id);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Mettre à jour un point de charge
     * PUT /api/steve/charge-points/{id}
     */
    public function updateChargePoint(Request $request, string $id): JsonResponse
    {
        $result = $this->service->updateChargePoint($id, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Supprimer un point de charge
     * DELETE /api/steve/charge-points/{id}
     */
    public function deleteChargePoint(string $id): JsonResponse
    {
        $result = $this->service->deleteChargePoint($id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Lister tous les points de charge
     * GET /api/steve/charge-points
     */
    public function listChargePoints(Request $request): JsonResponse
    {
        $result = $this->service->listChargePoints($request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ========================================================================
    // GROUPE 2: CONNECTOR STATUS & CONTROL
    // ========================================================================

    /**
     * Obtenir le statut d'un connecteur
     * GET /api/steve/connectors/status/{chargePointId}
     */
    public function getConnectorStatus(string $chargePointId, Request $request): JsonResponse
    {
        $connectorId = $request->query('connector_id');
        $result = $this->service->getConnectorStatus($chargePointId, $connectorId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * RemoteStartTransaction
     * POST /api/steve/commands/remote-start-transaction
     */
    public function remoteStartTransaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'connector_id' => 'required|integer|min:1',
            'id_tag' => 'required|string',
            'charging_profile' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->remoteStartTransaction(
            $request->charge_point_id,
            $request->all()
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * RemoteStopTransaction
     * POST /api/steve/commands/remote-stop-transaction
     */
    public function remoteStopTransaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->remoteStopTransaction(
            $request->charge_point_id,
            $request->transaction_id
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * UnlockConnector
     * POST /api/steve/commands/unlock-connector
     */
    public function unlockConnector(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'connector_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->unlockConnector(
            $request->charge_point_id,
            $request->connector_id
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ChangeAvailability
     * POST /api/steve/commands/change-availability
     */
    public function changeAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'connector_id' => 'required|integer',
            'type' => 'required|string|in:Inoperative,Operative'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->changeAvailability(
            $request->charge_point_id,
            $request->connector_id,
            $request->type
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ChangeConfiguration
     * POST /api/steve/commands/change-configuration
     */
    public function changeConfiguration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'key' => 'required|string',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->changeConfiguration(
            $request->charge_point_id,
            $request->key,
            $request->value
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ClearCache
     * POST /api/steve/commands/clear-cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->clearCache($request->charge_point_id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Reset
     * POST /api/steve/commands/reset
     */
    public function reset(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'type' => 'nullable|string|in:Soft,Hard'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->reset(
            $request->charge_point_id,
            $request->input('type', 'Soft')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ========================================================================
    // GROUPE 3: TRANSACTIONS & SESSIONS
    // ========================================================================

    /**
     * Obtenir la liste des transactions
     * GET /api/steve/transactions
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $result = $this->service->getTransactions($request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Obtenir les détails d'une transaction
     * GET /api/steve/transactions/{id}
     */
    public function getTransaction(string $id): JsonResponse
    {
        $result = $this->service->getTransaction($id);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Obtenir les valeurs de compteur
     * GET /api/steve/transactions/{id}/meter-values
     */
    public function getMeterValues(string $id, Request $request): JsonResponse
    {
        $result = $this->service->getMeterValues($id, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ========================================================================
    // GROUPE 4: CONFIGURATION & MAINTENANCE
    // ========================================================================

    /**
     * GetDiagnostics
     * POST /api/steve/commands/get-diagnostics
     */
    public function getDiagnostics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->getDiagnostics(
            $request->charge_point_id,
            $request->except('charge_point_id')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GetLog
     * POST /api/steve/commands/get-log
     */
    public function getLog(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'log_type' => 'required|string',
            'request_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->getLog(
            $request->charge_point_id,
            $request->except('charge_point_id')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * UpdateFirmware
     * POST /api/steve/commands/update-firmware
     */
    public function updateFirmware(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'location' => 'required|string|url',
            'retrieve_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->updateFirmware(
            $request->charge_point_id,
            $request->location,
            $request->retrieve_date
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GetConfiguration
     * POST /api/steve/commands/get-configuration
     */
    public function getConfiguration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'keys' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->getConfiguration(
            $request->charge_point_id,
            $request->input('keys', [])
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ClearChargingProfile
     * POST /api/steve/commands/clear-charging-profile
     */
    public function clearChargingProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->clearChargingProfile(
            $request->charge_point_id,
            $request->except('charge_point_id')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * SetChargingProfile
     * POST /api/steve/commands/set-charging-profile
     */
    public function setChargingProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'charging_profile' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->setChargingProfile(
            $request->charge_point_id,
            $request->charging_profile
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GetCompositeSchedule
     * POST /api/steve/commands/get-composite-schedule
     */
    public function getCompositeSchedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'connector_id' => 'required|integer|min:1',
            'duration' => 'required|integer|min:1',
            'charging_rate_unit' => 'nullable|string|in:W,A'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->getCompositeSchedule(
            $request->charge_point_id,
            $request->connector_id,
            $request->duration,
            $request->charging_rate_unit
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ========================================================================
    // GROUPE 5: USERS / AUTHENTICATION / TAG / RESERVATION
    // ========================================================================

    /**
     * Lister les utilisateurs
     * GET /api/steve/users
     */
    public function listUsers(Request $request): JsonResponse
    {
        $result = $this->service->listUsers($request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Obtenir un utilisateur
     * GET /api/steve/users/{id}
     */
    public function getUser(string $id): JsonResponse
    {
        $result = $this->service->getUser($id);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Créer un utilisateur
     * POST /api/steve/users
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idTag' => 'required|string',
            'parentIdTag' => 'nullable|string',
            'expiryDate' => 'nullable|date',
            'blocked' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->createUser($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Mettre à jour un utilisateur
     * PUT /api/steve/users/{id}
     */
    public function updateUser(Request $request, string $id): JsonResponse
    {
        $result = $this->service->updateUser($id, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Supprimer un utilisateur
     * DELETE /api/steve/users/{id}
     */
    public function deleteUser(string $id): JsonResponse
    {
        $result = $this->service->deleteUser($id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * ReserveNow
     * POST /api/steve/commands/reserve-now
     */
    public function reserveNow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'connector_id' => 'required|integer|min:0',
            'id_tag' => 'required|string',
            'expiry_date' => 'required|date|after:now',
            'parent_id_tag' => 'nullable|string',
            'reservation_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->reserveNow(
            $request->charge_point_id,
            $request->all()
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * CancelReservation
     * POST /api/steve/commands/cancel-reservation
     */
    public function cancelReservation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'reservation_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->cancelReservation(
            $request->charge_point_id,
            $request->reservation_id
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * GetLocalListVersion
     * POST /api/steve/commands/get-local-list-version
     */
    public function getLocalListVersion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->getLocalListVersion($request->charge_point_id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * SendLocalList
     * POST /api/steve/commands/send-local-list
     */
    public function sendLocalList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charge_point_id' => 'required|string',
            'list_version' => 'required|integer|min:0',
            'update_type' => 'required|string|in:Full,Differential',
            'local_authorization_list' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->service->sendLocalList(
            $request->charge_point_id,
            $request->list_version,
            $request->update_type,
            $request->input('local_authorization_list', [])
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}

