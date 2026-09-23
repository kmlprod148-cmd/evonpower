<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Traits\IntegratorDataIsolation;
use Illuminate\Support\Facades\Gate;

class SystemPermissionController extends Controller
{
    use IntegratorDataIsolation;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des permissions système pour l'intégrateur
     */
    public function index()
    {
        Gate::authorize('view_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Les intégrateurs ne voient que les permissions liées à leurs opérateurs
        $permissions = Permission::whereHas('users', function($query) use ($integrator) {
                $query->where('integrator_id', $integrator->id)
                      ->whereHas('roles', function($roleQuery) {
                          $roleQuery->where('name', 'operator');
                      });
            })
            ->with(['users' => function($query) use ($integrator) {
                $query->where('integrator_id', $integrator->id);
            }])
            ->paginate(15);

        return view('integrator.system-permissions.index', compact('permissions'));
    }

    /**
     * Affiche le formulaire de création d'une permission
     */
    public function create()
    {
        Gate::authorize('create_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Récupérer les opérateurs de l'intégrateur
        $operators = \App\Models\User::where('integrator_id', $integrator->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->get();

        return view('integrator.system-permissions.create', compact('operators'));
    }

    /**
     * Crée une nouvelle permission
     */
    public function store(Request $request)
    {
        Gate::authorize('create_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'operator_ids' => 'required|array|min:1',
            'operator_ids.*' => 'exists:users,id'
        ]);

        // Vérifier que tous les opérateurs appartiennent à l'intégrateur
        $operators = \App\Models\User::whereIn('id', $request->operator_ids)
            ->where('integrator_id', $integrator->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->get();

        if ($operators->count() !== count($request->operator_ids)) {
            return redirect()->back()
                ->with('error', 'Certains opérateurs sélectionnés ne vous appartiennent pas.')
                ->withInput();
        }

        try {
            $permission = Permission::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
            ]);

            // Assigner la permission aux opérateurs
            foreach ($operators as $operator) {
                $operator->givePermissionTo($permission);
            }

            return redirect()->route('integrator.system-permissions.index')
                ->with('success', 'Permission créée avec succès.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la permission.')
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'une permission
     */
    public function show(Permission $permission)
    {
        Gate::authorize('view_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Vérifier que la permission est liée aux opérateurs de l'intégrateur
        $hasAccess = $permission->users()->where('integrator_id', $integrator->id)->exists();
        if (!$hasAccess) {
            abort(403, 'Accès non autorisé à cette permission.');
        }

        $permission->load(['users' => function($query) use ($integrator) {
            $query->where('integrator_id', $integrator->id);
        }]);

        return view('integrator.system-permissions.show', compact('permission'));
    }

    /**
     * Affiche le formulaire d'édition d'une permission
     */
    public function edit(Permission $permission)
    {
        Gate::authorize('edit_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Vérifier que la permission est liée aux opérateurs de l'intégrateur
        $hasAccess = $permission->users()->where('integrator_id', $integrator->id)->exists();
        if (!$hasAccess) {
            abort(403, 'Accès non autorisé à cette permission.');
        }

        // Récupérer les opérateurs de l'intégrateur
        $operators = \App\Models\User::where('integrator_id', $integrator->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->get();

        // Récupérer les opérateurs qui ont déjà cette permission
        $assignedOperatorIds = $permission->users()
            ->where('integrator_id', $integrator->id)
            ->pluck('users.id')
            ->toArray();

        return view('integrator.system-permissions.edit', compact('permission', 'operators', 'assignedOperatorIds'));
    }

    /**
     * Met à jour une permission
     */
    public function update(Request $request, Permission $permission)
    {
        Gate::authorize('edit_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Vérifier que la permission est liée aux opérateurs de l'intégrateur
        $hasAccess = $permission->users()->where('integrator_id', $integrator->id)->exists();
        if (!$hasAccess) {
            abort(403, 'Accès non autorisé à cette permission.');
        }

        $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'operator_ids' => 'required|array|min:1',
            'operator_ids.*' => 'exists:users,id'
        ]);

        // Vérifier que tous les opérateurs appartiennent à l'intégrateur
        $operators = \App\Models\User::whereIn('id', $request->operator_ids)
            ->where('integrator_id', $integrator->id)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->get();

        if ($operators->count() !== count($request->operator_ids)) {
            return redirect()->back()
                ->with('error', 'Certains opérateurs sélectionnés ne vous appartiennent pas.')
                ->withInput();
        }

        try {
            $permission->update([
                'display_name' => $request->display_name,
                'description' => $request->description,
            ]);

            // Retirer la permission de tous les opérateurs de l'intégrateur
            $permission->users()->where('integrator_id', $integrator->id)->each(function($user) use ($permission) {
                $user->revokePermissionTo($permission);
            });

            // Assigner la permission aux nouveaux opérateurs
            foreach ($operators as $operator) {
                $operator->givePermissionTo($permission);
            }

            return redirect()->route('integrator.system-permissions.index')
                ->with('success', 'Permission mise à jour avec succès.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la permission.')
                ->withInput();
        }
    }

    /**
     * Supprime une permission
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('delete_system_permissions');
        
        $integrator = auth()->user()->integrator;
        if (!$integrator) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        // Vérifier que la permission est liée aux opérateurs de l'intégrateur
        $hasAccess = $permission->users()->where('integrator_id', $integrator->id)->exists();
        if (!$hasAccess) {
            abort(403, 'Accès non autorisé à cette permission.');
        }

        try {
            // Retirer la permission de tous les opérateurs de l'intégrateur
            $permission->users()->where('integrator_id', $integrator->id)->each(function($user) use ($permission) {
                $user->revokePermissionTo($permission);
            });

            // Supprimer la permission si elle n'est plus utilisée
            if ($permission->users()->count() === 0) {
                $permission->delete();
            }

            return redirect()->route('integrator.system-permissions.index')
                ->with('success', 'Permission supprimée avec succès.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la permission.');
        }
    }
}