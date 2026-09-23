<?php

namespace App\Http\Controllers;

use App\Services\HierarchicalTransactionService;
use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class HierarchicalTransactionController extends Controller
{
    protected $hierarchicalTransactionService;

    public function __construct(HierarchicalTransactionService $hierarchicalTransactionService)
    {
        $this->hierarchicalTransactionService = $hierarchicalTransactionService;
    }

    /**
     * Traiter une transaction hiérarchique pour une session
     */
    public function processSessionTransaction(Request $request, int $sessionId): JsonResponse
    {
        try {
            $session = ChargingSession::findOrFail($sessionId);
            
            $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($session);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction hiérarchique traitée avec succès',
                    'data' => $result
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la transaction hiérarchique',
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la transaction hiérarchique', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier les soldes avant transaction
     */
    public function checkBalances(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            
            // Identifier la hiérarchie
            $hierarchy = $this->hierarchicalTransactionService->identifyHierarchy($chargingPointId);
            
            if (!$hierarchy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'identifier la hiérarchie'
                ], 400);
            }

            // Vérifier les soldes
            $balances = $this->hierarchicalTransactionService->verifyBalances($hierarchy);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'hierarchy' => [
                        'operator' => $hierarchy['operator'] ? $hierarchy['operator']->name : null,
                        'integrator' => $hierarchy['integrator'] ? $hierarchy['integrator']->name : null,
                        'partner' => $hierarchy['partner'] ? $hierarchy['partner']->name : null,
                    ],
                    'balances' => $balances
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification des soldes', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification des soldes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des transactions hiérarchiques
     */
    public function getTransactionHistory(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            
            $history = $this->hierarchicalTransactionService->getHierarchicalTransactionHistory(
                $chargingPointId, 
                $limit
            );
            
            return response()->json([
                'success' => true,
                'data' => $history
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simuler une transaction hiérarchique (pour tests)
     */
    public function simulateTransaction(Request $request, int $chargingPointId): JsonResponse
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            
            // Créer une session de test
            $testSession = new ChargingSession([
                'charging_point_id' => $chargingPointId,
                'energy_delivered' => $request->get('energy_delivered', 10.5),
                'duration' => $request->get('duration', 3600), // 1 heure
                'status' => 'completed'
            ]);
            
            $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($testSession);
            
            return response()->json([
                'success' => true,
                'message' => 'Simulation de transaction effectuée',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la simulation de transaction', [
                'charging_point_id' => $chargingPointId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la simulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}