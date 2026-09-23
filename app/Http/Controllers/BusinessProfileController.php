<?php

namespace App\Http\Controllers;

use App\Services\BusinessProfileDisplayService;
use App\Services\IntegratorBusinessProfilePermissionService;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BusinessProfileController extends Controller
{
    protected $businessProfileDisplayService;
    protected $integratorPermissionService;

    public function __construct(
        BusinessProfileDisplayService $businessProfileDisplayService,
        IntegratorBusinessProfilePermissionService $integratorPermissionService
    ) {
        $this->businessProfileDisplayService = $businessProfileDisplayService;
        $this->integratorPermissionService = $integratorPermissionService;
    }

    /**
     * Afficher la liste des business profiles
     */
    public function index()
    {
        $user = auth()->user();
        
        // Obtenir les business profiles selon le rôle de l'utilisateur
        if ($user->hasRole('admin')) {
            // Les admins ne voient que les business profiles qu'ils ont créés
            $businessProfiles = BusinessProfile::where('is_active', true)
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('created_by_id', $user->id)
                      ->orWhere(function ($q2) use ($user) {
                          $q2->where('created_by_type', get_class($user))
                             ->where('created_by_id', $user->id);
                      });
                })
                ->with('creator')
                ->orderBy('name')
                ->get();
        } else {
            // Utiliser le nouveau service de permissions pour les intégrateurs
            $businessProfiles = $this->integratorPermissionService->getVisibleBusinessProfiles($user);
        }
        
        Log::info('Business profiles loaded for user', [
            'user_id' => $user->id,
            'user_role' => $user->getRoleNames()->first(),
            'profiles_count' => $businessProfiles->count()
        ]);
        
        return view('business-profiles.index', compact('businessProfiles'));
    }

    /**
     * Obtenir les business profiles pour un select/dropdown (API)
     */
    public function getForSelect(Request $request)
    {
        $user = auth()->user();
        
        // Obtenir les business profiles pour un select/dropdown
        $businessProfiles = $this->businessProfileDisplayService->getBusinessProfilesForSelect($user);
        
        return response()->json($businessProfiles);
    }

    /**
     * Obtenir UNIQUEMENT les business profiles de l'intégrateur pour un select/dropdown (API)
     */
    public function getIntegratorOwnForSelect(Request $request)
    {
        $user = auth()->user();
        
        // Obtenir UNIQUEMENT les business profiles de l'intégrateur pour un select/dropdown
        $businessProfiles = $this->businessProfileDisplayService->getIntegratorOwnBusinessProfilesForSelect($user);
        
        return response()->json($businessProfiles);
    }

    /**
     * Obtenir les business profiles avec leurs détails (API)
     */
    public function getWithDetails(Request $request)
    {
        $user = auth()->user();
        
        // Obtenir les business profiles avec leurs détails
        $businessProfiles = $this->businessProfileDisplayService->getBusinessProfilesWithDetails($user);
        
        return response()->json($businessProfiles);
    }

    /**
     * Vérifier si un business profile est visible (API)
     */
    public function checkVisibility(Request $request, $businessProfileId)
    {
        $user = auth()->user();
        
        $isVisible = $this->businessProfileDisplayService->isBusinessProfileVisible($user, $businessProfileId);
        
        return response()->json([
            'visible' => $isVisible,
            'business_profile_id' => $businessProfileId
        ]);
    }

    /**
     * Afficher le formulaire de création d'un business profile
     */
    public function create()
    {
        $user = auth()->user();

        // Vérifier les permissions de création
        if (!$this->integratorPermissionService->canCreateBusinessProfile($user)) {
            abort(403, 'Vous n\'avez pas la permission de créer des profils d\'entreprise.');
        }

        $integrators = Integrator::orderBy('name')->get();
        $partners    = Partner::orderBy('name')->get();

        return view('business-profiles.create', compact('integrators', 'partners'));
    }

    /**
     * Enregistrer un nouveau business profile
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Vérifier les permissions de création
        if (!$this->integratorPermissionService->canCreateBusinessProfile($user)) {
            abort(403, 'Vous n\'avez pas la permission de créer des profils d\'entreprise.');
        }

        // Validation des données
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Créer le business profile
        $businessProfile = BusinessProfile::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? '',
            'is_active' => $validatedData['is_active'] ?? true,
            'created_by_type' => get_class($user),
            'created_by_id' => $user->id,
            'created_by' => $user->id,
            'created_by_role' => $user->getRoleNames()->first() ?? 'user',
            // Valeurs par défaut pour les champs requis
            'integrator_commission' => 0.00,
            'owner_commission' => 0.00,
            'operator_commission' => 0.00,
            'partner_commission' => 0.00,
            'admin_fee_fixed' => 0.00,
            'admin_fee_percentage' => 0.00,
            'integrator_fee_fixed' => 0.00,
            'integrator_fee_percentage' => 0.00,
            'partner_fee_fixed' => 0.00,
            'partner_fee_percentage' => 0.00,
        ]);

        Log::info('Business profile created by integrator', [
            'user_id' => $user->id,
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $user->integrator_id ?? null
        ]);

        return redirect()->route('business-profiles.index')
            ->with('success', 'Business profile créé avec succès.');
    }

    /**
     * Afficher un business profile spécifique
     */
    public function show(BusinessProfile $businessProfile)
    {
        $user = auth()->user();
        // Super-admin peut voir tous les profils
        if ($user->hasRole(['super_admin', 'Super Admin', 'super admin'])) {
            return view('business-profiles.show', compact('businessProfile'));
        }
        
        // Restreindre l'accès des admins aux seuls profils qu'ils ont créés
        if ($user->hasRole('admin')) {
            $isOwner = ($businessProfile->created_by === $user->id)
                || ($businessProfile->created_by_id === $user->id)
                || ($businessProfile->created_by_type === get_class($user) && $businessProfile->created_by_id === $user->id);
            if (!$isOwner) {
                abort(403, 'Accès refusé.');
            }
        }
        return view('business-profiles.show', compact('businessProfile'));
    }

    /**
     * Afficher le formulaire d'édition d'un business profile
     */
    public function edit(BusinessProfile $businessProfile)
    {
        $user = auth()->user();
        
        // Super-admin peut éditer tous les profils
        if ($user->hasRole(['super_admin', 'Super Admin', 'super admin'])) {
            return view('business-profiles.edit', compact('businessProfile'));
        }
        
        // Admin: ne peut éditer que ses propres profils
        if ($user->hasRole('admin')) {
            $isOwner = ($businessProfile->created_by === $user->id)
                || ($businessProfile->created_by_id === $user->id)
                || ($businessProfile->created_by_type === get_class($user) && $businessProfile->created_by_id === $user->id);
            if (!$isOwner) {
                abort(403, 'Vous n\'avez pas la permission de modifier ce profil d\'entreprise.');
            }
        } else {
            // Vérifier les permissions d'édition pour les intégrateurs
            if (!$this->integratorPermissionService->canEditBusinessProfile($user, $businessProfile)) {
                abort(403, 'Vous n\'avez pas la permission de modifier ce profil d\'entreprise.');
            }
        }
        
        return view('business-profiles.edit', compact('businessProfile'));
    }

    /**
     * Mettre à jour un business profile
     */
    public function update(Request $request, BusinessProfile $businessProfile)
    {
        $user = auth()->user();
        
        // Super-admin peut mettre à jour tous les profils
        if ($user->hasRole(['super_admin', 'Super Admin', 'super admin'])) {
            // Pas de vérification supplémentaire nécessaire
        } elseif ($user->hasRole('admin')) {
            // Admin: ne peut mettre à jour que ses propres profils
            $isOwner = ($businessProfile->created_by === $user->id)
                || ($businessProfile->created_by_id === $user->id)
                || ($businessProfile->created_by_type === get_class($user) && $businessProfile->created_by_id === $user->id);
            if (!$isOwner) {
                abort(403, 'Vous n\'avez pas la permission de modifier ce profil d\'entreprise.');
            }
        } else {
            // Règles intégrateur
            if (!$this->integratorPermissionService->canEditBusinessProfile($user, $businessProfile)) {
                abort(403, 'Vous n\'avez pas la permission de modifier ce profil d\'entreprise.');
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Validation des données avec BusinessProfileRequest si disponible, sinon validation basique
            if ($request instanceof \App\Http\Requests\BusinessProfileRequest) {
                $validatedData = $request->validated();
            } else {
                $validatedData = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'is_public' => 'boolean',
                    'is_active' => 'boolean',
                    'target_audience' => 'nullable|array',
                    'target_audience.*' => 'in:integrator,operator',
                    'subscription_period' => 'nullable|in:monthly,quarterly,yearly',
                    'base_fee_amount' => 'nullable|numeric|min:0',
                    'terminal_fee_amount' => 'nullable|numeric|min:0',
                    'transaction_fee_type' => 'nullable|array',
                    'transaction_fee_type.*' => 'in:fixed,percentage',
                    'transaction_fee_fixed_amount' => 'nullable|numeric|min:0',
                    'transaction_fee_percentage' => 'nullable|numeric|min:0|max:100',
                    'charge_fee_fixed_amount' => 'nullable|numeric|min:0',
                    'charge_fee_percentage' => 'nullable|numeric|min:0|max:100',
                    'admin_fee_fixed' => 'nullable|numeric|min:0',
                    'admin_fee_percentage' => 'nullable|numeric|min:0|max:100',
                    'integrator_fee_fixed' => 'nullable|numeric|min:0',
                    'integrator_fee_percentage' => 'nullable|numeric|min:0|max:100',
                    'partner_fee_fixed' => 'nullable|numeric|min:0',
                    'partner_fee_percentage' => 'nullable|numeric|min:0|max:100',
                    'operator_commission' => 'nullable|numeric|min:0|max:100',
                    'integrator_commission' => 'nullable|numeric|min:0|max:100',
                    'owner_commission' => 'nullable|numeric|min:0|max:100',
                    'partner_commission' => 'nullable|numeric|min:0|max:100',
                ]);
            }

            // Traiter subscription_period
            if (isset($validatedData['subscription_period'])) {
                $validatedData['maintenance_fee_type'] = $validatedData['subscription_period'];
                $validatedData['terminal_fee_period'] = $validatedData['subscription_period'];
            }

            // Traiter transaction_fee_config depuis les champs du formulaire
            if (isset($validatedData['transaction_fee_type']) || isset($validatedData['transaction_fee_fixed_amount']) || isset($validatedData['transaction_fee_percentage'])) {
                $validatedData['transaction_fee_config'] = json_encode([
                    'types' => $validatedData['transaction_fee_type'] ?? [],
                    'fixed_amount' => floatval($validatedData['transaction_fee_fixed_amount'] ?? 0),
                    'percentage' => floatval($validatedData['transaction_fee_percentage'] ?? 0)
                ]);
            }

            // Traiter charge_fee_config depuis les champs du formulaire
            if (isset($validatedData['charge_fee_fixed_amount']) || isset($validatedData['charge_fee_percentage'])) {
                $validatedData['charge_fee_config'] = json_encode([
                    'fixed_amount' => floatval($validatedData['charge_fee_fixed_amount'] ?? 0),
                    'percentage' => floatval($validatedData['charge_fee_percentage'] ?? 0)
                ]);
            }

            // Convertir target_audience en JSON si c'est un tableau
            if (isset($validatedData['target_audience']) && is_array($validatedData['target_audience'])) {
                $validatedData['target_audience'] = json_encode($validatedData['target_audience']);
            }

            // Assurer des valeurs par défaut pour les champs numériques
            $validatedData['base_fee_amount'] = floatval($validatedData['base_fee_amount'] ?? $businessProfile->base_fee_amount ?? 0);
            $validatedData['terminal_fee_amount'] = floatval($validatedData['terminal_fee_amount'] ?? $businessProfile->terminal_fee_amount ?? 0);
            $validatedData['admin_fee_fixed'] = floatval($validatedData['admin_fee_fixed'] ?? $businessProfile->admin_fee_fixed ?? 0);
            $validatedData['admin_fee_percentage'] = floatval($validatedData['admin_fee_percentage'] ?? $businessProfile->admin_fee_percentage ?? 0);
            $validatedData['integrator_fee_fixed'] = floatval($validatedData['integrator_fee_fixed'] ?? $businessProfile->integrator_fee_fixed ?? 0);
            $validatedData['integrator_fee_percentage'] = floatval($validatedData['integrator_fee_percentage'] ?? $businessProfile->integrator_fee_percentage ?? 0);
            $validatedData['partner_fee_fixed'] = floatval($validatedData['partner_fee_fixed'] ?? $businessProfile->partner_fee_fixed ?? 0);
            $validatedData['partner_fee_percentage'] = floatval($validatedData['partner_fee_percentage'] ?? $businessProfile->partner_fee_percentage ?? 0);
            $validatedData['operator_commission'] = floatval($validatedData['operator_commission'] ?? $businessProfile->operator_commission ?? 0);
            $validatedData['integrator_commission'] = floatval($validatedData['integrator_commission'] ?? $businessProfile->integrator_commission ?? 0);
            $validatedData['owner_commission'] = floatval($validatedData['owner_commission'] ?? $businessProfile->owner_commission ?? 0);
            $validatedData['partner_commission'] = floatval($validatedData['partner_commission'] ?? $businessProfile->partner_commission ?? 0);

            // Supprimer les champs temporaires du formulaire
            $fieldsToRemove = [
                'transaction_fee_type',
                'transaction_fee_fixed_amount',
                'transaction_fee_percentage',
                'charge_fee_fixed_amount',
                'charge_fee_percentage',
                'subscription_period'
            ];
            
            foreach ($fieldsToRemove as $field) {
                unset($validatedData[$field]);
            }

            // Mettre à jour le business profile avec tous les champs
            $businessProfile->update($validatedData);
            
            // Rafraîchir le modèle pour s'assurer que les données sont à jour
            $businessProfile->refresh();

            \Illuminate\Support\Facades\DB::commit();

            // Propager les changements vers toutes les bornes concernées (après le commit)
            // Note: Les relations Eloquent chargeront automatiquement les nouvelles valeurs,
            // mais on log les bornes affectées pour information
            $message = 'Business profile mis à jour avec succès.';
            try {
                $propagationService = new \App\Services\BusinessProfilePropagationService();
                $affectedCount = $propagationService->getAffectedChargingPointsCount($businessProfile);
                
                if ($affectedCount > 0) {
                    $propagationResults = $propagationService->propagateToChargingPoints($businessProfile);
                    
                    Log::info('Business profile changes affect charging points', [
                        'business_profile_id' => $businessProfile->id,
                        'total_charging_points_affected' => $affectedCount,
                        'propagation_results' => $propagationResults
                    ]);
                    
                    $message .= " {$affectedCount} borne(s) de recharge concernée(s) ont été mises à jour automatiquement.";
                }
            } catch (\Exception $propagationError) {
                // Log l'erreur mais ne pas bloquer la mise à jour du business profile
                Log::warning('Failed to track business profile propagation', [
                    'business_profile_id' => $businessProfile->id,
                    'error' => $propagationError->getMessage()
                ]);
            }

            Log::info('Business profile updated successfully', [
                'user_id' => $user->id,
                'business_profile_id' => $businessProfile->id,
                'integrator_id' => $user->integrator_id ?? null,
                'updated_fields' => array_keys($validatedData)
            ]);

            return redirect()->route('business-profiles.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            
            Log::error('Error updating business profile', [
                'user_id' => $user->id,
                'business_profile_id' => $businessProfile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du business profile: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprimer un business profile
     */
    public function destroy(BusinessProfile $businessProfile)
    {
        $user = auth()->user();
        
        // Super-admin peut supprimer tous les profils
        if ($user->hasRole(['super_admin', 'Super Admin', 'super admin'])) {
            // Pas de vérification supplémentaire nécessaire
        } elseif ($user->hasRole('admin')) {
            // Admin: ne peut supprimer que ses propres profils
            $isOwner = ($businessProfile->created_by === $user->id)
                || ($businessProfile->created_by_id === $user->id)
                || ($businessProfile->created_by_type === get_class($user) && $businessProfile->created_by_id === $user->id);
            if (!$isOwner) {
                abort(403, 'Vous n\'avez pas la permission de supprimer ce profil d\'entreprise.');
            }
        } else {
            // Vérifier les permissions de suppression pour intégrateurs
            if (!$this->integratorPermissionService->canDeleteBusinessProfile($user, $businessProfile)) {
                abort(403, 'Vous n\'avez pas la permission de supprimer ce profil d\'entreprise.');
            }
        }

        $businessProfile->delete();

        Log::info('Business profile deleted by integrator', [
            'user_id' => $user->id,
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $user->integrator_id
        ]);

        return redirect()->route('business-profiles.index')
            ->with('success', 'Business profile supprimé avec succès.');
    }

    /**
     * Gérer les taux des partenaires pour un business profile
     */
    public function managePartnerRates(BusinessProfile $businessProfile)
    {
        $this->authorize('managePartnerRates', $businessProfile);
        
        $businessProfile->load('pricingPlans'); // Eager load pricing plans
        $partners = $businessProfile->partners()->with('pricingPlans')->get(); // Load associated partners with their pricing plans
        $availablePlans = $businessProfile->pricingPlans ?? \App\Models\PricingPlan::all();

        return view('business-profiles.manage-partner-rates', compact('businessProfile', 'partners', 'availablePlans'));
    }

    /**
     * Appliquer les plans tarifaires aux partenaires pour un business profile
     */
    public function applyPartnerRates(Request $request, BusinessProfile $businessProfile)
    {
        $this->authorize('managePartnerRates', $businessProfile);

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