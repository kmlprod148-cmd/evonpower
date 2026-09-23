<?php

namespace App\Http\Controllers\API;

use App\Core\Http\Controllers\ApiController;
use App\DTO\Pricing\PricingPlanCreateDTO;
use App\Services\PricingCalculationService;
use App\Services\PricingPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PricingPlanController extends ApiController
{
    protected PricingPlanService $pricingPlanService;

    protected PricingCalculationService $calculationService;

    public function __construct(
        PricingPlanService $pricingPlanService,
        PricingCalculationService $calculationService
    ) {
        $this->pricingPlanService = $pricingPlanService;
        $this->calculationService = $calculationService;
    }

    /**
     * Récupère la liste des plans tarifaires
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(\Illuminate\Http\Request $request = null)
    {
        $filters = $request ? $request->only(['is_active', 'integrator_id', 'partner_id', 'group_id', 'search']) : [];

        try {
            $pricingPlans = $this->pricingPlanService->getAllPricingPlans($filters);

            return $this->sendSuccess($pricingPlans);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la récupération des plans tarifaires', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les détails d'un plan tarifaire
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $pricingPlan = $this->pricingPlanService->getPricingPlan($id);

            if (! $pricingPlan) {
                return $this->sendNotFound('Plan tarifaire non trouvé');
            }

            return $this->sendSuccess($pricingPlan);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la récupération du plan tarifaire', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Crée un nouveau plan tarifaire
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $dto = PricingPlanCreateDTO::fromRequest($request);
            $pricingPlan = $this->pricingPlanService->createPricingPlan($dto);

            return $this->sendSuccess($pricingPlan, 'Plan tarifaire créé avec succès', 201);
        } catch (ValidationException $e) {
            return $this->sendError('Erreur de validation', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la création du plan tarifaire', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour un plan tarifaire
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        try {
            $dto = PricingPlanCreateDTO::fromRequest($request);
            $pricingPlan = $this->pricingPlanService->updatePricingPlan($id, $dto);

            return $this->sendSuccess($pricingPlan, 'Plan tarifaire mis à jour avec succès');
        } catch (ValidationException $e) {
            return $this->sendError('Erreur de validation', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la mise à jour du plan tarifaire', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprime un plan tarifaire
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $result = $this->pricingPlanService->deletePricingPlan($id);

            if (! $result) {
                return $this->sendNotFound('Plan tarifaire non trouvé');
            }

            return $this->sendSuccess(null, 'Plan tarifaire supprimé avec succès');
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la suppression du plan tarifaire', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère les offres tarifaires pour l'affichage public
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffers(Request $request)
    {
        $filters = $request->only(['integrator_id', 'partner_id', 'group_id']);

        try {
            $offers = $this->pricingPlanService->getPricingOffers($filters);

            return $this->sendSuccess($offers);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la récupération des offres tarifaires', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère le tarif applicable pour un point de recharge
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlanForChargingPoint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'charging_point_id' => 'required|exists:charging_points,id',
            'connector_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        try {
            $chargingPointId = $request->input('charging_point_id');
            $connectorId = $request->input('connector_id');

            $plan = $this->pricingPlanService->getPlanForChargingPoint($chargingPointId, $connectorId);

            if (! $plan) {
                return $this->sendNotFound('Aucun plan tarifaire trouvé');
            }

            return $this->sendSuccess($plan);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la récupération du plan tarifaire', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Calcule le prix estimé d'une recharge
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:pricing_plans,id',
            'energy' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:0',
            'start_time' => 'nullable|date',
            'power' => 'nullable|numeric|min:0',
            'customer_segment' => 'nullable|string|max:50',
            'location_zone' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        try {
            $plan = PricingPlan::with(['vatRate', 'planRates', 'additionalRates', 'ruleConditions'])
                ->findOrFail($request->input('plan_id'));

            $sessionData = [
                'duration_minutes' => $request->input('duration'),
                'energy_kwh' => $request->input('energy'),
                'start_time' => $request->input('start_time', now()),
            ];

            if ($request->has('power')) {
                $sessionData['power'] = $request->input('power');
            }
            if ($request->has('customer_segment')) {
                $sessionData['customer_segment'] = $request->input('customer_segment');
            }
            if ($request->has('location_zone')) {
                $sessionData['location_zone'] = $request->input('location_zone');
            }
            if ($request->has('quantity')) {
                $sessionData['quantity'] = $request->input('quantity');
            }

            $result = $this->calculationService->calculatePrice($plan, $sessionData);

            return $this->sendSuccess($result);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors du calcul du prix', ['exception' => $e->getMessage()], 500);
        }
    }
}
