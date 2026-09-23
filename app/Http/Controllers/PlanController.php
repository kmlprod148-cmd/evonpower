<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pricing\StorePlanRequest;
use App\Http\Requests\Pricing\UpdatePlanRequest;
use App\Models\AdditionalRate;
use App\Models\BusinessProfile;
use App\Models\Plan;
use App\Models\VatRate;
use App\Notifications\NewPlanCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    /**
     * Display a listing of the plans.
     */
    public function index()
    {
        $plans = Plan::with('vatRate')
            ->orderBy('priority')
            ->paginate(10);

        return view('plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new plan.
     */
    public function create()
    {
        $vatRates = VatRate::getActiveRates();
        $defaultVatRate = VatRate::getDefault() ?? $vatRates->first();

        return view('plans.create', compact('vatRates', 'defaultVatRate'));
    }

    /**
     * Store a newly created plan in storage.
     */
    public function store(StorePlanRequest $request)
    {
        $validatedData = $request->validated();
        $plan = null;

        try {
            DB::beginTransaction();

            $plan = Plan::create($this->extractPlanData($validatedData));

            if (! empty($validatedData['additional_rates'])) {
                $this->syncAdditionalRates($plan, $validatedData['additional_rates']);
            }

            // Link the plan to the partner so it appears in their plan list
            if (Auth::user()->hasRole('partner')) {
                $partnerId = Auth::user()->partner_id
                    ?? optional(Auth::user()->partner)->id;
                if ($partnerId) {
                    $plan->partners()->syncWithoutDetaching([$partnerId]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error creating plan: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du plan: '.$e->getMessage())
                ->withInput();
        }

        try {
            \App\Models\User::role('admin')->each(function ($admin) use ($plan) {
                $admin->notify(new NewPlanCreatedNotification($plan));
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to send plan creation notifications: '.$e->getMessage(), [
                'plan_id' => $plan->id,
            ]);
        }

        return redirect()->route('plans.index')
            ->with('success', 'Plan tarifaire créé avec succès.');
    }

    /**
     * Display the specified plan.
     */
    public function show(Plan $plan)
    {
        $plan->load(['vatRate', 'additionalRates', 'groups', 'businessProfiles']);

        return view('plans.show', compact('plan'));
    }

    /**
     * Show the form for editing the specified plan.
     */
    public function edit(Plan $plan)
    {
        $this->authorizeForPartner($plan);

        $vatRates = VatRate::getActiveRates();
        $defaultVatRate = VatRate::getDefault() ?? $vatRates->first();
        $plan->load(['vatRate', 'additionalRates']);

        return view('plans.edit', compact('plan', 'vatRates', 'defaultVatRate'));
    }

    /**
     * Update the specified plan in storage.
     */
    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->authorizeForPartner($plan);

        $validatedData = $request->validated();

        try {
            DB::beginTransaction();

            $plan->update($this->extractPlanData($validatedData));

            if ($request->exists('additional_rates')) {
                $this->syncAdditionalRates($plan, $validatedData['additional_rates'] ?? []);
            }

            DB::commit();

            return redirect()->route('plans.index')
                ->with('success', 'Plan tarifaire mis à jour avec succès.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating plan: '.$e->getMessage(), [
                'plan_id' => $plan->id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du plan.')
                ->withInput();
        }
    }

    /**
     * Remove the specified plan from storage.
     */
    public function destroy(Request $request, Plan $plan)
    {
        $this->authorizeForPartner($plan);

        try {
            if ($plan->chargingPoints()->exists()) {
                return redirect()->to(route('plans.index'))
                    ->with('error', 'Ce plan est utilisé par des bornes de recharge et ne peut pas être supprimé.');
            }

            DB::beginTransaction();

            $plan->additionalRates()->delete();
            $plan->businessProfiles()->detach();
            $plan->groups()->detach();
            $plan->delete();

            DB::commit();

            return redirect()->to(route('plans.index'))
                ->with('success', 'Plan tarifaire supprimé avec succès.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error deleting plan: '.$e->getMessage(), [
                'plan_id' => $plan->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->to(route('plans.index'))
                ->with('error', 'Erreur lors de la suppression du plan.');
        }
    }

    /**
     * Activate an inactive plan.
     */
    public function activate(Request $request, Plan $plan)
    {
        try {
            $plan->update(['is_active' => true]);

            return redirect()->to(route('plans.index'))
                ->with('success', 'Plan tarifaire activé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Error activating plan: '.$e->getMessage(), [
                'plan_id' => $plan->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->to(route('plans.index'))
                ->with('error', 'Erreur lors de l’activation du plan.');
        }
    }

    /**
     * Show the business profile association screen.
     */
    public function applyToProfiles(Plan $plan)
    {
        $plan->load('businessProfiles');

        $businessProfiles = BusinessProfile::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $associatedProfileIds = $plan->businessProfiles
            ->pluck('id')
            ->all();

        return view('plans.apply-to-profiles', compact('plan', 'businessProfiles', 'associatedProfileIds'));
    }

    /**
     * Persist business profile associations for a plan.
     */
    public function saveProfileAssociations(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'business_profiles' => ['nullable', 'array'],
            'business_profiles.*' => ['integer', 'exists:business_profiles,id'],
        ]);

        $plan->businessProfiles()->sync($validated['business_profiles'] ?? []);

        return redirect()->route('plans.show', $plan->id)
            ->with('success', 'Les associations de profils business ont été mises à jour.');
    }

    /**
     * Toggle plan active status.
     */
    public function toggleStatus(Plan $plan)
    {
        try {
            $plan->update(['is_active' => ! $plan->is_active]);

            $status = $plan->is_active ? 'activé' : 'désactivé';

            return redirect()->back()
                ->with('success', "Plan {$status} avec succès.");
        } catch (\Throwable $e) {
            Log::error('Error toggling plan status: '.$e->getMessage(), [
                'plan_id' => $plan->id,
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la modification du statut.');
        }
    }

    /**
     * Deny access if the authenticated partner does not own the plan.
     * Admins and super_admins always pass through.
     */
    private function authorizeForPartner(Plan $plan): void
    {
        $user = Auth::user();
        if ($user->hasRole(['admin', 'super_admin'])) {
            return;
        }
        if ($user->hasRole('partner')) {
            $partnerId = $user->partner_id ?? optional($user->partner)->id ?? 0;
            $owns = $plan->partners()->where('partners.id', $partnerId)->exists();
            if (! $owns) {
                abort(403, 'Vous n\'avez pas accès à ce plan tarifaire.');
            }

            return;
        }
        // Other roles: must be the creator
        if ($plan->created_by !== $user->id) {
            abort(403, 'Vous n\'avez pas accès à ce plan tarifaire.');
        }
    }

    /**
     * Build the data persisted on the pricing_plans table.
     *
     * @param  array<string, mixed>  $validatedData
     * @return array<string, mixed>
     */
    private function extractPlanData(array $validatedData): array
    {
        return [
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'rate_type' => $validatedData['rate_type'],
            'base_rate' => $validatedData['base_rate'],
            'fixed_price' => $validatedData['fixed_price'] ?? 0,
            'price_per_kwh' => $validatedData['price_per_kwh'] ?? 0,
            'price_per_minute' => $validatedData['price_per_minute'] ?? 0,
            'activation_fee' => $validatedData['activation_fee'] ?? 0,
            'weekend_price' => $validatedData['weekend_price'] ?? 0,
            'night_price' => $validatedData['night_price'] ?? 0,
            'has_weekend_pricing' => $validatedData['has_weekend_pricing'] ?? false,
            'has_night_pricing' => $validatedData['has_night_pricing'] ?? false,
            'night_start_time' => $validatedData['night_start_time'] ?? '22:00',
            'night_end_time' => $validatedData['night_end_time'] ?? '06:00',
            'vat_rate_id' => $validatedData['vat_rate_id'],
            'priority' => $validatedData['priority'] ?? 0,
            'max_duration' => $validatedData['max_duration'],
            'is_active' => $validatedData['is_active'] ?? false,
            'currency' => strtoupper($validatedData['currency'] ?? 'EUR'),
            'billing_interval' => $validatedData['billing_interval'] ?? 'session',
            'valid_from' => $validatedData['valid_from'] ?? null,
            'valid_until' => $validatedData['valid_until'] ?? null,
            'mobile_theme_color' => $validatedData['mobile_theme_color'] ?? null,
        ];
    }

    /**
     * Replace the plan's additional rates with a new set.
     *
     * @param  array<int, array<string, mixed>>  $additionalRatesData
     */
    private function syncAdditionalRates(Plan $plan, array $additionalRatesData): void
    {
        $plan->additionalRates()->delete();

        if ($additionalRatesData === []) {
            return;
        }

        $this->createAdditionalRates($plan, $additionalRatesData);
    }

    /**
     * Create additional rates for a plan.
     *
     * @param  array<int, array<string, mixed>>  $additionalRatesData
     */
    private function createAdditionalRates(Plan $plan, array $additionalRatesData): void
    {
        foreach ($additionalRatesData as $rateData) {
            if (empty($rateData['name']) || ! isset($rateData['price'])) {
                continue;
            }

            $data = [
                'pricing_plan_id' => $plan->id,
                'name' => $rateData['name'],
                'rate_type' => $rateData['rate_type'] ?? 'fixed',
                'price' => $rateData['price'],
                'vat_rate_id' => $rateData['vat_rate_id'] ?? $plan->vat_rate_id,
                'condition_type' => $rateData['condition_type'] ?? 'all',
                // Time fields
                'time_start' => $rateData['time_start'] ?? null,
                'time_end' => $rateData['time_end'] ?? null,
                // Day fields (JSON array)
                'days' => $rateData['days'] ?? null,
                // Power fields
                'power_min' => $rateData['min_power'] ?? null,
                'power_max' => $rateData['max_power'] ?? null,
                // Duration fields
                'duration_min' => $rateData['min_duration'] ?? null,
                'duration_max' => $rateData['max_duration'] ?? null,
                // Customer & location
                'customer_segment' => $rateData['customer_segment'] ?? null,
                'location_zone' => $rateData['location_zone'] ?? null,
                // Quantity
                'quantity_min' => $rateData['quantity_min'] ?? null,
                'quantity_max' => $rateData['quantity_max'] ?? null,
                // Percentage & apply type
                'is_percentage' => $rateData['is_percentage'] ?? false,
                'percentage_value' => $rateData['percentage_value'] ?? null,
                'apply_type' => $rateData['apply_type'] ?? 'markup',
                // Date restrictions
                'applicable_dates' => $rateData['applicable_dates'] ?? null,
                'excluded_dates' => $rateData['excluded_dates'] ?? null,
            ];

            AdditionalRate::create($data);
        }
    }
}
