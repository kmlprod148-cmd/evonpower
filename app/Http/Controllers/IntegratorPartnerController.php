<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partner;
use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class IntegratorPartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des partenaires et opérateurs de l'intégrateur
     */
    public function index()
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('viewAny', Partner::class);
        
        $user = auth()->user();
        
        // Filtrer les partenaires selon les permissions
        $partnersQuery = Partner::query();
        
        // Si l'utilisateur est un intégrateur, ne montrer que ses partenaires
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id;
            $partnersQuery->where(function($q) use ($user, $integratorId) {
                // Partenaires créés par cet intégrateur (nouveau système polymorphique)
                $q->where(function($subQ) use ($user) {
                    $subQ->where('created_by_type', get_class($user))
                         ->where('created_by_id', $user->id);
                })
                // Partenaires créés par cet intégrateur (ancien système)
                ->orWhere('created_by', $user->id)
                // Partenaires liés à l'intégrateur via integrator_id
                ->orWhere(function($subQ) use ($integratorId) {
                    if ($integratorId) {
                        $subQ->where('integrator_id', $integratorId);
                    }
                });
            });
        }
        
        // Récupérer les opérateurs (Users avec rôle operator)
        $operatorsQuery = User::whereHas('roles', function($q) {
            $q->where('name', 'operator');
        });
        
        // Filtrer les opérateurs selon les permissions
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('integrator', $userRolesLower)) {
            $integratorId = $user->integrator_id;
            $operatorsQuery->where(function($q) use ($user, $integratorId) {
                // Opérateurs créés par cet intégrateur
                $q->where('created_by', $user->id)
                // Opérateurs liés à l'intégrateur via integrator_id
                ->orWhere(function($subQ) use ($integratorId) {
                    if ($integratorId) {
                        $subQ->where('integrator_id', $integratorId);
                    }
                });
            });
        }
        
        // Récupérer tous les partenaires d'abord
        $partnersList = $partnersQuery->get();
        
        // Paginer les partenaires pour compatibilité avec les vues qui utilisent $partners
        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $partnersPage = $partnersList->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $partners = new \Illuminate\Pagination\LengthAwarePaginator(
            $partnersPage,
            $partnersList->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        // Combiner les partenaires et opérateurs dans une collection unifiée
        $allItems = collect();
        foreach ($partnersList as $partner) {
            $allItems->push([
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'phone' => $partner->phone,
                'city' => $partner->city,
                'country' => $partner->country,
                'type' => $partner->type ?? 'Partenaire',
                'is_active' => $partner->is_active,
                'item_type' => 'partner',
                'partner' => $partner,
                'operator' => null,
            ]);
        }
        
        // Récupérer les opérateurs et les marquer
        $operators = $operatorsQuery->with(['partner', 'chargingPoints'])->get();
        foreach ($operators as $operator) {
            $allItems->push([
                'id' => $operator->id,
                'name' => $operator->name,
                'email' => $operator->email,
                'phone' => $operator->phone,
                'city' => $operator->city,
                'country' => $operator->country,
                'type' => 'Opérateur',
                'is_active' => $operator->is_active,
                'item_type' => 'operator',
                'partner' => null,
                'operator' => $operator,
            ]);
        }
        
        // Paginer manuellement la collection combinée
        $perPage = 15;
        $currentPage = request()->get('page', 1);
        $items = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return view('integrator.partners.index', compact('paginatedItems', 'partners'));
    }

    /**
     * Affiche le formulaire de création d'un partenaire
     */
    public function create()
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('create', Partner::class);

        // Récupérer tous les business profiles disponibles pour l'intégrateur
        // Inclut: profils de l'intégrateur + profils de son admin (type operators et publics) + profils publics + profils avec target_audience operators
        $currentUser = auth()->user();
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        $businessProfiles = $businessProfileService->getAvailableBusinessProfilesForIntegrator($currentUser);
        
        // Charger la relation creator pour chaque business profile
        $businessProfiles->load('creator');

        // Récupérer les intégrateurs (pour les admins)
        $integrators = collect();
        if ($currentUser->hasRole('admin')) {
            $integrators = \App\Models\Integrator::with('user')->get();
        }

        return view('integrator.partners.create', compact('businessProfiles', 'integrators'));
    }

    /**
     * Crée un nouveau partenaire
     */
    public function store(Request $request)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('create', Partner::class);
        
        $user = auth()->user();
        $integrator = $user->integrator;
        
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Validation de base
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:partners',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'integrator_id' => 'nullable|exists:integrators,id',
            'type' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'add_to_list' => 'nullable|boolean',
            'create_admin_user' => 'nullable|boolean',
        ];
        
        // Ajouter les règles de validation pour l'utilisateur si la création est demandée
        if ($request->has('create_admin_user') && $request->input('create_admin_user')) {
            $validationRules['admin_name'] = 'required|string|max:255';
            $validationRules['admin_email'] = 'required|email|max:255|unique:users,email';
            $validationRules['admin_password'] = 'required|string|min:8|confirmed';
        }
        
        $request->validate($validationRules);

        // Vérifier que le business profile est autorisé pour cet intégrateur
        // Utiliser la même logique que dans create() et edit()
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        $allowedBusinessProfiles = $businessProfileService->getAvailableBusinessProfilesForIntegrator($user);
        $allowedBpIds = $allowedBusinessProfiles->pluck('id')->toArray();

        if (!in_array($request->business_profile_id, $allowedBpIds)) {
            return redirect()->back()
                ->with('error', 'Business profile non autorisé.')
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Vérifier que le business profile existe et est actif
            $businessProfile = BusinessProfile::where('id', $request->business_profile_id)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Déterminer l'intégrateur responsable
            $responsibleIntegratorId = $request->integrator_id ?? $integrator->id;
            
            // Récupérer l'intégrateur responsable pour created_by
            $responsibleIntegrator = \App\Models\Integrator::find($responsibleIntegratorId);
            
            if (!$responsibleIntegrator) {
                throw new \Exception("Intégrateur introuvable.");
            }
            
            // Créer le partenaire
            $partner = Partner::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'type' => $request->type ?? 'partner',
                'description' => $request->description,
                'integrator_id' => $responsibleIntegratorId,
                'created_by' => $responsibleIntegrator->user_id, // Utiliser l'utilisateur de l'intégrateur
                'created_by_type' => get_class($responsibleIntegrator),
                'created_by_id' => $responsibleIntegrator->id,
                'business_profile_id' => $businessProfile->id,
                'is_active' => true,
                'add_to_list' => $request->has('add_to_list'),
            ]);
            
            // Créer un compte utilisateur si demandé
            if ($request->has('create_admin_user') && $request->input('create_admin_user')) {
                $user = User::create([
                    'name' => $request->input('admin_name'),
                    'email' => $request->input('admin_email'),
                    'password' => Hash::make($request->input('admin_password')),
                    'integrator_id' => $responsibleIntegratorId,
                    'partner_id' => $partner->id,
                    'created_by' => $responsibleIntegrator->user_id,
                ]);
                
                // Assigner le rôle operator
                if (method_exists($user, 'assignRole')) {
                    $user->assignRole('operator');
                }
            }
            
            DB::commit();
            
            $successMessage = 'Partenaire créé avec succès avec le business profile "' . $businessProfile->name . '".';
            if ($request->has('create_admin_user') && $request->input('create_admin_user')) {
                $successMessage .= ' Compte utilisateur créé avec succès.';
            }
            
            return redirect()->route('integrator.partners.index')
                ->with('success', $successMessage);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création partenaire: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création du partenaire.')
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un partenaire
     */
    public function show(Partner $partner)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('view', $partner);

        // Calculer les statistiques du partenaire
        $stats = [
            'total_charging_points' => $partner->chargingPoints()->count(),
            'active_charging_points' => $partner->chargingPoints()->where('status', 'online')->count(),
            'total_transactions' => $partner->chargingPoints()
                ->withCount('transactions')
                ->get()
                ->sum('transactions_count'),
        ];

        return view('integrator.partners.show', compact('partner', 'stats'));
    }

    /**
     * Affiche le formulaire d'édition d'un partenaire
     */
    public function edit(Partner $partner)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('update', $partner);

        // Récupérer tous les business profiles disponibles pour l'intégrateur
        // Inclut: profils de l'intégrateur + profils de son admin (type operators et publics) + profils publics + profils avec target_audience operators
        $currentUser = auth()->user();
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        $businessProfiles = $businessProfileService->getAvailableBusinessProfilesForIntegrator($currentUser);
        
        // Charger la relation creator pour chaque business profile
        $businessProfiles->load('creator');

        return view('integrator.partners.edit', compact('partner', 'businessProfiles'));
    }

    /**
     * Met à jour un partenaire
     */
    public function update(Request $request, Partner $partner)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('update', $partner);

        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:partners,email,' . $partner->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'type' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'add_to_list' => 'nullable|boolean',
        ]);

        // Vérifier que le business profile est autorisé pour cet intégrateur
        // Utiliser la même logique que dans create() et edit()
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        $allowedBusinessProfiles = $businessProfileService->getAvailableBusinessProfilesForIntegrator($user);
        $allowedBpIds = $allowedBusinessProfiles->pluck('id')->toArray();

        if (!in_array($request->business_profile_id, $allowedBpIds)) {
            return redirect()->back()
                ->with('error', 'Business profile non autorisé.')
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Vérifier que le business profile existe et est actif
            $businessProfile = BusinessProfile::where('id', $request->business_profile_id)
                ->where('is_active', true)
                ->firstOrFail();
            
            // Mettre à jour le partenaire
            $partner->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'type' => $request->type ?? 'partner',
                'description' => $request->description,
                'business_profile_id' => $businessProfile->id,
                'add_to_list' => $request->has('add_to_list'),
            ]);
            
            DB::commit();

            return redirect()->route('integrator.partners.index')
                ->with('success', 'Partenaire mis à jour avec succès avec le business profile "' . $businessProfile->name . '".');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour partenaire: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour du partenaire.')
                ->withInput();
        }
    }

    /**
     * Supprime un partenaire
     */
    public function destroy(Partner $partner)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('delete', $partner);

        try {
            $partner->delete();
            
            return redirect()->route('integrator.partners.index')
                ->with('success', 'Partenaire supprimé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur suppression partenaire: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression du partenaire.');
        }
    }

    /**
     * Active un partenaire
     */
    public function activate(Partner $partner)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('activate', $partner);
        

        try {
            $partner->update(['is_active' => true]);
            
            return redirect()->back()
                ->with('success', 'Partenaire activé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur activation partenaire: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'activation du partenaire.');
        }
    }

    /**
     * Désactive un partenaire
     */
    public function deactivate(Partner $partner)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('activate', $partner);
        

        try {
            $partner->update(['is_active' => false]);
            
            return redirect()->back()
                ->with('success', 'Partenaire désactivé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur désactivation partenaire: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la désactivation du partenaire.');
        }
    }
}
