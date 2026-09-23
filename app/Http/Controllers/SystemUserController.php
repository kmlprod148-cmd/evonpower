<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste de tous les opérateurs système créés par les intégrateurs
     */
    public function index()
    {
        // Vérifier les permissions selon le rôle de l'utilisateur
        if (auth()->user()->hasRole('integrator')) {
            // Les intégrateurs voient tous leurs opérateurs et ceux de leurs sous-intégrateurs
            $integrator = auth()->user()->integrator;
            if (!$integrator) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
            }

            $operators = User::whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->where(function($query) use ($integrator) {
                    $query->where('integrator_id', $integrator->id)
                          ->orWhereHas('integrator', function($subQuery) use ($integrator) {
                              $subQuery->where('parent_integrator_id', $integrator->id);
                          });
                })
                ->with(['integrator', 'partner', 'roles'])
                ->paginate(15);

        } elseif (auth()->user()->hasRole('partner')) {
            // Les partenaires voient leurs opérateurs
            $partner = auth()->user()->partner;
            if (!$partner) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
            }

            $operators = User::whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->where('partner_id', $partner->id)
                ->with(['integrator', 'partner', 'roles'])
                ->paginate(15);

        } elseif (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin')) {
            // Les admins voient tous les opérateurs système
            $operators = User::whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->where('user_type', 'system')
                ->with(['integrator', 'partner', 'roles'])
                ->paginate(15);

        } else {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé.');
        }

        return view('admin.system-users.index', compact('operators'));
    }

    public function create()
    {
        // Afficher un formulaire de création (exemple API)
        return response()->json(['message' => 'Formulaire de création utilisateur système']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:system_users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
        ]);
        $validated['password'] = bcrypt($validated['password']);
        $user = SystemUser::create($validated);
        return response()->json($user, 201);
    }

    public function show($id)
    {
        $user = SystemUser::findOrFail($id);
        return response()->json($user);
    }

    public function edit($id)
    {
        $user = SystemUser::findOrFail($id);
        // Afficher un formulaire d'édition (exemple API)
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = SystemUser::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:system_users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|required|string',
        ]);
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }
        $user->update($validated);
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = SystemUser::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }
} 