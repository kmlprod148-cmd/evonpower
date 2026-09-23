<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HierarchicalBalanceSynchronizationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HierarchicalBalanceSyncController extends Controller
{
    protected HierarchicalBalanceSynchronizationService $syncService;

    public function __construct(HierarchicalBalanceSynchronizationService $syncService)
    {
        $this->syncService = $syncService;
        $this->middleware('auth');
    }

    /**
     * Afficher la page de synchronisation des balances
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        // Statistiques rapides
        $stats = [
            'admins' => User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
            })->count(),
            'integrators' => User::whereHas('roles', function($q) {
                $q->where('name', 'integrator');
            })->count(),
            'operators' => User::whereHas('roles', function($q) {
                $q->where('name', 'operator');
            })->count(),
            'partners' => User::whereHas('roles', function($q) {
                $q->where('name', 'partner');
            })->count(),
        ];

        return view('admin.balances.sync-hierarchical', compact('stats'));
    }

    /**
     * Synchroniser toutes les balances hiérarchiquement
     */
    public function syncAll(Request $request)
    {
        $this->authorize('viewAny', User::class);

        try {
            $results = $this->syncService->synchronizeAllHierarchicalBalances();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Balances synchronized successfully',
                    'results' => $results
                ]);
            }

            return redirect()->route('admin.balances.sync-hierarchical')
                ->with('success', 'Toutes les balances ont été synchronisées avec succès.')
                ->with('results', $results);

        } catch (\Exception $e) {
            Log::error('HierarchicalBalanceSyncController: Error during sync all', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.balances.sync-hierarchical')
                ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }

    /**
     * Synchroniser un utilisateur spécifique avec sa hiérarchie
     */
    public function syncUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $this->authorize('view', $user);

        try {
            $results = $this->syncService->synchronizeUserWithHierarchy($user);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Balance synchronized for {$user->name}",
                    'results' => $results
                ]);
            }

            return redirect()->back()
                ->with('success', "Balance synchronisée pour {$user->name}.");

        } catch (\Exception $e) {
            Log::error('HierarchicalBalanceSyncController: Error during sync user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }

    /**
     * Synchroniser tous les utilisateurs d'un rôle spécifique
     */
    public function syncByRole(Request $request, string $role)
    {
        $this->authorize('viewAny', User::class);

        if (!in_array($role, ['admin', 'integrator', 'operator', 'partner'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rôle invalide'
            ], 400);
        }

        try {
            $results = [];

            if (in_array($role, ['admin', 'super_admin'])) {
                $results = $this->syncService->synchronizeAdmins();
            } elseif ($role === 'integrator') {
                $results = $this->syncService->synchronizeIntegrators();
            } elseif (in_array($role, ['operator', 'partner'])) {
                $results = $this->syncService->synchronizeOperatorsAndPartners();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Balances synchronized for role: {$role}",
                    'results' => $results
                ]);
            }

            return redirect()->route('admin.balances.sync-hierarchical')
                ->with('success', "Balances synchronisées pour le rôle: {$role}.");

        } catch (\Exception $e) {
            Log::error('HierarchicalBalanceSyncController: Error during sync by role', [
                'role' => $role,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.balances.sync-hierarchical')
                ->with('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }
}

