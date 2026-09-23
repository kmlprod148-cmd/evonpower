<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\GroupStoreRequest;
use App\Http\Requests\Group\GroupUpdateRequest;
use App\Models\Group;
use App\Models\User;
use App\Models\BusinessProfile;
use Spatie\Permission\Models\Permission;
use App\Repositories\Interfaces\GroupRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    protected $groupRepository;

    public function __construct(GroupRepositoryInterface $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    /**
     * Display a listing of the groups.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Group::forUser(auth()->user());

        // Filtrage par recherche
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filtrage par statut
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status == '1');
        }

        // Filtrage par ville
        if ($request->has('city') && $request->city) {
            $query->where('city', $request->city);
        }

        // Filtrage par profil business
        if ($request->has('business_profile_id') && $request->business_profile_id) {
            $query->where('business_profile_id', $request->business_profile_id);
        }

        // Tri
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        $allowedSortFields = ['name', 'email', 'city', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 15;
        }
        
        $groups = $query->paginate($perPage)->appends($request->query());

        // Statistiques
        // Statistiques optimisées
        $stats = [];
        try {
            $baseQuery = Group::forUser(auth()->user());
            
            $stats['total_count'] = $baseQuery->count();
            $stats['active_count'] = $baseQuery->clone()->where('is_active', true)->count();
            $stats['inactive_count'] = $baseQuery->clone()->where('is_active', false)->count();
            $stats['new_count'] = $baseQuery->clone()->where('created_at', '>=', now()->startOfMonth())->count();
        } catch (\Throwable $e) {
            \Log::warning('Failed to compute group stats: '.$e->getMessage());
        }

        try {
            $stats['charging_points_count'] = DB::table('charging_points')->count();
        } catch (\Throwable $e) {
            \Log::warning('Failed to compute charging points stats: '.$e->getMessage());
            $stats['charging_points_count'] = 0; // Set a default value
        }

        // Liste des villes pour le filtre
        $citiesQuery = Group::forUser(auth()->user());
        $cities = $citiesQuery->whereNotNull('city')
                           ->where('city', '!=', '')
                           ->distinct()
                           ->orderBy('city')
                           ->pluck('city');

        // Liste des profils business pour le filtre
        $businessProfiles = BusinessProfile::where('is_active', true)
                                          ->orderBy('name')
                                          ->get();

        return view('groups.index', compact(
            'groups',
            'stats',
            'cities',
            'businessProfiles'
        ));
    }

    /**
     * Show the form for creating a new group.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $businessProfiles = BusinessProfile::where('is_active', true)
                                          ->orderBy('name')
                                          ->get();
                                          
        return view('groups.create', compact('businessProfiles'));
    }

    /**
     * Store the data for step 1 of group creation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:private,public',
            'name' => 'required|string|max:255',
        ]);
        $validated['user_id'] = auth()->id();
        $user = auth()->user();
        if ($user->partner_id) {
            $validated['partner_id'] = $user->partner_id;
        } elseif ($user->partner) {
            $validated['partner_id'] = $user->partner->id;
        }
        session(['group_step1' => $validated]);
        return redirect()->route('groups.create.step2');
    }

    /**
     * Show the form for step 2 of group creation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createStep2(Request $request)
    {
        $groupStep1Data = $request->session()->get('group_step1');

        if (!$groupStep1Data) {
            return redirect()->route('groups.create')
                ->with('error', 'Les informations du groupe n\'ont pas été trouvées. Veuillez revenir à l\'étape précédente.');
        }

        return view('groups.create.step2', compact('groupStep1Data'));
    }

    /**
     * Store the data for step 2 of group creation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeStep2(Request $request)
    {
        $step1Data = session('group_step1', []);
        $step2Data = $request->all();
        $data = array_merge($step1Data, $step2Data);
        if (empty($data['name'])) {
            return back()->with('error', 'Le nom du groupe est obligatoire.');
        }
        if (empty($data['user_id'])) {
            $data['user_id'] = auth()->id();
        }
        if (empty($data['partner_id'])) {
            $user = auth()->user();
            if ($user->partner_id) {
                $data['partner_id'] = $user->partner_id;
            } elseif ($user->partner) {
                $data['partner_id'] = $user->partner->id;
            }
        }
        $group = \App\Models\Group::create($data);
        session()->forget('group_step1');
        return redirect()->route('groups.index')->with('success', 'Groupe créé avec succès');
    }

    public function store(GroupStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $groupData = $request->validated();
            if (!isset($groupData['partner_id']) && $request->has('partner_id')) {
                $groupData['partner_id'] = $request->partner_id;
            }
            $groupData['is_active'] = $request->has('is_active') ? true : false;
            $groupData['user_id'] = auth()->id();
            if (auth()->user()->hasRole('integrator') && auth()->user()->integrator_id) {
                $groupData['integrator_id'] = auth()->user()->integrator_id;
            }
            if (auth()->user()->hasRole('partner')) {
                $groupData['partner_id'] = auth()->user()->partner_id
                    ?? optional(auth()->user()->partner)->id
                    ?? null;
            }
            $group = Group::create($groupData);
            DB::commit();
            return redirect()->route('groups.index')
                ->with('success', 'Groupe créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du groupe', [
                'error' => $e->getMessage(),
                'data' => $groupData ?? []
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création du groupe.');
        }
    }

    /**
     * Display the specified group.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group)
    {
        $this->authorize('show', $group);
        
        // Charger uniquement les relations qui existent
        $relations = ['partner', 'chargingPoints'];
        
        // Vérifier si la relation stations existe
        if (method_exists($group, 'stations')) {
            $relations[] = 'stations';
        }
        
        $group->load($relations);

        // Paginer les points de charge
        $chargingPoints = $group->chargingPoints()->with('partner')->paginate(10);

        // Statistiques
        $stats = [
            'total_charging_points' => $chargingPoints->total(),
            'online_charging_points' => $group->chargingPoints()->where('status', 'online')->count(),
            'charging_points_status' => $group->chargingPoints()
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'total_power_output' => $group->chargingPoints()->sum('power_output'),
        ];
        
        // Ajouter les stations si la relation existe
        if (method_exists($group, 'stations')) {
            $stats['total_stations'] = $group->stations()->count();
        }

        // Transactions récentes
        $recentTransactions = DB::table('transactions')
            ->join('charging_points', 'transactions.charging_point_id', '=', 'charging_points.id')
            ->where('charging_points.group_id', $group->id)
            ->orderBy('transactions.created_at', 'desc')
            ->limit(10)
            ->select('transactions.*')
            ->get();

        return view('groups.show', compact('group', 'stats', 'recentTransactions', 'chargingPoints'));
    }
/**
     * Display the charging points for the specified group.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function manageChargingPoints(Group $group)
    {
        // Load charging points related to the group
        $chargingPoints = $group->chargingPoints()->paginate(10); // Assuming pagination is desired

        return view('groups.manage-charging-points', compact('group', 'chargingPoints'));
    }

    /**
     * Show the form for editing the specified group.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Group $group)
    {
        $this->authorize('edit', $group);
        $group->load('permissions');
        $businessProfiles = BusinessProfile::where('is_active', true)
                                          ->orderBy('name')
                                          ->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('-', $permission->name)[0];
        });
        $groupPermissions = $group->permissions->pluck('name')->toArray();
                                          
        return view('groups.edit', compact('group', 'businessProfiles', 'permissions', 'groupPermissions'));
    }

    /**
     * Update the specified group in storage.
     *
     * @param  \App\Http\Requests\Group\GroupUpdateRequest  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(GroupUpdateRequest $request, $id)
    {
        $group = $this->groupRepository->findOrFail($id);
        $this->authorize('edit', $group);
        DB::beginTransaction();
        
        try {
            $data = $request->validated();
            
            // Gérer le checkbox is_active
            $data['is_active'] = $request->has('is_active');
            
            // Mettre à jour le groupe
            $updatedGroup = $this->groupRepository->update($group, $data);

            if ($request->has('permissions')) {
                $updatedGroup->syncPermissions($request->permissions);
            }
            
            // Log de l'action
            Log::info('Groupe mis à jour', [
                'group_id' => $group->id,
                'name' => $group->name,
                'updated_by' => auth()->id(),
                'changes' => $group->getChanges()
            ]);

            DB::commit();

            return redirect()->route('groups.show', ['group' => $group->id])
                ->with('success', 'Groupe modifié avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la mise à jour du groupe', [
                'group_id' => $group->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du groupe.');
        }
    }

    /**
     * Remove the specified group from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);
        DB::beginTransaction();
        try {
            if ($group->chargingPoints()->exists()) {
                return redirect()->back()
                    ->withErrors(['error' => 'Impossible de supprimer ce groupe car il a des bornes de recharge associées.'])
                    ->with('error', 'Impossible de supprimer ce groupe car il a ' . $group->chargingPoints()->count() . ' borne(s) de recharge associée(s).');
            }
            $groupName = $group->name;
            $group->delete();
            DB::commit();
            return redirect()->route('groups.index')
                ->with('success', 'Groupe supprimé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression du groupe', [
                'group_id' => $group->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression du groupe.');
        }
    }

    /**
     * Activate the specified group.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function activate(Group $group)
    {
        $this->authorize('update', $group);
        try {
            $this->groupRepository->update($group, ['is_active' => true]);
            
            Log::info('Groupe activé', [
                'group_id' => $group->id,
                'activated_by' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('success', 'Groupe activé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'activation du groupe', [
                'group_id' => $group->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'activation du groupe.');
        }
    }

    /**
     * Deactivate the specified group.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Group $group)
    {
        $this->authorize('update', $group);
        try {
            $this->groupRepository->update($group, ['is_active' => false]);
            
            Log::info('Groupe désactivé', [
                'group_id' => $group->id,
                'deactivated_by' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('success', 'Groupe désactivé avec succès.');
                
        } catch (\Exception $e) {
            Log::error('Erreur lors de la désactivation du groupe', [
                'group_id' => $group->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la désactivation du groupe.');
        }
    }

    /**
     * Export groups data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        try {
            $query = Group::forUser(auth()->user())->with('businessProfile');

            // Appliquer les mêmes filtres que dans index()
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && $request->status !== '') {
                $query->where('is_active', $request->status == '1');
            }

            if ($request->has('city') && $request->city) {
                $query->where('city', $request->city);
            }

            $groups = $query->get();

            // Créer le CSV
            $csvFileName = 'groups_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
            ];

            $columns = ['ID', 'Nom', 'Adresse', 'Ville', 'Pays', 'Profil Business', 'Statut', 'Date de création'];

            $callback = function() use ($groups, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($groups as $group) {
                    fputcsv($file, [
                        $group->id,
                        $group->name,
                        $group->address,
                        $group->city,
                        $group->country,
                        $group->businessProfile ? $group->businessProfile->name : 'N/A',
                        $group->is_active ? 'Actif' : 'Inactif',
                        $group->created_at->format('d/m/Y H:i'),
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export des groupes', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'export des données.');
        }
    }

    /**
     * Bulk action for groups.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'group_ids' => 'required|array',
            'group_ids.*' => 'exists:groups,id'
        ]);

        $action = $request->action;
        $ids = $request->group_ids;
        $count = count($ids);

        try {
            switch ($action) {
                case 'activate':
                    Group::whereIn('id', $ids)->update(['is_active' => true]);
                    $message = "$count groupe(s) activé(s) avec succès.";
                    break;
                    
                case 'deactivate':
                    Group::whereIn('id', $ids)->update(['is_active' => false]);
                    $message = "$count groupe(s) désactivé(s) avec succès.";
                    break;
                    
                case 'delete':
                    // Vérifier qu'aucun groupe n'a de relations
                    $hasRelations = Group::whereIn('id', $ids)
                        ->where(function($query) {
                            $query->has('chargingPoints');
                        })->exists();
                    
                    if ($hasRelations) {
                        return redirect()->back()
                            ->with('error', 'Certains groupes ont des bornes de recharge associées et ne peuvent pas être supprimés.');
                    }
                    
                    Group::whereIn('id', $ids)->delete();
                    $message = "$count groupe(s) supprimé(s) avec succès.";
                    break;
            }

            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'action en masse sur les groupes', [
                'action' => $action,
                'ids' => $ids,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'exécution de l\'action.');
        }
    }
}