<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientSpaceService;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur pour l'Espace Client
 */
class ClientSpaceController extends Controller
{
    public function __construct(
        protected ClientSpaceService $clientSpaceService
    ) {}

    /**
     * Obtenir les stations de recharge avec filtrage
     * 
     * GET /api/client/stations
     * 
     * @queryParam latitude float Latitude de la position
     * @queryParam longitude float Longitude de la position
     * @queryParam radius float Rayon de recherche en km (défaut: 25)
     * @queryParam connector_types array Types de connecteurs
     * @queryParam available_only boolean Stations disponibles uniquement
     * @queryParam min_power float Puissance minimale
     * @queryParam max_price_per_kwh float Prix max par kWh
     * @queryParam sort_by string Tri (distance, price, power)
     * @queryParam sort_order string Ordre (asc, desc)
     */
    public function getStations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'connector_types' => 'nullable|array',
            'connector_types.*' => 'string',
            'available_only' => 'nullable|boolean',
            'min_power' => 'nullable|numeric|min:0',
            'max_price_per_kwh' => 'nullable|numeric|min:0',
            'partner_ids' => 'nullable|array',
            'partner_ids.*' => 'integer',
            'integrator_ids' => 'nullable|array',
            'integrator_ids.*' => 'integer',
            'sort_by' => 'nullable|string|in:distance,price,power',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $stations = $this->clientSpaceService->getChargingStations($request->all());

            return response()->json([
                'success' => true,
                'data' => $stations,
                'count' => count($stations),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATIONS_FETCH_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une station
     * 
     * GET /api/client/stations/{id}
     */
    public function getStationDetails(int $id): JsonResponse
    {
        try {
            $station = $this->clientSpaceService->getStationDetails($id, auth()->user());

            if (!$station) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'STATION_NOT_FOUND',
                        'message' => 'Station non trouvée',
                    ],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $station,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATION_DETAILS_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Rechercher des stations
     * 
     * GET /api/client/stations/search
     * 
     * @queryParam q string required Texte de recherche
     */
    public function searchStations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = $this->clientSpaceService->searchStations(
                $request->input('q'),
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SEARCH_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Ajouter une station aux favoris
     * 
     * POST /api/client/favorites
     * 
     * @bodyParam station_id integer required ID de la station
     */
    public function addFavorite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'station_id' => 'required|integer|exists:charging_points,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $this->clientSpaceService->addFavorite($user, $request->input('station_id'));

            return response()->json([
                'success' => true,
                'message' => 'Station ajoutée aux favoris',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ADD_FAVORITE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Retirer une station des favoris
     * 
     * DELETE /api/client/favorites/{station_id}
     */
    public function removeFavorite(int $stationId): JsonResponse
    {
        try {
            $user = auth()->user();
            $this->clientSpaceService->removeFavorite($user, $stationId);

            return response()->json([
                'success' => true,
                'message' => 'Station retirée des favoris',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REMOVE_FAVORITE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Obtenir les favoris de l'utilisateur
     * 
     * GET /api/client/favorites
     */
    public function getFavorites(): JsonResponse
    {
        try {
            $favorites = $this->clientSpaceService->getUserFavorites(auth()->user());

            return response()->json([
                'success' => true,
                'data' => $favorites,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FAVORITES_FETCH_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des sessions
     * 
     * GET /api/client/sessions
     * 
     * @queryParam date_from string Date de début (Y-m-d)
     * @queryParam date_to string Date de fin (Y-m-d)
     * @queryParam status string Statut de la session
     * @queryParam charging_point_id integer ID de la station
     * @queryParam per_page integer Résultats par page
     */
    public function getSessionHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after:date_from',
            'status' => 'nullable|string|in:active,completed,cancelled',
            'charging_point_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $history = $this->clientSpaceService->getSessionHistory(
                auth()->user(),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $history['data'],
                'meta' => $history['meta'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'HISTORY_FETCH_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des sessions
     * 
     * GET /api/client/sessions/statistics
     * 
     * @queryParam start_date string Date de début (Y-m-d)
     * @queryParam end_date string Date de fin (Y-m-d)
     */
    public function getSessionStatistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $stats = $this->clientSpaceService->getSessionStatistics(
                auth()->user(),
                $request->has('start_date') ? \Carbon\Carbon::parse($request->start_date) : null,
                $request->has('end_date') ? \Carbon\Carbon::parse($request->end_date) : null
            );

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'STATISTICS_FETCH_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une session
     * 
     * GET /api/client/sessions/{id}
     */
    public function getSessionDetails(int $id): JsonResponse
    {
        try {
            $session = \App\Models\ChargingSession::with(['chargingPoint', 'pricingPlan'])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $session->id,
                    'transaction_id' => $session->transaction_id,
                    'charging_point' => [
                        'id' => $session->chargingPoint?->id,
                        'name' => $session->chargingPoint?->name,
                        'address' => $session->chargingPoint?->address,
                        'city' => $session->chargingPoint?->city,
                    ],
                    'status' => $session->status,
                    'start_timestamp' => $session->start_timestamp,
                    'stopped_at' => $session->stopped_at,
                    'duration_minutes' => $session->duration_minutes,
                    'energy_consumed_kwh' => $session->energy_consumed_kwh,
                    'total_cost' => $session->total_cost,
                    'currency' => $session->currency ?? 'EUR',
                    'meter_start' => $session->meter_start,
                    'meter_stop' => $session->meter_stop,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SESSION_NOT_FOUND',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
