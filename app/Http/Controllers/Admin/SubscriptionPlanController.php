<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPlanType;
use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Station;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\VatRate;
use App\Services\SubscriptionPlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    protected SubscriptionPlanService $service;

    public function __construct(SubscriptionPlanService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a paginated list of subscription plans
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'search',
            'type',
            'active',
            'featured',
            'order_by',
            'order_dir',
        ]);

        $plans = $this->service->getAll($filters, 15);

        $types = SubscriptionPlanType::getOptions();

        $stats = [
            'total'       => SubscriptionPlan::count(),
            'active'      => SubscriptionPlan::where('is_active', true)->count(),
            'featured'    => SubscriptionPlan::where('is_featured', true)->count(),
            'subscribers' => UserSubscription::where('status', 'active')->count(),
        ];

        return view('admin.subscriptions.plans.index', compact('plans', 'filters', 'types', 'stats'));
    }

    /**
     * Show the form for creating a new subscription plan
     */
    public function create(): View
    {
        $types = SubscriptionPlanType::getOptions();
        $vatRates = VatRate::active()->get();
        $groups = Group::all();
        $chargingPoints = ChargingPoint::all();
        $stations = Station::all();

        return view('admin.subscriptions.plans.create', compact(
            'types',
            'vatRates',
            'groups',
            'chargingPoints',
            'stations'
        ));
    }

    /**
     * Store a newly created subscription plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', SubscriptionPlanType::getValues()),
            'price' => 'required|numeric|min:0',
            'vat_rate_id' => 'nullable|exists:vat_rates,id',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'duration_months' => 'nullable|integer|min:0',
            'max_sessions' => 'nullable|integer|min:0',
            'max_kwh' => 'nullable|numeric|min:0',
            'max_duration_minutes' => 'nullable|integer|min:0',
            'max_charging_points' => 'nullable|integer|min:0',
            'terms_conditions' => 'nullable|string',
            'features' => 'nullable|array',
            'allow_renewal' => 'boolean',
            'allow_upgrade' => 'boolean',
            'allow_downgrade' => 'boolean',
            'cancellation_fee' => 'nullable|numeric|min:0',
            'min_contract_months' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            'group_ids' => 'array',
            'group_ids.*' => 'exists:groups,id',
            'charging_point_ids' => 'array',
            'charging_point_ids.*' => 'exists:charging_points,id',
            'station_ids' => 'array',
            'station_ids.*' => 'exists:stations,id',
        ]);

        // Handle VAT rate
        if (empty($validated['vat_rate']) && !empty($validated['vat_rate_id'])) {
            $vatRate = VatRate::find($validated['vat_rate_id']);
            $validated['vat_rate'] = $vatRate?->rate ?? 0;
        } else {
            $validated['vat_rate'] = $validated['vat_rate'] ?? 0;
        }

        // Handle features as JSON
        if (isset($validated['features'])) {
            $validated['features'] = json_encode($validated['features']);
        }

        $plan = $this->service->create($validated);

        return redirect()
            ->route('admin.subscriptions.plans.show', $plan->id)
            ->with('success', 'Plan d\'abonnement créé avec succès');
    }

    /**
     * Display the specified subscription plan
     */
    public function show(int $id): View
    {
        $plan = $this->service->getById($id);
        
        if (!$plan) {
            abort(404, 'Plan d\'abonnement non trouvé');
        }

        $activeSubscriptions = $plan->userSubscriptions()
            ->with(['user'])
            ->active()
            ->paginate(10);

        return view('admin.subscriptions.plans.show', compact('plan', 'activeSubscriptions'));
    }

    /**
     * Show the form for editing the specified subscription plan
     */
    public function edit(int $id): View
    {
        $plan = $this->service->getById($id);
        
        if (!$plan) {
            abort(404, 'Plan d\'abonnement non trouvé');
        }

        $types = SubscriptionPlanType::getOptions();
        $vatRates = VatRate::active()->get();
        $groups = Group::all();
        $chargingPoints = ChargingPoint::all();
        $stations = Station::all();

        // Get selected IDs
        $selectedGroupIds = $plan->groups->pluck('id')->toArray();
        $selectedChargingPointIds = $plan->chargingPoints->pluck('id')->toArray();
        $selectedStationIds = $plan->stations->pluck('id')->toArray();

        return view('admin.subscriptions.plans.edit', compact(
            'plan',
            'types',
            'vatRates',
            'groups',
            'chargingPoints',
            'stations',
            'selectedGroupIds',
            'selectedChargingPointIds',
            'selectedStationIds'
        ));
    }

    /**
     * Update the specified subscription plan
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:' . implode(',', SubscriptionPlanType::getValues()),
            'price' => 'required|numeric|min:0',
            'vat_rate_id' => 'nullable|exists:vat_rates,id',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'duration_months' => 'nullable|integer|min:0',
            'max_sessions' => 'nullable|integer|min:0',
            'max_kwh' => 'nullable|numeric|min:0',
            'max_duration_minutes' => 'nullable|integer|min:0',
            'max_charging_points' => 'nullable|integer|min:0',
            'terms_conditions' => 'nullable|string',
            'features' => 'nullable|array',
            'allow_renewal' => 'boolean',
            'allow_upgrade' => 'boolean',
            'allow_downgrade' => 'boolean',
            'cancellation_fee' => 'nullable|numeric|min:0',
            'min_contract_months' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer|min:0',
            'group_ids' => 'array',
            'group_ids.*' => 'exists:groups,id',
            'charging_point_ids' => 'array',
            'charging_point_ids.*' => 'exists:charging_points,id',
            'station_ids' => 'array',
            'station_ids.*' => 'exists:stations,id',
        ]);

        // Handle VAT rate
        if (empty($validated['vat_rate']) && !empty($validated['vat_rate_id'])) {
            $vatRate = VatRate::find($validated['vat_rate_id']);
            $validated['vat_rate'] = $vatRate?->rate ?? 0;
        } else {
            $validated['vat_rate'] = $validated['vat_rate'] ?? 0;
        }

        // Handle features as JSON
        if (isset($validated['features'])) {
            $validated['features'] = json_encode($validated['features']);
        }

        $plan = $this->service->update($id, $validated);

        return redirect()
            ->route('admin.subscriptions.plans.show', $plan->id)
            ->with('success', 'Plan d\'abonnement mis à jour avec succès');
    }

    /**
     * Remove the specified subscription plan
     */
    public function destroy(int $id)
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return back()->with('error', 'Impossible de supprimer ce plan d\'abonnement');
        }

        return redirect()
            ->route('admin.subscriptions.plans.index')
            ->with('success', 'Plan d\'abonnement supprimé avec succès');
    }

    /**
     * Toggle plan active status
     */
    public function toggleActive(int $id)
    {
        $plan = SubscriptionPlan::find($id);
        
        if (!$plan) {
            return back()->with('error', 'Plan d\'abonnement non trouvé');
        }

        $plan->update(['is_active' => !$plan->is_active]);

        $status = $plan->is_active ? 'activé' : 'désactivé';
        
        return back()->with('success', "Plan d'abonnement {$status}");
    }

    /**
     * Toggle plan featured status
     */
    public function toggleFeatured(int $id)
    {
        $plan = SubscriptionPlan::find($id);
        
        if (!$plan) {
            return back()->with('error', 'Plan d\'abonnement non trouvé');
        }

        $plan->update(['is_featured' => !$plan->is_featured]);

        $status = $plan->is_featured ? 'mis en avant' : 'retiré des mises en avant';
        
        return back()->with('success', "Plan d'abonnement {$status}");
    }
}
