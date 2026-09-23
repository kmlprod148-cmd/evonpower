<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Http\Requests\Partner\PartnerStoreRequest;
use App\Http\Requests\Partner\PartnerUpdateRequest;

use App\Repositories\Interfaces\PartnerRepositoryInterface;
use App\Services\PartnerService; // Add PartnerService import
use App\Exceptions\CrudException; // Add CrudException import

class PartnerController extends Controller
{
    private PartnerRepositoryInterface $repository;
    private PartnerService $service; // Add PartnerService property

    public function __construct(PartnerRepositoryInterface $repository, PartnerService $service) // Inject PartnerService
    {
        $this->repository = $repository;
        $this->service = $service; // Assign PartnerService
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $query = Partner::query()
            ->withCount('chargingPoints');

        // Rôle / visibilité
        if ($user->hasRole(['integrator', 'Integrator'])) {
            $query->where(function ($q) use ($user) {
                $q->where('integrator_id', $user->integrator_id)
                  ->orWhere(function ($sub) use ($user) {
                      $sub->where('created_by_type', get_class($user))
                          ->where('created_by_id', $user->id);
                  });
            });
        } elseif ($user->hasRole(['partner'])) {
            $query->where('id', $user->partner_id);
        }
        // Admin / super admin voient tout, aucun filtre supplémentaire

        // Filtres simples
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $type = $request->get('type');
            $query->where('type', 'like', '%' . $type . '%');
        }

        if ($request->filled('city')) {
            $query->where('city', $request->get('city'));
        }

        if ($request->filled('integrator_id')) {
            $query->where('integrator_id', $request->get('integrator_id'));
        }

        $partners = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        // Données pour les filtres (liste des villes et intégrateurs)
        $cities = Partner::whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $integrators = \App\Models\Integrator::orderBy('name')->get();

        return view('partners.index', compact('partners', 'cities', 'integrators'));
    }

    public function create()
    {
        $user = auth()->user();
        
        // Utiliser le service BusinessProfileDisplayService
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin'])) {
            // Admin voit tous les business profiles
            $businessProfiles = BusinessProfile::where('is_active', true)->get();
        } else {
            // Pour les intégrateurs : leurs propres profils + ceux des admins
            $businessProfiles = $businessProfileService->getVisibleBusinessProfilesForIntegrator($user);
        }
        
        // Charger la relation creator pour chaque business profile
        $businessProfiles->load('creator');
        
        $integrators = \App\Models\Integrator::orderBy('name')->get();

        return view('partners.create', compact('businessProfiles', 'integrators'));
    }

    public function store(PartnerStoreRequest $request)
    {
        Log::info('PartnerController store method hit.');
        Log::info('User ID: ' . (auth()->id() ?? 'Guest'));
        if (auth()->check()) {
            Log::info('User roles: ' . implode(', ', auth()->user()->getRoleNames()->toArray()));
        }
        Log::info('Request data: ' . json_encode($request->all()));

        $validated = $request->validated();
        $user = auth()->user();

        Log::info('PartnerController store - User roles for hasRole check: ' . implode(', ', $user->getRoleNames()->toArray()));
        Log::info('PartnerController store - Is user admin (hasRole check): ' . ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin']) ? 'true' : 'false'));

        // Vérifier que le business_profile_id choisi est autorisé pour cet utilisateur
        // Admins can use any business profile, so bypass this check for them
        if (!$user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin'])) {
            $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
            $allowedBusinessProfiles = $businessProfileService->getVisibleBusinessProfilesForIntegrator($user);
            $allowedBpIds = $allowedBusinessProfiles->pluck('id')->toArray();

            if (!in_array($request->business_profile_id, $allowedBpIds)) {
                abort(403, 'Business profile non autorisé');
            }
        }

        // On ne valide plus ici le champ integrator_id, la règle du FormRequest suffit
        if ($user->hasRole('integrator')) {
             // Si intégrateur, on force son propre integrator_id
             $validated['integrator_id'] = $user->integrator_id;
             // Utiliser l'ancien système pour l'instant
             $validated['created_by'] = $user->id;
        }

        try {
            // Use the service to handle partner creation and associated user creation
            $partner = $this->service->createPartnerWithAdminUser($validated, $request->input('admin_email'), $request->input('admin_password')); // Call service method

            return redirect()->route('partners.index')
                ->with('success', 'Partenaire créé avec succès. L\'administrateur peut se connecter avec l\'email et le mot de passe fournis.');
        } catch (CrudException $e) { // Catch CrudException
            Log::error('Error creating partner: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', $e->getMessage()) // Use the exception message
                ->withInput();
        } catch (\Exception $e) { // Keep generic exception for unexpected errors
            Log::error('Unexpected error creating partner: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'An unexpected error occurred while creating the partner.')
                ->withInput();
        }
    }

public function show(Partner $partner)
{
    // Contournement temporaire pour les admins (robuste aux variantes)
    if (!auth()->user()->hasRole(['admin','Admin','super_admin','super_admin','Super Admin','Super-Admin'])) {
        $this->authorize('view', $partner);
    }
    // Charger toutes les relations nécessaires
    $partner->load([
        'businessProfile',
        'integrator',
        'users' => function($query) {
            $query->with('roles');
        },
        'chargingPoints' => function($query) {
            $query->withCount('connectors');
        },
        'groups'
    ]);

    // Statistiques détaillées
    $stats = [
        'total_charging_points' => $partner->chargingPoints->count(),
        'active_charging_points' => $partner->chargingPoints->where('status', 'online')->count(),
        'total_users' => $partner->users->count(),
        'total_groups' => $partner->groups->count(),
        'total_connectors' => $partner->chargingPoints->sum('connectors_count'),
    ];

    return view('partners.show', compact('partner', 'stats'));
}

    public function edit(Partner $partner)
    {
        // Contournement temporaire pour les admins (robuste aux variantes)
        if (!auth()->user()->hasRole(['admin','Admin','super_admin','super_admin','Super Admin','Super-Admin'])) {
            $this->authorize('update', $partner);
        }

        // Charger le partenaire avec ses relations
        $partner->load(['businessProfile', 'integrator']);
        
        // Récupérer les BusinessProfiles selon le rôle de l'utilisateur
        $user = auth()->user();
        
        // Utiliser le service BusinessProfileDisplayService
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        
        if ($user->hasRole(['admin','Admin'])) {
            // Admin voit tous les business profiles
            $businessProfiles = BusinessProfile::where('is_active', true)->get();
        } else {
            // Pour les intégrateurs : leurs propres profils + ceux des admins
            $businessProfiles = $businessProfileService->getVisibleBusinessProfilesForIntegrator($user);
        }
        
        // Charger la relation creator pour chaque business profile
        $businessProfiles->load('creator');
        
        $integrators = \App\Models\Integrator::orderBy('name')->get();

        return view('partners.edit', compact('partner', 'businessProfiles', 'integrators'));
    }

    public function update(PartnerUpdateRequest $request, Partner $partner)
    {
        $this->authorize('update', $partner);
        
        try {
            DB::beginTransaction();
            
            $validated = $request->validated();
            
            // Mettre à jour le partenaire
            $partner->update($validated);
            
            // Gérer le logo si fourni
            if ($request->hasFile('logo')) {
                if ($partner->logo) {
                    \Storage::disk('public')->delete($partner->logo);
                }
                
                $logoPath = $request->file('logo')->store('logos/partners', 'public');
                $partner->update(['logo' => $logoPath]);
            }
            
            DB::commit();
            
            return redirect()->route('partners.show', $partner)
                ->with('success', 'Partenaire mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la mise à jour du partenaire: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour.')
                ->withInput();
        }
    }

    public function destroy(Partner $partner)
    {
        Log::info('PartnerController@destroy: Attempting to delete partner.', [
            'user_id' => auth()->id(), 
            'partner_id' => $partner->id,
            'partner_name' => $partner->name
        ]);
        
        $this->authorize('delete', $partner);
        Log::info('PartnerController@destroy: Authorization successful for partner deletion.', [
            'user_id' => auth()->id(), 
            'partner_id' => $partner->id
        ]);
        
        try {
            // Vérifier les relations avant suppression
            $relations = $this->checkPartnerRelations($partner);
            if (!empty($relations)) {
                Log::warning('PartnerController@destroy: Partner has relations that will be deleted', [
                    'partner_id' => $partner->id,
                    'relations' => $relations
                ]);
            }
            
            // Use the service to handle partner deletion with proper constraint handling
            $result = $this->service->delete($partner->id);
            
            if ($result) {
                Log::info('PartnerController@destroy: Partner deleted successfully.', [
                    'user_id' => auth()->id(), 
                    'partner_id' => $partner->id
                ]);

                return redirect()->route('partners.index')
                    ->with('success', 'Partenaire et toutes ses données associées ont été supprimés avec succès.');
            } else {
                Log::error('PartnerController@destroy: Partner deletion returned false', [
                    'user_id' => auth()->id(), 
                    'partner_id' => $partner->id
                ]);
                
                return redirect()->back()
                    ->with('error', 'La suppression du partenaire a échoué.');
            }
            
        } catch (CrudException $e) {
            Log::error('PartnerController@destroy: CrudException during partner deletion', [
                'user_id' => auth()->id(),
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
                
        } catch (\Exception $e) {
            Log::error('PartnerController@destroy: Unexpected error during partner deletion', [
                'user_id' => auth()->id(),
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Une erreur inattendue s\'est produite lors de la suppression du partenaire. Veuillez consulter les logs pour plus de détails.');
        }
    }

    /**
     * Check partner relations before deletion.
     *
     * @param Partner $partner
     * @return array
     */
    private function checkPartnerRelations(Partner $partner): array
    {
        $relations = [];
        
        // Charger les relations
        $partner->load(['users', 'chargingPoints', 'groups']);
        
        if ($partner->users->count() > 0) {
            $relations['users'] = $partner->users->count();
        }
        
        if ($partner->chargingPoints->count() > 0) {
            $relations['charging_points'] = $partner->chargingPoints->count();
        }
        
        if ($partner->groups->count() > 0) {
            $relations['groups'] = $partner->groups->count();
        }
        
        return $relations;
    }
}