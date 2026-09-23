<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OperatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard spécifique pour les opérateurs système
     */
    public function dashboard()
    {
        // Vérifier que l'utilisateur est bien un opérateur système
        if (!auth()->user()->hasRole('operator') || auth()->user()->user_type !== 'system') {
            abort(403, 'Accès non autorisé.');
        }

        $user = auth()->user();
        
        // Statistiques pour l'opérateur système
        $stats = [
            'total_charging_points' => $user->chargingPoints()->count(),
            'active_charging_points' => $user->chargingPoints()->where('is_active', true)->count(),
            'total_transactions' => $user->transactions()->count(),
            'transactions_this_month' => $user->transactions()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'integrator_name' => $user->integrator ? $user->integrator->name : 'Non assigné',
            'created_at' => $user->created_at->format('d/m/Y'),
            'last_login' => $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Jamais'
        ];

        // Activité récente
        $recentActivity = $user->transactions()
            ->with(['chargingPoint', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('operator.dashboard', compact('stats', 'recentActivity'));
    }

    /**
     * Profil de l'opérateur système
     */
    public function profile()
    {
        // Vérifier que l'utilisateur est bien un opérateur système
        if (!auth()->user()->hasRole('operator') || auth()->user()->user_type !== 'system') {
            abort(403, 'Accès non autorisé.');
        }

        $user = auth()->user();
        $user->load(['integrator', 'partner', 'roles']);

        return view('operator.profile', compact('user'));
    }

    /**
     * Formulaire d'édition du profil
     */
    public function editProfile()
    {
        // Vérifier que l'utilisateur est bien un opérateur système
        if (!auth()->user()->hasRole('operator') || auth()->user()->user_type !== 'system') {
            abort(403, 'Accès non autorisé.');
        }

        $user = auth()->user();
        $user->load(['integrator', 'partner']);

        return view('operator.profile-edit', compact('user'));
    }

    /**
     * Mise à jour du profil
     */
    public function updateProfile(Request $request)
    {
        // Vérifier que l'utilisateur est bien un opérateur système
        if (!auth()->user()->hasRole('operator') || auth()->user()->user_type !== 'system') {
            abort(403, 'Accès non autorisé.');
        }

        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Vérifier le mot de passe actuel si un nouveau mot de passe est fourni
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
            }
        }

        // Mettre à jour les informations
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
        ]);

        return redirect()->route('operator.profile')
            ->with('success', 'Profil mis à jour avec succès.');
    }
}
