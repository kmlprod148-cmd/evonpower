<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\Partner;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\IntegratorDataIsolation;
use App\Services\IntegratorPermissionService;

class IntegratorOperatorController extends Controller
{
    use IntegratorDataIsolation;

    protected IntegratorPermissionService $permissionService;

    public function __construct(IntegratorPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des opérateurs de l'intégrateur
     */
    public function index()
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('viewAny', User::class);
        
        $user = auth()->user();
        
        // Filtrer les opérateurs selon les permissions
        $query = User::whereHas('roles', function($q) {
            $q->where('name', 'operator');
        });
        
        // Si l'utilisateur est un intégrateur, ne montrer que ses opérateurs
        // Vérification insensible à la casse
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('integrator', $userRolesLower)) {
            $integratorId = $user->integrator_id;
            $query->where(function($q) use ($user, $integratorId) {
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
        
        $operators = $query->paginate(15);

        return view('integrator.operators.index', compact('operators'));
    }

    /**
     * Affiche le formulaire de création d'un opérateur
     */
    public function create()
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('create', User::class);
        
        $user = auth()->user();
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        
        // Vérification insensible à la casse
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('integrator', $userRolesLower)) {
            // UNIQUEMENT les business profiles de l'intégrateur
            $businessProfiles = $businessProfileService->getIntegratorOwnBusinessProfiles($user);
        } else {
            // admin voit tout
            $businessProfiles = BusinessProfile::where('is_active', true)->get();
        }
        
        // Charger la relation creator pour chaque business profile
        $businessProfiles->load('creator');
        
        // Récupérer les intégrateurs pour l'admin
        $integrators = null;
        if (in_array('admin', $userRolesLower)) {
            $integrators = \App\Models\Integrator::with('user')->get();
        }

        return view('integrator.operators.create', compact('businessProfiles', 'integrators'));
    }

    /**
     * Crée un nouvel opérateur avec business profile
     */
    public function store(Request $request)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('create', User::class);
        
        $user = auth()->user();
        
        // Déterminer l'intégrateur selon le rôle (vérification insensible à la casse)
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        if (in_array('admin', $userRolesLower)) {
            // Admin peut choisir l'intégrateur
            $integratorId = $request->integrator_id;
            if (!$integratorId) {
                return redirect()->back()
                    ->with('error', 'Veuillez sélectionner un intégrateur.')
                    ->withInput();
            }
            $integrator = \App\Models\Integrator::findOrFail($integratorId);
        } else {
            // Intégrateur utilise son propre ID
            $integrator = $user->integrator;
            if (!$integrator) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'required|exists:business_profiles,id',
            'integrator_id' => 'nullable|exists:integrators,id',
        ]);

        // Verify the business profile belongs to allowed set
        $user = auth()->user();
        $allowedBpIds = BusinessProfile::where('is_active', true)
            ->where(function($query) use ($user) {
                $query->where('created_by', $user->id)
                ->orWhereHas('creator', function($creatorQuery) {
                    $creatorQuery->whereHas('roles', function($roleQuery) {
                        $roleQuery->where('name', 'admin');
                    });
                });
            })
            ->pluck('id')->toArray();

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
            
            // Créer l'utilisateur opérateur
            $operator = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'user_type' => 'system',
                'integrator_id' => $integrator->id,
                'is_active' => true,
                'created_by' => $user->id, // Utiliser l'utilisateur actuel (intégrateur)
            ]);
            
            // Assigner le rôle d'opérateur
            $operator->assignRole('operator');
            
            // Créer un partenaire pour l'opérateur avec le business profile
            $partner = Partner::create([
                'name' => $request->name . ' (Opérateur)',
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'integrator_id' => $integrator->id,
                'business_profile_id' => $businessProfile->id,
                'is_active' => true,
            ]);
            
            // Lier l'utilisateur au partenaire
            $user->update(['partner_id' => $partner->id]);
            
            DB::commit();
            
            // Notifier les admins
            User::role('admin')->each(function($admin) use ($user) {
                $admin->notify(new \App\Notifications\NewUserNotification($user));
            });
            
            return redirect()->route('integrator.operators.index')
                ->with('success', 'Opérateur créé avec succès avec le business profile "' . $businessProfile->name . '".');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création opérateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de l\'opérateur.')
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un opérateur
     */
    public function show(User $operator)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('view', $operator);

        // Calculer les statistiques de l'opérateur
        $stats = [
            'total_charging_points' => \App\Models\ChargingPoint::where('user_id', $operator->id)->count(),
            'total_transactions' => \App\Models\Transaction::whereHas('chargingPoint', function($query) use ($operator) {
                $query->where('user_id', $operator->id);
            })->where('status', 'completed')->count(),
            'total_revenue' => \App\Models\Transaction::whereHas('chargingPoint', function($query) use ($operator) {
                $query->where('user_id', $operator->id);
            })->where('status', 'completed')->sum('amount')
        ];

        return view('integrator.operators.show', compact('operator', 'stats'));
    }

    /**
     * Affiche le formulaire d'édition d'un opérateur
     */
    public function edit(User $operator)
    {
        // Utiliser la policy pour vérifier les permissions
        // TEMPORARY: Skip authorization for admin to debug
        if (!auth()->user()->hasRole(['admin', 'super_admin', 'super_admin'])) {
            $this->authorize('update', $operator);
        }

        // Récupérer les business profiles - UNIQUEMENT ceux de l'intégrateur
        $currentUser = auth()->user();
        $businessProfileService = app(\App\Services\BusinessProfileDisplayService::class);
        $businessProfiles = $businessProfileService->getIntegratorOwnBusinessProfiles($currentUser);
        
        // Charger la relation creator pour chaque business profile
        $businessProfiles->load('creator');

        // Récupérer le business profile actuel de l'opérateur
        $currentBusinessProfile = null;
        if ($operator->partner && $operator->partner->businessProfile) {
            $currentBusinessProfile = $operator->partner->businessProfile;
        }

        return view('integrator.operators.edit', compact('operator', 'businessProfiles', 'currentBusinessProfile'));
    }

    /**
     * Met à jour un opérateur et son business profile
     */
    public function update(Request $request, User $operator)
    {
        // Utiliser la policy pour vérifier les permissions
        // TEMPORARY: Skip authorization for admin to debug
        if (!auth()->user()->hasRole(['admin', 'super_admin', 'super_admin'])) {
            $this->authorize('update', $operator);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $operator->id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'required|exists:business_profiles,id',
        ]);

        // Vérifier que le business profile est autorisé pour cet intégrateur
        $user = auth()->user();
        $allowedBpIds = BusinessProfile::where('is_active', true)
            ->where(function($query) use ($user) {
                $query->where(function($subQ) use ($user) {
                    $subQ->where('created_by_type', get_class($user))
                         ->where('created_by_id', $user->id);
                })
                ->orWhere('created_by', $user->id)
                ->orWhereHas('creator', function($creatorQuery) {
                    $creatorQuery->whereHas('roles', function($roleQuery) {
                        $roleQuery->where('name', 'admin');
                    });
                });
            })
            ->pluck('id')->toArray();

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
            
            // Mettre à jour l'utilisateur
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
            ];
            
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            
            $operator->update($updateData);
            
            // Mettre à jour le partenaire associé
            if ($operator->partner) {
                $operator->partner->update([
                    'name' => $request->name . ' (Opérateur)',
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'postal_code' => $request->postal_code,
                    'country' => $request->country,
                    'business_profile_id' => $businessProfile->id,
                ]);
            }
            
            DB::commit();

            return redirect()->route('integrator.operators.index')
                ->with('success', 'Opérateur mis à jour avec succès avec le business profile "' . $businessProfile->name . '".');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour opérateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de l\'opérateur.')
                ->withInput();
        }
    }

    /**
     * Supprime un opérateur
     */
    public function destroy(User $operator)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('delete', $operator);

        try {
            $operator->delete();
            
            return redirect()->route('integrator.operators.index')
                ->with('success', 'Opérateur supprimé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur suppression opérateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'opérateur.');
        }
    }

    /**
     * Active un opérateur
     */
    public function activate(User $operator)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('update', $operator);

        try {
            $operator->update(['is_active' => true]);
            
            return redirect()->back()
                ->with('success', 'Opérateur activé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur activation opérateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'activation de l\'opérateur.');
        }
    }

    /**
     * Désactive un opérateur
     */
    public function deactivate(User $operator)
    {
        // Utiliser la policy pour vérifier les permissions
        $this->authorize('update', $operator);

        try {
            $operator->update(['is_active' => false]);
            
            return redirect()->back()
                ->with('success', 'Opérateur désactivé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur désactivation opérateur: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la désactivation de l\'opérateur.');
        }
    }
}
