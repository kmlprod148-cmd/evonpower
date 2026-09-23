<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use App\Services\PricingPlanService;
use App\Exceptions\ChargingPoint\ChargingPointNotFoundException;
use App\Models\PricingPlan;
use Illuminate\Support\Collection;

class OfferController extends Controller
{
    protected $chargingPointRepository;
    protected $pricingPlanService;

    public function __construct(
        ChargingPointRepositoryInterface $chargingPointRepository,
        PricingPlanService $pricingPlanService
    ) {
        $this->chargingPointRepository = $chargingPointRepository;
        $this->pricingPlanService = $pricingPlanService;
    }

    public function show($id)
    {
        try {
            // Récupérer la borne de recharge avec ses relations
            $chargingPoint = $this->chargingPointRepository->findWithRelations($id, ['connectors', 'pricingPlan']);
            
            if (!$chargingPoint) {
                abort(404, 'Borne de recharge non trouvée');
            }

            // Récupérer tous les plans tarifaires disponibles pour cette borne
            $pricingPlans = $this->getAvailablePricingPlans($chargingPoint);

            // Préparer les données pour la vue
            $data = [
                'charging_point' => $chargingPoint,
                'pricing_plans' => $pricingPlans
            ];

            return view('offre.show', compact('data'));
            
        } catch (ChargingPointNotFoundException $e) {
            abort(404, 'Borne de recharge non trouvée');
        } catch (\Exception $e) {
            abort(500, 'Erreur lors du chargement de l\'offre');
        }
    }

    /**
     * Récupère tous les plans tarifaires disponibles pour une borne
     * 
     * @param \App\Models\ChargingPoint $chargingPoint
     * @return \Illuminate\Support\Collection
     */
    private function getAvailablePricingPlans($chargingPoint): Collection
    {
        $plans = collect();

        // 1. Ajouter le plan assigné directement à la borne
        if ($chargingPoint->pricingPlan && $chargingPoint->pricingPlan->is_active) {
            $plans->push($chargingPoint->pricingPlan);
        }

        // 2. Ajouter les plans assignés aux connecteurs
        foreach ($chargingPoint->connectors as $connector) {
            if ($connector->pricingPlan && $connector->pricingPlan->is_active) {
                $plans->push($connector->pricingPlan);
            }
        }

        // 3. Si aucun plan n'est trouvé, ajouter le premier plan actif disponible
        if ($plans->isEmpty()) {
            $defaultPlan = PricingPlan::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('id', 'asc')
                ->first();
            
            if ($defaultPlan) {
                $plans->push($defaultPlan);
            } else {
                // Si aucun plan actif, créer un plan temporaire
                $defaultPlan = new PricingPlan([
                    'name' => 'Plan Standard',
                    'description' => 'Plan tarifaire standard',
                    'rate_type' => 'mixed',
                    'price_per_kwh' => 0.30,
                    'price_per_minute' => 0.10,
                    'activation_fee' => 1.00,
                    'currency' => 'EUR',
                    'is_active' => true
                ]);
                $plans->push($defaultPlan);
            }
        }

        // 4. Supprimer les doublons et trier par priorité
        return $plans->unique('id')->sortBy('priority');
    }
} 