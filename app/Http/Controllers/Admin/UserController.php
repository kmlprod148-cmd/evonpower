<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Integrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        // Récupérer les utilisateurs depuis la base de données avec leurs rôles
        $users = User::with('roles')
            ->paginate(50)
            ->through(function ($user) {
                $user->role = $user->roles->first() ? $user->roles->first()->name : 'user';
                return $user;
            });

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        // Récupérer les intégrateurs pour la sélection
        $integrators = \App\Models\Integrator::with('user')->get();
        
        return view('admin.users.create', compact('integrators'));
    }

    public function store(Request $request)
    {
        // Règles de validation de base
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,integrator,partner,operator',
            'integrator_id' => 'nullable|exists:integrators,id',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
        
        // Validation conditionnelle pour integrator_id
        if (in_array($request->role, ['partner', 'operator'])) {
            $rules['integrator_id'] = 'required|exists:integrators,id';
        }
        
        $messages = [
            'integrator_id.required' => 'L\'intégrateur responsable est requis pour les partenaires et opérateurs.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Déterminer le created_by selon le rôle
            $createdBy = auth()->id(); // Par défaut, l'admin qui crée
            $createdByType = get_class(auth()->user());
            $createdById = auth()->id();
            
            // Si c'est un partenaire ou opérateur, utiliser l'intégrateur sélectionné
            if (in_array($request->role, ['partner', 'operator']) && $request->integrator_id) {
                $integrator = \App\Models\Integrator::find($request->integrator_id);
                if ($integrator) {
                    $createdBy = $integrator->user_id;
                    $createdByType = get_class($integrator);
                    $createdById = $integrator->id;
                }
            }
            
            // Créer l'utilisateur avec les champs de traçabilité
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => $request->boolean('is_active'),
                'created_by' => $createdBy,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
                'integrator_id' => in_array($request->role, ['operator', 'partner']) ? $request->integrator_id : null,
            ] + $this->getOptionalUserProfileAttributes($request));
            
            // Assigner le rôle
            $user->assignRole($request->role);

            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $user = User::with(['roles', 'integrator'])->findOrFail($id);
        $user->role = $user->roles->first() ? $user->roles->first()->name : 'user';

        return view('admin.users.show', compact('user', 'id'));
    }

    public function edit($id)
    {
        $user = User::with(['roles', 'integrator'])->findOrFail($id);
        $user->role = $user->roles->first() ? $user->roles->first()->name : 'user';
        
        // Récupérer les intégrateurs pour la sélection
        $integrators = Integrator::with('user')->get();

        return view('admin.users.edit', compact('user', 'id', 'integrators'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,integrator,partner,operator',
            'integrator_id' => 'nullable|exists:integrators,id',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];

        if (in_array($request->role, ['partner', 'operator'])) {
            $rules['integrator_id'] = 'required|exists:integrators,id';
        }

        $validator = Validator::make($request->all(), $rules, [
            'integrator_id.required' => 'L\'intégrateur responsable est requis pour les partenaires et opérateurs.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user = User::findOrFail($id);
            
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'is_active' => $request->boolean('is_active'),
                'integrator_id' => in_array($request->role, ['partner', 'operator']) ? $request->integrator_id : null,
            ] + $this->getOptionalUserProfileAttributes($request));
            
            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }
            
            // Mettre à jour le rôle
            $user->syncRoles([$request->role]);

            return redirect()->route('admin.users.show', $id)
                ->with('success', 'Utilisateur mis à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Empêcher la suppression de son propre compte
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            }
            
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
        }
    }

    private function getOptionalUserProfileAttributes(Request $request): array
    {
        $attributes = [];

        if (!Schema::hasTable('users')) {
            return $attributes;
        }

        if (Schema::hasColumn('users', 'raison_social')) {
            $attributes['raison_social'] = $request->company;
        } elseif (Schema::hasColumn('users', 'company')) {
            $attributes['company'] = $request->company;
        }

        if (Schema::hasColumn('users', 'position')) {
            $attributes['position'] = $request->position;
        }

        if (Schema::hasColumn('users', 'notes')) {
            $attributes['notes'] = $request->notes;
        }

        return $attributes;
    }
}
