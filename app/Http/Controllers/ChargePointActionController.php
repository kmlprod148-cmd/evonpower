<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\SteVeApiEndpointService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour les actions de commande sur les points de charge via l'API SteVe
 * 
 * Endpoints:
 * - POST /commands/{id}/start - Démarrer une transaction de charge
 * - POST /commands/{id}/stop - Arrêter une transaction de charge
 * - POST /commands/{id}/unlock - Débloquer un connecteur
 * - POST /commands/{id}/reset - Réinitialiser un point de charge
 */
class ChargePointActionController extends Controller
{
    protected SteVeApiEndpointService $steveService;

    public function __construct(SteVeApiEndpointService $steveService)
    {
        $this->middleware('auth:sanctum');
        $this->steveService = $steveService;
    }

    /**
     * Démarrer une transaction de charge
     * POST /commands/{id}/start
     * 
     * @param Request $request
     * @param int|string $id ID du ChargingPoint local
     * @return JsonResponse
     */
    public function start(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'connector_id' => 'required|integer|min:1',
            'id_tag' => 'required|string|max:20',
            'charging_profile' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation échouée'
            ], 422);
        }

        try {
            // Récupérer le point de charge
            $chargingPoint = ChargingPoint::findOrFail($id);

            // Vérifier que le point de charge a un ID SteVe configuré
            if (empty($chargingPoint->steve_charging_point_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge sans identifiant SteVe configuré',
                    'message' => 'Le point de charge n\'a pas d\'identifiant SteVe configuré'
                ], 400);
            }

            Log::info('ChargePointActionController: Starting charge', [
                'charging_point_id' => $id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                'charge_box_id' => $chargingPoint->charge_box_id,
                'connector_id' => $request->connector_id,
                'id_tag' => $request->id_tag
            ]);

            // Appeler le service SteVe
            $result = $this->steveService->remoteStartTransaction(
                $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id,
                [
                    'connector_id' => $request->connector_id,
                    'id_tag' => $request->id_tag,
                    'charging_profile' => $request->charging_profile
                ]
            );

            $statusCode = ($result['success'] ?? false)
                ? 200
                : (($result['error_code'] ?? null) === 'charger_not_connected' ? 409 : 400);

            return response()->json($result, $statusCode);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ChargePointActionController: Charging point not found', [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
                'message' => 'Le point de charge spécifié n\'existe pas'
            ], 404);

        } catch (\Exception $e) {
            Log::error('ChargePointActionController: Failed to start charge', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du démarrage de la charge'
            ], 500);
        }
    }

    /**
     * Arrêter une transaction de charge
     * POST /commands/{id}/stop
     * 
     * @param Request $request
     * @param int|string $id ID du ChargingPoint local
     * @return JsonResponse
     */
    public function stop(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation échouée'
            ], 422);
        }

        try {
            // Récupérer le point de charge
            $chargingPoint = ChargingPoint::findOrFail($id);

            // Vérifier que le point de charge a un ID SteVe configuré
            if (empty($chargingPoint->steve_charging_point_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge sans identifiant SteVe configuré',
                    'message' => 'Le point de charge n\'a pas d\'identifiant SteVe configuré'
                ], 400);
            }

            Log::info('ChargePointActionController: Stopping charge', [
                'charging_point_id' => $id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                'charge_box_id' => $chargingPoint->charge_box_id,
                'transaction_id' => $request->transaction_id
            ]);

            // Appeler le service SteVe
            $result = $this->steveService->remoteStopTransaction(
                $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id,
                $request->transaction_id
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ChargePointActionController: Charging point not found', [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
                'message' => 'Le point de charge spécifié n\'existe pas'
            ], 404);

        } catch (\Exception $e) {
            Log::error('ChargePointActionController: Failed to stop charge', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de l\'arrêt de la charge'
            ], 500);
        }
    }

    /**
     * Débloquer un connecteur
     * POST /commands/{id}/unlock
     * 
     * @param Request $request
     * @param int|string $id ID du ChargingPoint local
     * @return JsonResponse
     */
    public function unlock(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'connector_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation échouée'
            ], 422);
        }

        try {
            // Récupérer le point de charge
            $chargingPoint = ChargingPoint::findOrFail($id);

            // Vérifier que le point de charge a un ID SteVe configuré
            if (empty($chargingPoint->steve_charging_point_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge sans identifiant SteVe configuré',
                    'message' => 'Le point de charge n\'a pas d\'identifiant SteVe configuré'
                ], 400);
            }

            Log::info('ChargePointActionController: Unlocking connector', [
                'charging_point_id' => $id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                'connector_id' => $request->connector_id
            ]);

            // Appeler le service SteVe
            $result = $this->steveService->unlockConnector(
                $chargingPoint->steve_charging_point_id,
                $request->connector_id
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ChargePointActionController: Charging point not found', [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
                'message' => 'Le point de charge spécifié n\'existe pas'
            ], 404);

        } catch (\Exception $e) {
            Log::error('ChargePointActionController: Failed to unlock connector', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors du déblocage du connecteur'
            ], 500);
        }
    }

    /**
     * Réinitialiser un point de charge
     * POST /commands/{id}/reset
     * 
     * @param Request $request
     * @param int|string $id ID du ChargingPoint local
     * @return JsonResponse
     */
    public function reset(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:Soft,Hard'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation échouée'
            ], 422);
        }

        try {
            // Récupérer le point de charge
            $chargingPoint = ChargingPoint::findOrFail($id);

            // Vérifier que le point de charge a un ID SteVe configuré
            if (empty($chargingPoint->steve_charging_point_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Point de charge sans identifiant SteVe configuré',
                    'message' => 'Le point de charge n\'a pas d\'identifiant SteVe configuré'
                ], 400);
            }

            $resetType = $request->input('type', 'Soft');

            Log::info('ChargePointActionController: Resetting charge point', [
                'charging_point_id' => $id,
                'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
                'type' => $resetType
            ]);

            // Appeler le service SteVe
            $result = $this->steveService->reset(
                $chargingPoint->steve_charging_point_id,
                $resetType
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('ChargePointActionController: Charging point not found', [
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Point de charge non trouvé',
                'message' => 'Le point de charge spécifié n\'existe pas'
            ], 404);

        } catch (\Exception $e) {
            Log::error('ChargePointActionController: Failed to reset charge point', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la réinitialisation du point de charge'
            ], 500);
        }
    }
}

