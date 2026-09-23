<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Services\FinancialService;
use App\Services\OfferEstimator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function show($id): View
    {
        // Find the charging point by ID
        $chargingPoint = ChargingPoint::with(['pricingPlan', 'businessProfile'])->findOrFail($id);
        
        // Fallback to a default pricing plan if not set
        if (!$chargingPoint->pricingPlan) {
            $defaultPlan = PricingPlan::where('is_default', true)->first();
            if ($defaultPlan) {
                $chargingPoint->setRelation('pricingPlan', $defaultPlan);
            }
        }

        // Créer l'estimateur d'offre
        $offerEstimator = new OfferEstimator($chargingPoint);
        
        // Récupérer les informations de la borne et les limites
        $chargingPointInfo = $offerEstimator->getChargingPointInfo();
        $limitInfo = $offerEstimator->getReservationLimitInfo();

        return view('offers.show', [
            'chargingPoint' => $chargingPoint,
            'chargingPointInfo' => $chargingPointInfo,
            'limitInfo' => $limitInfo,
            'canReserve' => $offerEstimator->checkReservationLimit()
        ]);
    }

    public function calculateCost(Request $request, $charging_point_id): JsonResponse
    {
        $validatedData = $request->validate([
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'duration_minutes' => 'nullable|numeric|min:0',
            'energy_kwh' => 'nullable|numeric|min:0',
        ]);

        $pricingPlan = PricingPlan::findOrFail($validatedData['pricing_plan_id']);

        // Map the data to the format expected by FinancialService
        $mappedData = [
            'pricing_plan_id' => $validatedData['pricing_plan_id'],
        ];

        // Determine reservation type and value based on provided data
        if (isset($validatedData['duration_minutes']) && $validatedData['duration_minutes'] > 0) {
            $mappedData['reservation_type'] = 'minute';
            $mappedData['reservation_value'] = $validatedData['duration_minutes'];
        } elseif (isset($validatedData['energy_kwh']) && $validatedData['energy_kwh'] > 0) {
            $mappedData['reservation_type'] = 'kwh';
            $mappedData['reservation_value'] = $validatedData['energy_kwh'];
        }

        $financials = $this->financialService->calculateAndDistributeCommissions($pricingPlan, $mappedData);

        return response()->json([
            'success' => true,
            'estimations' => [
                'cost' => $financials['total_price'],
                'price_per_minute_cost' => $financials['price_details']['cost_by_time'],
                'price_per_kwh_cost' => $financials['price_details']['cost_by_energy'],
            ]
        ]);
    }

    /**
     * Nouvelle méthode pour l'estimation en temps réel
     */
    public function estimate(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'mode' => 'required|in:per_kw,per_min',
            'value' => 'required|numeric|min:0.1'
        ]);

        try {
            $chargingPoint = ChargingPoint::with(['pricingPlan', 'businessProfile'])->findOrFail($id);
            $offerEstimator = new OfferEstimator($chargingPoint);
            
            $estimation = $offerEstimator->estimate($validatedData);
            $limitInfo = $offerEstimator->getReservationLimitInfo();
            
            return response()->json([
                'success' => true,
                'estimation' => $estimation,
                'limitInfo' => $limitInfo,
                'canReserve' => $offerEstimator->checkReservationLimit()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}