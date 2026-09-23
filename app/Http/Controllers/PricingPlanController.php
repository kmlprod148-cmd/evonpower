<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\VatRate;
use App\Services\PricingCalculationService;
use App\Services\PricingPlanService;
use App\Support\RequestAwareRoute;
use Illuminate\Http\Request;

class PricingPlanController extends Controller
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
     * Page "Liste des plans tarifaires"
     */
    public function index(Request $request)
    {
        $query = PricingPlan::forUser(auth()->user())
            ->with('vatRate')
            ->withCount(['chargingPoints', 'groups'])
            ->orderBy('priority')
            ->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($type = $request->get('type')) {
            $query->where('rate_type', $type);
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $plans = $query->paginate(15)->appends($request->query());

        return view('pricing-plans.index', compact('plans'));
    }

    /**
     * Détail d'un plan tarifaire.
     */
    public function show(PricingPlan $pricingPlan)
    {
        $pricingPlan->load([
            'vatRate',
            'additionalRates.days',
            'chargingPoints',
            'groups',
        ]);

        // Statistiques de base (nombre de bornes, groupes, revenus)
        $statsPlans = $this->pricingPlanService->getPricingPlansWithStats([
            'is_active' => null,
            'search' => $pricingPlan->name,
        ]);
        $stats = $statsPlans->firstWhere('id', $pricingPlan->id)?->stats ?? [
            'charging_points_count' => $pricingPlan->charging_points_count ?? $pricingPlan->chargingPoints()->count(),
            'groups_count' => $pricingPlan->groups_count ?? $pricingPlan->groups()->count(),
            'total_revenue' => null,
        ];

        return view('pricing-plans.show', [
            'plan' => $pricingPlan,
            'stats' => $stats,
        ]);
    }

    /**
     * Rediriger la création vers le flux existant basé sur `plans.*`
     */
    public function store(Request $request)
    {
        return redirect()
            ->to(RequestAwareRoute::to($request, 'plans.create'))
            ->with('info', 'Utilisez l’interface avancée de création de plan tarifaire.');
    }

    /**
     * Rediriger l’affichage détaillé vers la nouvelle page.
     */
    public function create()
    {
        $vatRates = VatRate::getActiveRates();
        $defaultVatRate = VatRate::getDefault();

        return view('plans.create', compact('vatRates', 'defaultVatRate'));
    }

    /**
     * Rediriger l’édition vers le flux existant.
     */
    public function edit(Request $request, PricingPlan $pricingPlan)
    {
        return redirect()->to(RequestAwareRoute::to($request, 'plans.edit', $pricingPlan));
    }

    /**
     * Mise à jour déléguée au contrôleur historique.
     */
    public function update(Request $request, PricingPlan $pricingPlan)
    {
        return redirect()->to(RequestAwareRoute::to($request, 'plans.update', $pricingPlan));
    }

    /**
     * Suppression déléguée.
     */
    public function destroy(Request $request, PricingPlan $pricingPlan)
    {
        return redirect()->to(RequestAwareRoute::to($request, 'plans.destroy', $pricingPlan));
    }

    /**
     * Actions supplémentaires: on s’appuie sur les routes historiques.
     */
    public function duplicate(Request $request, PricingPlan $pricingPlan)
    {
        return redirect()->to(RequestAwareRoute::to($request, 'plans.edit', $pricingPlan))
            ->with('info', 'Dupliquer le plan depuis l’interface avancée si nécessaire.');
    }

    public function deactivate(Request $request, PricingPlan $pricingPlan)
    {
        $pricingPlan->update(['is_active' => false]);

        return redirect()->to(RequestAwareRoute::to($request, 'pricing-plans.index'))
            ->with('success', 'Plan tarifaire désactivé avec succès.');
    }

    public function calculatePrice(PricingPlan $pricingPlan, Request $request)
    {
        $sessionData = $request->validate([
            'duration_minutes' => 'required|numeric|min:0',
            'energy_kwh' => 'required|numeric|min:0',
            'start_time' => 'required|date',
            'power' => 'nullable|numeric|min:0',
            'customer_segment' => 'nullable|string|max:50',
            'location_zone' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $result = $this->calculationService->calculatePrice($pricingPlan, $sessionData);

        return response()->json($result);
    }

    public function getStats(PricingPlan $pricingPlan)
    {
        $plans = $this->pricingPlanService->getPricingPlansWithStats([
            'search' => $pricingPlan->name,
        ]);

        $stats = $plans->firstWhere('id', $pricingPlan->id)?->stats ?? null;

        return response()->json([
            'success' => (bool) $stats,
            'stats' => $stats,
        ]);
    }
}
