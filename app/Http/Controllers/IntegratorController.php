<?php

namespace App\Http\Controllers;

use App\Models\Integrator;
use App\Models\User;
use App\Services\IntegratorDisplayService;
use App\Services\IntegratorPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IntegratorController extends Controller
{
    protected $integratorDisplayService;

    public function __construct(IntegratorDisplayService $integratorDisplayService)
    {
        $this->integratorDisplayService = $integratorDisplayService;
    }

    /**
     * Afficher la liste des intégrateurs visibles pour l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Obtenir les intégrateurs visibles pour cet utilisateur
        $integrators = $this->integratorDisplayService->getVisibleIntegratorsForUser($user);
        
        return view('integrators.index', compact('integrators'));
    }

    /**
     * Afficher les détails d'un intégrateur
     */
    public function show(Integrator $integrator)
    {
        try {
            $user = auth()->user();
            
            // Vérifier que l'utilisateur peut voir cet intégrateur
            $visibleIntegrators = $this->integratorDisplayService->getVisibleIntegratorsForUser($user);
            
            if (!$visibleIntegrators->pluck('id')->contains($integrator->id)) {
                abort(403, 'Vous n\'avez pas l\'autorisation de voir cet intégrateur.');
            }
            
            // Charger les relations nécessaires avec gestion d'erreur
            try {
                $integrator->load(['user', 'businessProfile', 'partners', 'operators', 'chargingPoints']);
            } catch (\Exception $e) {
                Log::warning('Error loading integrator relations', [
                    'integrator_id' => $integrator->id,
                    'error' => $e->getMessage()
                ]);
                // Continuer sans les relations si elles échouent
            }
            
            return view('integrators.show', compact('integrator'));
        } catch (\Exception $e) {
            Log::error('Error showing integrator', [
                'integrator_id' => $integrator->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('integrators.index')
                ->with('error', 'Une erreur est survenue lors du chargement des détails de l\'intégrateur.');
        }
    }

    /**
     * Afficher le formulaire de création d'un intégrateur
     */
    public function create()
    {
        $this->authorize('create_integrators');
        
        // Charger les profils business disponibles
        $businessProfiles = \App\Models\BusinessProfile::where('is_active', true)->get();
        
        return view('integrators.create', compact('businessProfiles'));
    }

    /**
     * Enregistrer un nouvel intégrateur
     */
    public function store(Request $request)
    {
        $this->authorize('create_integrators');
        
        $request->validate([
            // Champs utilisateur
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            
            // Champs intégrateur
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:integrators,email',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'website' => 'nullable|url',
            'description' => 'nullable|string|max:1000',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'is_active' => 'boolean',
        ]);
        
        // Créer le compte utilisateur
        $user = \App\Models\User::create([
            'name' => $request->user_name,
            'email' => $request->user_email,
            'password' => \Hash::make($request->password),
            'email_verified_at' => now(),
            'created_by' => auth()->id(),
        ]);
        
        // Assigner le rôle intégrateur
        $user->assignRole('integrator');
        
        // S'assurer que le rôle a toutes les permissions nécessaires
        try {
            \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to assign integrator permissions: " . $e->getMessage());
        }
        
        // Créer l'intégrateur
        $integrator = Integrator::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city' => $request->city,
            'address' => $request->address,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'contact_name' => $request->contact_name,
            'website' => $request->website,
            'description' => $request->description,
            'business_profile_id' => $request->business_profile_id,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);
        
        // Lier l'utilisateur à l'intégrateur
        $user->update(['integrator_id' => $integrator->id]);
        
        // Assigner automatiquement toutes les permissions nécessaires
        // S'assurer que le rôle existe avec toutes les permissions, puis les assigner à l'utilisateur
        IntegratorPermissionService::assignIntegratorPermissions($user);
        
        return redirect()->route('integrators.show', $integrator)
            ->with('success', 'Intégrateur et compte utilisateur créés avec succès.');
    }

    /**
     * Afficher le formulaire d'édition d'un intégrateur
     */
    public function edit(Integrator $integrator)
    {
        $this->authorize('edit_integrators');
        
        $user = auth()->user();
        
        // Vérifier que l'utilisateur peut modifier cet intégrateur
        $visibleIntegrators = $this->integratorDisplayService->getVisibleIntegratorsForUser($user);
        
        if (!$visibleIntegrators->pluck('id')->contains($integrator->id)) {
            abort(403, 'Vous n\'avez pas l\'autorisation de modifier cet intégrateur.');
        }
        
        // Charger les profils business disponibles
        $businessProfiles = \App\Models\BusinessProfile::where('is_active', true)->get();
        
        return view('integrators.edit', compact('integrator', 'businessProfiles'));
    }

    /**
     * Mettre à jour un intégrateur
     */
    public function update(Request $request, Integrator $integrator)
    {
        try {
            $this->authorize('edit_integrators');
            
            $user = auth()->user();
            
            // Vérifier que l'utilisateur peut modifier cet intégrateur
            $visibleIntegrators = $this->integratorDisplayService->getVisibleIntegratorsForUser($user);
            
            if (!$visibleIntegrators->pluck('id')->contains($integrator->id)) {
                abort(403, 'Vous n\'avez pas l\'autorisation de modifier cet intégrateur.');
            }
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:integrators,email,' . $integrator->id,
                'phone' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:255',
                'postal_code' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:100',
                'contact_name' => 'nullable|string|max:255',
                'website' => 'nullable|url',
                'description' => 'nullable|string|max:1000',
                'is_active' => 'boolean',
            ]);
            
            $integrator->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'city' => $request->city,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'contact_name' => $request->contact_name,
                'website' => $request->website,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'),
            ]);
            
            return redirect()->route('integrators.show', $integrator)
                ->with('success', 'Intégrateur mis à jour avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('Unauthorized update attempt', [
                'user_id' => auth()->id(),
                'integrator_id' => $integrator->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('integrators.index')
                ->with('error', 'Vous n\'avez pas l\'autorisation de modifier cet intégrateur.');
        } catch (\Exception $e) {
            Log::error('Error updating integrator', [
                'user_id' => auth()->id(),
                'integrator_id' => $integrator->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.')
                ->withInput();
        }
    }

    /**
     * Supprimer un intégrateur
     */
    public function destroy(Integrator $integrator)
    {
        $this->authorize('delete_integrators');
        
        $user = auth()->user();
        
        // Vérifier que l'utilisateur peut supprimer cet intégrateur
        $visibleIntegrators = $this->integratorDisplayService->getVisibleIntegratorsForUser($user);
        
        if (!$visibleIntegrators->pluck('id')->contains($integrator->id)) {
            abort(403, 'Vous n\'avez pas l\'autorisation de supprimer cet intégrateur.');
        }
        
        // Vérifier s'il y a des dépendances
        if ($integrator->operators()->count() > 0) {
            return redirect()->route('integrators.index')
                ->with('error', 'Impossible de supprimer cet intégrateur car il a des opérateurs associés.');
        }
        
        if ($integrator->chargingPoints()->count() > 0) {
            return redirect()->route('integrators.index')
                ->with('error', 'Impossible de supprimer cet intégrateur car il a des points de charge associés.');
        }
        
        $integrator->delete();
        
        return redirect()->route('integrators.index')
            ->with('success', 'Intégrateur supprimé avec succès.');
    }

    /**
     * Activer un intégrateur
     */
    public function activate(Integrator $integrator)
    {
        $this->authorize('edit_integrators');
        
        $integrator->update(['is_active' => true]);
        
        return redirect()->back()
            ->with('success', 'Intégrateur activé avec succès.');
    }

    /**
     * Désactiver un intégrateur
     */
    public function deactivate(Integrator $integrator)
    {
        $this->authorize('edit_integrators');
        
        $integrator->update(['is_active' => false]);
        
        return redirect()->back()
            ->with('success', 'Intégrateur désactivé avec succès.');
    }

    /**
     * Exporter la liste des intégrateurs
     */
    public function export(Request $request)
    {
        $this->authorize('view_integrators');
        
        $user = auth()->user();
        $integrators = $this->integratorDisplayService->getVisibleIntegratorsForUser($user);
        
        // Logique d'export (CSV, Excel, etc.)
        // Pour l'instant, retourner une réponse JSON
        return response()->json([
            'success' => true,
            'data' => $integrators,
            'message' => 'Export des intégrateurs généré avec succès.'
        ]);
    }

    /**
     * Obtenir les intégrateurs pour un select/dropdown (API)
     */
    public function getForSelect(Request $request)
    {
        $user = auth()->user();
        
        // Obtenir les intégrateurs pour un select/dropdown
        $integrators = $this->integratorDisplayService->getIntegratorsForSelect($user);
        
        return response()->json($integrators);
    }

    /**
     * Obtenir les intégrateurs avec leurs détails (API)
     */
    public function getWithDetails(Request $request)
    {
        $user = auth()->user();
        
        // Obtenir les intégrateurs avec leurs détails
        $integrators = $this->integratorDisplayService->getIntegratorsWithDetails($user);
        
        return response()->json($integrators);
    }
}