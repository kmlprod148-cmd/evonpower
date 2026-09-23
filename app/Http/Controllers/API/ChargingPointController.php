<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Validator;
use App\Services\SteVeIntegrationService;
use Illuminate\Support\Facades\Log;

class ChargingPointController extends Controller
{
    /**
     * Get all charging points
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $chargingPoints = ChargingPoint::with(['pricingPlan', 'businessProfiles'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $chargingPoints
        ]);
    }

    /**
     * Get specific charging point
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::with(['pricingPlan', 'businessProfiles'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $chargingPoint
        ]);
    }

    /**
     * Assign pricing plan to charging point
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignPricingPlan(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $chargingPoint = ChargingPoint::findOrFail($id);
        $chargingPoint->pricing_plan_id = $request->pricing_plan_id;
        $chargingPoint->save();

        return response()->json([
            'success' => true,
            'message' => 'Pricing plan assigned successfully',
            'data' => $chargingPoint->load('pricingPlan')
        ]);
    }

    /**
     * Assign pricing plan to multiple charging points
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignPricingPlanBatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'charging_point_ids' => 'required|array',
            'charging_point_ids.*' => 'exists:charging_points,id',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        ChargingPoint::whereIn('id', $request->charging_point_ids)
            ->update(['pricing_plan_id' => $request->pricing_plan_id]);

        return response()->json([
            'success' => true,
            'message' => 'Pricing plan assigned to multiple charging points successfully'
        ]);
    }

    /**
     * Get compatible pricing plans for charging point
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getCompatiblePricingPlans(int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($id);
        
        // Get all pricing plans (you can add compatibility logic here)
        $pricingPlans = PricingPlan::all();

        return response()->json([
            'success' => true,
            'data' => $pricingPlans
        ]);
    }

    /**
     * Get business profiles for charging point
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getBusinessProfiles(int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::with('businessProfiles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $chargingPoint->businessProfiles
        ]);
    }

    /**
     * Get applied business profiles for charging point
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getAppliedBusinessProfiles(int $id): JsonResponse
    {
        $chargingPoint = ChargingPoint::with('businessProfiles')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $chargingPoint->businessProfiles
        ]);
    }

    /**
     * Get charge box ID for a charging point
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getChargeBoxId(int $id): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($id);

            // If charge_box_id exists, return it
            if ($chargingPoint->charge_box_id) {
                return response()->json([
                    'success' => true,
                    'charge_box_id' => $chargingPoint->charge_box_id,
                    'source' => 'database'
                ]);
            }

            // Otherwise generate one from the charging point
            $chargeBoxId = $chargingPoint->serial_number ?? "BORNE_{$chargingPoint->id}";

            return response()->json([
                'success' => true,
                'charge_box_id' => $chargeBoxId,
                'source' => 'generated'
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting charge box ID', [
                'charging_point_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving charge box ID: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connect charging point to SteVe server
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function connectToSteVe(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'charge_box_id' => 'required|string|max:255',
                'steve_server_url' => 'nullable|string'
            ]);

            $chargingPoint = ChargingPoint::findOrFail($id);
            $steveService = new SteVeIntegrationService();

            // Default SteVe server URL if not provided
            $steveServerUrl = $request->steve_server_url ?? 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';

            // Generate the full WebSocket URL for the charging point
            $websocketUrl = $steveServerUrl . $request->charge_box_id;

            // Update the charging point with the connection information
            $chargingPoint->update([
                'charge_box_id' => $request->charge_box_id,
                'steve_server_url' => $steveServerUrl,
                'websocket_url' => $websocketUrl,
                'status' => 'connecting',
                'last_connection_attempt' => now(),
            ]);

            // Test the connection
            $connectionResult = $steveService->connectChargingPoint($chargingPoint);

            Log::info("Charging point {$id} connected to SteVe", [
                'charge_box_id' => $request->charge_box_id,
                'websocket_url' => $websocketUrl
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Borne connectée avec succès au serveur SteVe',
                'data' => [
                    'charge_box_id' => $request->charge_box_id,
                    'websocket_url' => $websocketUrl,
                    'charging_point' => $chargingPoint->fresh(),
                    'connection_result' => $connectionResult
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error connecting charging point to SteVe", [
                'charging_point_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au serveur SteVe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect charging point from SteVe server
     *
     * @param int $id
     * @return JsonResponse
     */
    public function disconnectFromSteVe(int $id): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($id);
            $steveService = new SteVeIntegrationService();

            $result = $steveService->disconnectChargingPoint($chargingPoint);

            Log::info("Charging point {$id} disconnected from SteVe");

            return response()->json([
                'success' => true,
                'message' => 'Borne déconnectée avec succès du serveur SteVe',
                'data' => [
                    'charging_point' => $chargingPoint->fresh(),
                    'disconnect_result' => $result
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error disconnecting charging point from SteVe", [
                'charging_point_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion du serveur SteVe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SteVe connection status for a charging point
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getSteVeStatus(int $id): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($id);
            $steveService = new SteVeIntegrationService();

            // Get connection test results
            $connectionTest = $steveService->testConnection();

            return response()->json([
                'success' => true,
                'data' => [
                    'charging_point_id' => $id,
                    'charge_box_id' => $chargingPoint->charge_box_id,
                    'status' => $chargingPoint->status,
                    'websocket_url' => $chargingPoint->websocket_url,
                    'last_connection_attempt' => $chargingPoint->last_connection_attempt,
                    'steve_connection_status' => $chargingPoint->steve_connection_status,
                    'server_status' => $connectionTest
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting SteVe status", [
                'charging_point_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut SteVe: ' . $e->getMessage()
            ], 500);
        }
    }
}
