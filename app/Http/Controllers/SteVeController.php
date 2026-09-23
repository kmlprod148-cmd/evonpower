<?php

namespace App\Http\Controllers;

use App\Services\SteVeApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SteVeController extends Controller
{
    protected SteVeApiService $steVeService;

    public function __construct(SteVeApiService $steVeService)
    {
        $this->steVeService = $steVeService;
    }

    /**
     * Get transactions for a charging point
     */
    public function getChargingPointTransactions(Request $request, string $chargingPointId): JsonResponse
    {
        try {
            $periodType = $request->get('period', 'LAST_30');
            $type = $request->get('type', 'ALL');
            
            $transactions = $this->steVeService->getTransactions([
                'chargeBoxId' => $chargingPointId,
                'periodType' => $periodType,
                'type' => $type
            ]);

            // Format transactions for frontend
            $formattedTransactions = array_map(function ($transaction) {
                return [
                    'id' => $transaction['id'],
                    'start_time' => $transaction['startTimestamp'],
                    'stop_time' => $transaction['stopTimestamp'] ?? null,
                    'duration' => $this->calculateDuration($transaction),
                    'energy_consumed' => $this->calculateEnergyConsumed($transaction),
                    'ocpp_tag' => $transaction['ocppIdTag'],
                    'connector_id' => $transaction['connectorId'],
                    'status' => $transaction['stopTimestamp'] ? 'completed' : 'active',
                    'stop_reason' => $transaction['stopReason'] ?? null,
                    'stop_actor' => $transaction['stopEventActor'] ?? null,
                ];
            }, $transactions);

            return response()->json([
                'success' => true,
                'data' => $formattedTransactions,
                'total' => count($formattedTransactions)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching charging point transactions', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions'
            ], 500);
        }
    }

    /**
     * Get transaction statistics for a charging point
     */
    public function getChargingPointStatistics(string $chargingPointId): JsonResponse
    {
        try {
            $stats = $this->steVeService->getTransactionStatistics($chargingPointId);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_sessions' => $stats['total_sessions'],
                    'active_sessions' => $stats['active_sessions'],
                    'completed_sessions' => $stats['completed_sessions'],
                    'total_energy' => round($stats['total_energy'], 2) . ' kWh',
                    'average_duration' => $this->formatDuration($stats['average_duration']),
                    'success_rate' => $stats['success_rate'] . '%'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching charging point statistics', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * Get OCPP tags for a charging point
     */
    public function getChargingPointTags(string $chargingPointId): JsonResponse
    {
        try {
            $tags = $this->steVeService->getChargingPointTags($chargingPointId);

            $formattedTags = array_map(function ($tag) {
                return [
                    'id' => $tag['ocppTagPk'],
                    'tag_id' => $tag['idTag'],
                    'blocked' => $tag['blocked'],
                    'in_transaction' => $tag['inTransaction'],
                    'active_transactions' => $tag['activeTransactionCount'],
                    'max_transactions' => $tag['maxActiveTransactionCount'],
                    'expiry_date' => $tag['expiryDate'],
                    'note' => $tag['note'],
                    'parent_tag' => $tag['parentIdTag']
                ];
            }, $tags);

            return response()->json([
                'success' => true,
                'data' => $formattedTags
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching charging point tags', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tags OCPP'
            ], 500);
        }
    }

    /**
     * Create a new OCPP tag
     */
    public function createOcppTag(Request $request): JsonResponse
    {
        $request->validate([
            'tag_id' => 'required|string|max:255',
            'max_transactions' => 'integer|min:1|max:10',
            'note' => 'string|max:500',
            'parent_tag' => 'string|max:255',
            'expiry_date' => 'date|after:today'
        ]);

        try {
            $user = Auth::user();
            
            $tagData = [
                'idTag' => $request->tag_id,
                'maxActiveTransactionCount' => $request->get('max_transactions', 1),
                'note' => $request->get('note', "EVON {$user->role} - {$user->name}"),
                'parentIdTag' => $request->get('parent_tag'),
                'expiryDate' => $request->get('expiry_date')
            ];

            $result = $this->steVeService->createOcppTag($tagData);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tag OCPP créé avec succès',
                    'data' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du tag OCPP'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error creating OCPP tag', [
                'tag_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du tag OCPP'
            ], 500);
        }
    }

    /**
     * Toggle tag block status
     */
    public function toggleTagBlock(Request $request, int $tagId): JsonResponse
    {
        $request->validate([
            'blocked' => 'required|boolean'
        ]);

        try {
            $result = $this->steVeService->toggleTagBlock($tagId, $request->blocked);

            if ($result) {
                $action = $request->blocked ? 'bloqué' : 'débloqué';
                return response()->json([
                    'success' => true,
                    'message' => "Tag OCPP {$action} avec succès",
                    'data' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du tag OCPP'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error toggling tag block status', [
                'tag_id' => $tagId,
                'blocked' => $request->blocked,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du tag OCPP'
            ], 500);
        }
    }

    /**
     * Delete an OCPP tag
     */
    public function deleteOcppTag(int $tagId): JsonResponse
    {
        try {
            $result = $this->steVeService->deleteOcppTag($tagId);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tag OCPP supprimé avec succès'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du tag OCPP'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error deleting OCPP tag', [
                'tag_id' => $tagId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du tag OCPP'
            ], 500);
        }
    }

    /**
     * Test SteVe API connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $isConnected = $this->steVeService->testConnection();

            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected ? 
                    'Connexion à SteVe API réussie' : 
                    'Impossible de se connecter à SteVe API'
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing SteVe API connection', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion'
            ], 500);
        }
    }

    /**
     * Remote action: Start charging
     */
    public function startCharging(Request $request, string $chargingPointId): JsonResponse
    {
        $request->validate([
            'tag_id' => 'required|string',
            'connector_id' => 'integer|min:1'
        ]);

        try {
            // Create a temporary tag for this charging session
            $user = Auth::user();
            $tempTag = $this->steVeService->createHierarchicalTag(
                $user->id,
                $user->role,
                $request->tag_id
            );

            if ($tempTag) {
                return response()->json([
                    'success' => true,
                    'message' => 'Recharge déclenchée avec succès',
                    'data' => [
                        'temp_tag' => $tempTag['idTag'],
                        'charging_point' => $chargingPointId,
                        'connector' => $request->get('connector_id', 1)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déclenchement de la recharge'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error starting charging', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déclenchement de la recharge'
            ], 500);
        }
    }

    /**
     * Remote action: Stop charging
     */
    public function stopCharging(string $chargingPointId): JsonResponse
    {
        try {
            // Get active transactions for this charging point
            $activeTransactions = $this->steVeService->getActiveTransactions($chargingPointId);

            if (empty($activeTransactions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune recharge active trouvée'
                ], 404);
            }

            // Block all active tags for this charging point
            $blockedTags = [];
            foreach ($activeTransactions as $transaction) {
                if (isset($transaction['ocppTagPk'])) {
                    $result = $this->steVeService->toggleTagBlock($transaction['ocppTagPk'], true);
                    if ($result) {
                        $blockedTags[] = $transaction['ocppIdTag'];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Recharge arrêtée avec succès',
                'data' => [
                    'blocked_tags' => $blockedTags,
                    'active_transactions' => count($activeTransactions)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error stopping charging', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'arrêt de la recharge'
            ], 500);
        }
    }

    /**
     * Calculate transaction duration in minutes
     */
    private function calculateDuration(array $transaction): ?int
    {
        if (!isset($transaction['startTimestamp']) || !isset($transaction['stopTimestamp'])) {
            return null;
        }

        $start = new \DateTime($transaction['startTimestamp']);
        $stop = new \DateTime($transaction['stopTimestamp']);
        
        return $start->diff($stop)->days * 24 * 60 + $start->diff($stop)->h * 60 + $start->diff($stop)->i;
    }

    /**
     * Calculate energy consumed in kWh
     */
    private function calculateEnergyConsumed(array $transaction): ?float
    {
        if (!isset($transaction['startValue']) || !isset($transaction['stopValue'])) {
            return null;
        }

        return round(floatval($transaction['stopValue']) - floatval($transaction['startValue']), 2);
    }

    /**
     * Format duration for display
     */
    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . 'h ' . $remainingMinutes . 'min';
    }
}
