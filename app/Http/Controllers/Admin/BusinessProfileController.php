<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class BusinessProfileController extends Controller
{
    public function __construct()
    {
        // Log the authenticated user's ID for debugging
        Log::info('AdminBusinessProfileController __construct called by user: ' . (Auth::id() ?? 'Guest'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('viewAny', BusinessProfile::class)) {
            Log::warning('User ' . Auth::id() . ' attempted to access business profiles index without permission.');
            abort(403, 'You do not have permission to view business profiles.');
        }

        $businessProfiles = BusinessProfile::all();
        return view('business-profiles.index', compact('businessProfiles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('create', BusinessProfile::class)) {
            Log::warning('User ' . Auth::id() . ' attempted to access business profiles create form without permission.');
            abort(403, 'You do not have permission to create business profiles.');
        }

        // Determine default target audience based on user role
        $user = Auth::user();
        $defaultTargetAudience = [];

        // If user is an integrator, only operator audience is available
        if ($user->hasRole(['integrator', 'Integrator'])) {
            $defaultTargetAudience = ['operator'];
        }
        // If user is admin, both audiences are available (no default)

        return view('business-profiles.create', compact('defaultTargetAudience'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('AdminBusinessProfileController store method hit.');
        if (Auth::check()) {
            Log::info('User ' . Auth::id() . ' attempting to store business profile.');
            Log::info('User roles: ' . implode(', ', Auth::user()->getRoleNames()->toArray()));
            Log::info('User permissions: ' . implode(', ', Auth::user()->getAllPermissions()->pluck('name')->toArray()));
        } else {
            Log::warning('Unauthenticated user attempted to store a business profile.');
        }

        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('create', BusinessProfile::class)) {
            Log::warning('User ' . Auth::id() . ' attempted to store a business profile without permission. Gate denied.');
            abort(403, 'You do not have permission to create business profiles.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_audience' => 'required|array',
            'partner_id' => 'nullable|exists:partners,id',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'base_fee_amount' => 'nullable|numeric|min:0',
            'terminal_fee_amount' => 'nullable|numeric|min:0',
            'transaction_fee_type' => 'nullable|array',
            'transaction_fee_fixed_amount' => 'nullable|numeric|min:0',
            'transaction_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'charge_fee_fixed_amount' => 'nullable|numeric|min:0',
            'charge_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'operator_commission' => 'nullable|numeric|min:0|max:100',
            'integrator_commission' => 'nullable|numeric|min:0|max:100',
            'owner_commission' => 'nullable|numeric|min:0|max:100',
            // Nouveaux champs pour la gestion des transactions
            'admin_contact_name' => 'nullable|string|max:255',
            'admin_contact_email' => 'nullable|email|max:255',
            'admin_contact_phone' => 'nullable|string|max:20',
            'integrator_contact_name' => 'nullable|string|max:255',
            'integrator_contact_email' => 'nullable|email|max:255',
            'integrator_contact_phone' => 'nullable|string|max:20',
            'admin_bank_account' => 'nullable|string|max:255',
            'integrator_bank_account' => 'nullable|string|max:255',
            'operator_bank_account' => 'nullable|string|max:255',
            'handles_admin_debits' => 'boolean',
            'handles_integrator_debits' => 'boolean',
            'handles_client_payments' => 'boolean',
            'handles_wire_transfers' => 'boolean',
            'min_transaction_amount' => 'nullable|numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();
        $data['created_by_type'] = get_class(Auth::user());
        $data['created_by_id'] = Auth::id();

        // Set partner_id to null if not provided
        if (!isset($data['partner_id']) || empty($data['partner_id'])) {
            $data['partner_id'] = null;
        }

        // Valeur par défaut pour integrator_id
        if (empty($data['integrator_id'])) {
            $firstIntegrator = \App\Models\Integrator::first();
            if ($firstIntegrator) {
                $data['integrator_id'] = $firstIntegrator->id;
            } else {
                return redirect()->back()->with('error', 'Aucun intégrateur disponible. Veuillez en créer un avant de créer un profil business.')->withInput();
            }
        }

        // Process target_audience if it's an array
        if (isset($data['target_audience']) && is_array($data['target_audience'])) {
            $data['target_audience'] = json_encode($data['target_audience']);
        }

        // Process transaction fee config
        if (isset($data['transaction_fee_fixed_amount']) || isset($data['transaction_fee_percentage'])) {
            $data['transaction_fee_config'] = json_encode([
                'types' => $data['transaction_fee_type'] ?? [],
                'fixed_amount' => floatval($data['transaction_fee_fixed_amount'] ?? 0),
                'percentage' => floatval($data['transaction_fee_percentage'] ?? 0)
            ]);
        }

        // Process charge fee config
        if (isset($data['charge_fee_fixed_amount']) || isset($data['charge_fee_percentage'])) {
            $data['charge_fee_config'] = json_encode([
                'fixed_amount' => floatval($data['charge_fee_fixed_amount'] ?? 0),
                'percentage' => floatval($data['charge_fee_percentage'] ?? 0)
            ]);
        }

        // Set default values for commission fields if not provided
        $data['operator_commission'] = floatval($data['operator_commission'] ?? 0);
        $data['integrator_commission'] = floatval($data['integrator_commission'] ?? 0);
        $data['owner_commission'] = floatval($data['owner_commission'] ?? 0);

        // Remove temporary fields that are not in the database
        $fieldsToRemove = [
            'transaction_fee_type',
            'transaction_fee_fixed_amount',
            'transaction_fee_percentage',
            'charge_fee_fixed_amount',
            'charge_fee_percentage',
            'subscription_period',
            'terminal_count'
        ];

        foreach ($fieldsToRemove as $field) {
            unset($data[$field]);
        }

        $businessProfile = BusinessProfile::create($data);

        return redirect()->route('business-profiles.index')->with('success', 'Business Profile created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessProfile $businessProfile)
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('view', $businessProfile)) {
            Log::warning('User ' . Auth::id() . ' attempted to view business profile ' . $businessProfile->id . ' without permission.');
            abort(403, 'You do not have permission to view this business profile.');
        }

        return view('business-profiles.show', compact('businessProfile'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BusinessProfile $businessProfile)
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup

        if (Gate::denies('update', $businessProfile)) {
            Log::warning('User ' . Auth::id() . ' attempted to edit business profile ' . $businessProfile->id . ' without permission.');
            abort(403, 'You do not have permission to edit this business profile.');
        }

        // Eager load all necessary relationships and data
        $businessProfile->load(['partners', 'creator', 'pricingPlan']);

        // Parse JSON fields to ensure they're properly formatted
        $targetAudience = is_string($businessProfile->target_audience)
            ? json_decode($businessProfile->target_audience, true) ?? []
            : ($businessProfile->target_audience ?? []);

        $transactionFeeConfig = is_string($businessProfile->transaction_fee_config)
            ? json_decode($businessProfile->transaction_fee_config, true) ?? []
            : ($businessProfile->transaction_fee_config ?? []);

        $chargeFeeConfig = is_string($businessProfile->charge_fee_config)
            ? json_decode($businessProfile->charge_fee_config, true) ?? []
            : ($businessProfile->charge_fee_config ?? []);

        $partners = $businessProfile->partners;

        // Determine if user is integrator for audience restrictions
        $user = Auth::user();
        $isIntegrator = $user->hasRole(['integrator', 'Integrator']);

        return view('business-profiles.edit', compact(
            'businessProfile',
            'partners',
            'targetAudience',
            'transactionFeeConfig',
            'chargeFeeConfig',
            'isIntegrator'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BusinessProfile $businessProfile)
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('update', $businessProfile)) {
            Log::warning('User ' . Auth::id() . ' attempted to update business profile ' . $businessProfile->id . ' without permission.');
            abort(403, 'You do not have permission to update this business profile.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // Add other validation rules as needed
        ]);

        $businessProfile->update($request->all());

        return redirect()->route('business-profiles.index')->with('success', 'Business Profile updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessProfile $businessProfile)
    {
        // Log user roles before policy check
        Log::info('BusinessProfileController destroy: User roles', ['user_id' => Auth::id(), 'roles' => Auth::user()->getRoleNames()]);

        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('delete', $businessProfile)) {
            Log::warning('User ' . Auth::id() . ' attempted to delete business profile ' . $businessProfile->id . ' without permission.');
            abort(403, 'You do not have permission to delete this business profile.');
        }

        $businessProfile->delete();

        return redirect()->route('business-profiles.index')->with('success', 'Business Profile deleted successfully.');
    }

    /**
     * Display the form for managing partner rates for the specified business profile.
     *
     * @param  \App\Models\BusinessProfile  $businessProfile
     * @return \Illuminate\Http\Response
     */
    public function managePartnerRates(BusinessProfile $businessProfile)
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('managePartnerRates', $businessProfile)) {
            Log::warning('User ' . Auth::id() . ' attempted to manage partner rates for business profile ' . $businessProfile->id . ' without permission.');
            abort(403, 'You do not have permission to manage partner rates for this business profile.');
        }

        $businessProfile->load('pricingPlans'); // Eager load pricing plans
        $partners = $businessProfile->partners()->with('pricingPlans')->get(); // Load associated partners with their pricing plans
        $availablePlans = $businessProfile->pricingPlans ?? \App\Models\PricingPlan::all();

        return view('business-profiles.manage-partner-rates', compact('businessProfile', 'partners', 'availablePlans'));
    }

    /**
     * Apply pricing plans to partners for the specified business profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BusinessProfile  $businessProfile
     * @return \Illuminate\Http\Response
     */
    public function applyPartnerRates(Request $request, BusinessProfile $businessProfile)
    {
        // Gate check is redundant if middleware is used, but kept for clarity/backup
        if (Gate::denies('managePartnerRates', $businessProfile)) {
            Log::warning('User ' . Auth::id() . ' attempted to apply partner rates for business profile ' . $businessProfile->id . ' without permission.');
            abort(403, 'You do not have permission to manage partner rates for this business profile.');
        }

        $request->validate([
            'partner_plans' => 'required|array',
            'partner_plans.*' => 'array',
            'partner_plans.*.*' => 'exists:pricing_plans,id',
        ]);

        $partnerPlans = $request->input('partner_plans', []);

        foreach ($partnerPlans as $partnerId => $planIds) {
            $partner = $businessProfile->partners()->find($partnerId);
            
            if ($partner) {
                // Synchroniser les plans tarifaires pour ce partenaire
                $partner->pricingPlans()->sync($planIds);
                
                Log::info('Applied pricing plans to partner', [
                    'partner_id' => $partnerId,
                    'plan_ids' => $planIds,
                    'business_profile_id' => $businessProfile->id,
                    'user_id' => Auth::id()
                ]);
            }
        }

        return redirect()
            ->route('business-profiles.manage-partner-rates', $businessProfile)
            ->with('success', 'Les tarifs ont été appliqués avec succès aux partenaires.');
    }
}
