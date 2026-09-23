<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Services\AdvancedFeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdvancedFeeController extends Controller
{
    protected $advancedFeeCalculationService;

    public function __construct(AdvancedFeeCalculationService $advancedFeeCalculationService)
    {
        $this->advancedFeeCalculationService = $advancedFeeCalculationService;
    }

    /**
     * Calculate comprehensive fees for a charging point
     */
    public function calculateChargingPointFees(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'charging_point_id' => 'required|exists:charging_points,id',
                'base_amount' => 'nullable|numeric|min:0'
            ]);

            $chargingPoint = ChargingPoint::with(['integrator', 'partner', 'group'])->findOrFail($request->charging_point_id);
            $baseAmount = $request->input('base_amount', 100); // Default amount for testing

            $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                $chargingPoint, 
                $baseAmount
            );

            return response()->json([
                'success' => true,
                'message' => 'Frais calculés avec succès',
                'data' => $comprehensiveBreakdown
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating charging point fees', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des frais',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply comprehensive fees to a transaction
     */
    public function applyFeesToTransaction(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'transaction_id' => 'required|exists:transactions,id'
            ]);

            $transaction = Transaction::with(['chargingPoint'])->findOrFail($request->transaction_id);

            $success = $this->advancedFeeCalculationService->applyComprehensiveFeesToTransaction($transaction);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Frais appliqués à la transaction avec succès',
                    'transaction_id' => $transaction->id,
                    'updated_fees' => $transaction->fresh()->getCreatorFeesBreakdown()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'appliquer les frais à la transaction'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error applying fees to transaction', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'application des frais',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee calculation details for multiple charging points
     */
    public function calculateMultipleChargingPointsFees(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'charging_point_ids' => 'required|array',
                'charging_point_ids.*' => 'exists:charging_points,id',
                'base_amount' => 'nullable|numeric|min:0'
            ]);

            $chargingPointIds = $request->input('charging_point_ids');
            $baseAmount = $request->input('base_amount', 100);
            $results = [];

            foreach ($chargingPointIds as $chargingPointId) {
                $chargingPoint = ChargingPoint::with(['integrator', 'partner', 'group'])->find($chargingPointId);
                
                if ($chargingPoint) {
                    $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                        $chargingPoint, 
                        $baseAmount
                    );
                    
                    $results[] = [
                        'charging_point_id' => $chargingPointId,
                        'charging_point_name' => $chargingPoint->name,
                        'creator_type' => $comprehensiveBreakdown['creator_type'],
                        'creator_name' => $comprehensiveBreakdown['creator_info']['name'],
                        'admin_fees' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                        'integrator_fees' => $comprehensiveBreakdown['integrator_part']['fees']['total'],
                        'partner_fees' => $comprehensiveBreakdown['partner_part']['fees']['total'],
                        'operator_revenue' => $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'],
                        'total_fees' => $comprehensiveBreakdown['total_breakdown']['total_fees'],
                        'applies_admin' => $comprehensiveBreakdown['admin_part']['applies'],
                        'applies_integrator' => $comprehensiveBreakdown['integrator_part']['applies'],
                        'applies_partner' => $comprehensiveBreakdown['partner_part']['applies']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Calculs des frais terminés',
                'data' => [
                    'base_amount' => $baseAmount,
                    'results' => $results,
                    'summary' => [
                        'total_charging_points' => count($results),
                        'total_admin_fees' => array_sum(array_column($results, 'admin_fees')),
                        'total_integrator_fees' => array_sum(array_column($results, 'integrator_fees')),
                        'total_partner_fees' => array_sum(array_column($results, 'partner_fees')),
                        'total_operator_revenue' => array_sum(array_column($results, 'operator_revenue')),
                        'total_fees' => array_sum(array_column($results, 'total_fees'))
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating multiple charging points fees', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des frais multiples',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee calculation summary for a specific creator
     */
    public function getCreatorFeeSummary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'creator_type' => 'required|in:integrator,partner',
                'creator_id' => 'required|integer',
                'base_amount' => 'nullable|numeric|min:0'
            ]);

            $creatorType = $request->input('creator_type');
            $creatorId = $request->input('creator_id');
            $baseAmount = $request->input('base_amount', 100);

            // Get charging points created by this creator
            $chargingPoints = ChargingPoint::where($creatorType . '_id', $creatorId)
                ->with(['integrator', 'partner', 'group'])
                ->get();

            $results = [];
            $totalAdminFees = 0;
            $totalIntegratorFees = 0;
            $totalPartnerFees = 0;
            $totalOperatorRevenue = 0;

            foreach ($chargingPoints as $chargingPoint) {
                $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                    $chargingPoint, 
                    $baseAmount
                );

                $results[] = [
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'location' => $chargingPoint->location,
                    'status' => $chargingPoint->status,
                    'admin_fees' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                    'integrator_fees' => $comprehensiveBreakdown['integrator_part']['fees']['total'],
                    'partner_fees' => $comprehensiveBreakdown['partner_part']['fees']['total'],
                    'operator_revenue' => $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'],
                    'total_fees' => $comprehensiveBreakdown['total_breakdown']['total_fees'],
                    'applies_admin' => $comprehensiveBreakdown['admin_part']['applies'],
                    'applies_integrator' => $comprehensiveBreakdown['integrator_part']['applies'],
                    'applies_partner' => $comprehensiveBreakdown['partner_part']['applies']
                ];

                $totalAdminFees += $comprehensiveBreakdown['admin_part']['fees']['total'];
                $totalIntegratorFees += $comprehensiveBreakdown['integrator_part']['fees']['total'];
                $totalPartnerFees += $comprehensiveBreakdown['partner_part']['fees']['total'];
                $totalOperatorRevenue += $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Résumé des frais du créateur généré',
                'data' => [
                    'creator_type' => $creatorType,
                    'creator_id' => $creatorId,
                    'base_amount' => $baseAmount,
                    'charging_points_count' => count($results),
                    'results' => $results,
                    'summary' => [
                        'total_admin_fees' => $totalAdminFees,
                        'total_integrator_fees' => $totalIntegratorFees,
                        'total_partner_fees' => $totalPartnerFees,
                        'total_operator_revenue' => $totalOperatorRevenue,
                        'total_fees' => $totalAdminFees + $totalIntegratorFees + $totalPartnerFees,
                        'total_revenue' => $totalOperatorRevenue + $totalAdminFees + $totalIntegratorFees + $totalPartnerFees
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting creator fee summary', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du résumé des frais',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test fee calculation with different scenarios
     */
    public function testFeeScenarios(Request $request): JsonResponse
    {
        try {
            $baseAmount = $request->input('base_amount', 100);
            $scenarios = [];

            // Scenario 1: Charging point created by integrator
            $integratorChargingPoints = ChargingPoint::whereNotNull('integrator_id')
                ->with(['integrator', 'partner', 'group'])
                ->limit(3)
                ->get();

            foreach ($integratorChargingPoints as $chargingPoint) {
                $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                    $chargingPoint, 
                    $baseAmount
                );

                $scenarios[] = [
                    'scenario' => 'Station créée par intégrateur',
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'creator_type' => $comprehensiveBreakdown['creator_type'],
                    'creator_name' => $comprehensiveBreakdown['creator_info']['name'],
                    'admin_fees' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                    'admin_applies' => $comprehensiveBreakdown['admin_part']['applies'],
                    'admin_reason' => $comprehensiveBreakdown['admin_part']['reason'],
                    'integrator_fees' => $comprehensiveBreakdown['integrator_part']['fees']['total'],
                    'integrator_applies' => $comprehensiveBreakdown['integrator_part']['applies'],
                    'integrator_reason' => $comprehensiveBreakdown['integrator_part']['reason'],
                    'operator_revenue' => $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'],
                    'total_fees' => $comprehensiveBreakdown['total_breakdown']['total_fees']
                ];
            }

            // Scenario 2: Charging point created by partner
            $partnerChargingPoints = ChargingPoint::whereNotNull('partner_id')
                ->with(['integrator', 'partner', 'group'])
                ->limit(3)
                ->get();

            foreach ($partnerChargingPoints as $chargingPoint) {
                $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                    $chargingPoint, 
                    $baseAmount
                );

                $scenarios[] = [
                    'scenario' => 'Station créée par partenaire',
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'creator_type' => $comprehensiveBreakdown['creator_type'],
                    'creator_name' => $comprehensiveBreakdown['creator_info']['name'],
                    'admin_fees' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                    'admin_applies' => $comprehensiveBreakdown['admin_part']['applies'],
                    'admin_reason' => $comprehensiveBreakdown['admin_part']['reason'],
                    'integrator_fees' => $comprehensiveBreakdown['integrator_part']['fees']['total'],
                    'integrator_applies' => $comprehensiveBreakdown['integrator_part']['applies'],
                    'integrator_reason' => $comprehensiveBreakdown['integrator_part']['reason'],
                    'partner_fees' => $comprehensiveBreakdown['partner_part']['fees']['total'],
                    'partner_applies' => $comprehensiveBreakdown['partner_part']['applies'],
                    'partner_reason' => $comprehensiveBreakdown['partner_part']['reason'],
                    'operator_revenue' => $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'],
                    'total_fees' => $comprehensiveBreakdown['total_breakdown']['total_fees']
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Tests des scénarios de frais terminés',
                'data' => [
                    'base_amount' => $baseAmount,
                    'scenarios' => $scenarios,
                    'summary' => [
                        'total_scenarios' => count($scenarios),
                        'integrator_scenarios' => count(array_filter($scenarios, fn($s) => $s['creator_type'] === 'integrator')),
                        'partner_scenarios' => count(array_filter($scenarios, fn($s) => $s['creator_type'] === 'partner')),
                        'admin_fees_applied' => count(array_filter($scenarios, fn($s) => $s['admin_applies'])),
                        'integrator_fees_applied' => count(array_filter($scenarios, fn($s) => $s['integrator_applies'])),
                        'partner_fees_applied' => count(array_filter($scenarios, fn($s) => $s['partner_applies'] ?? false))
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing fee scenarios', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors des tests des scénarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
