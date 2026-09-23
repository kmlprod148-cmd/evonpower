<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\HierarchicalBalanceService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur API pour la gestion des balances hiérarchiques
 * 
 * Endpoints disponibles :
 * - GET /api/balances/user/{id} - Balance d'un utilisateur
 * - GET /api/balances/current - Balance de l'utilisateur connecté
 * - GET /api/balances/statistics - Statistiques globales
 * - POST /api/balances/recalculate - Recalcul des balances
 * - GET /api/balances/hierarchy - Données hiérarchiques
 */
class HierarchicalBalanceController extends Controller
{
    protected HierarchicalBalanceService $balanceService;

    public function __construct(HierarchicalBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
        $this->middleware('auth:api');
    }

    /**
     * Récupère la balance d'un utilisateur spécifique
     * 
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserBalance(Request $request, int $userId): JsonResponse
    {
        try {
            // Vérifier les permissions
            $user = $request->user();
            if (!$user->hasRole('admin') && $user->id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $targetUser = User::findOrFail($userId);
            $forceRefresh = $request->boolean('force_refresh', false);
            
            $balance = $this->balanceService->calculateUserBalance($targetUser, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $balance,
                'message' => 'Balance récupérée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la balance utilisateur', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère la balance de l'utilisateur connecté
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentUserBalance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $forceRefresh = $request->boolean('force_refresh', false);
            
            $balance = $this->balanceService->calculateUserBalance($user, $forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $balance,
                'message' => 'Balance récupérée avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la balance utilisateur connecté', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques globales des balances
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getGlobalStatistics(Request $request): JsonResponse
    {
        try {
            // Vérifier les permissions (seuls les admins peuvent voir les stats globales)
            $user = $request->user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé - Seuls les administrateurs peuvent voir les statistiques globales'
                ], 403);
            }

            $forceRefresh = $request->boolean('force_refresh', false);
            $statistics = $this->balanceService->calculateGlobalStatistics($forceRefresh);

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Statistiques globales récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques globales', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalcule les balances
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function recalculateBalances(Request $request): JsonResponse
    {
        try {
            // Vérifier les permissions (seuls les admins peuvent forcer le recalcul)
            $user = $request->user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé - Seuls les administrateurs peuvent forcer le recalcul'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'nullable|integer|exists:users,id',
                'role' => 'nullable|string|in:admin,integrator,operator',
                'force' => 'boolean',
                'clear_cache' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');
            $role = $request->input('role');
            $force = $request->boolean('force', false);
            $clearCache = $request->boolean('clear_cache', false);

            // Nettoyer le cache si demandé
            if ($clearCache) {
                $this->balanceService->clearBalanceCache($userId);
            }

            $results = [];

            if ($userId) {
                // Recalcul pour un utilisateur spécifique
                $targetUser = User::findOrFail($userId);
                $balance = $this->balanceService->calculateUserBalance($targetUser, $force);
                $results = [
                    'success' => true,
                    'processed_users' => 1,
                    'user_balances' => [$balance],
                    'errors' => []
                ];
            } elseif ($role) {
                // Recalcul pour un rôle spécifique
                $users = User::role($role)->get();
                $results = [
                    'success' => true,
                    'processed_users' => 0,
                    'user_balances' => [],
                    'errors' => []
                ];

                foreach ($users as $user) {
                    try {
                        $balance = $this->balanceService->calculateUserBalance($user, $force);
                        $results['user_balances'][] = $balance;
                        $results['processed_users']++;
                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'error' => $e->getMessage()
                        ];
                        $results['success'] = false;
                    }
                }
            } else {
                // Recalcul pour tous les utilisateurs
                $results = $this->balanceService->recalculateAllBalances($force);
            }

            return response()->json([
                'success' => $results['success'],
                'data' => $results,
                'message' => $results['success'] 
                    ? 'Recalcul des balances terminé avec succès'
                    : 'Recalcul terminé avec des erreurs'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du recalcul des balances', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du recalcul des balances',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les données hiérarchiques
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getHierarchyData(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userRole = $user->getRoleNames()->first();

            $hierarchyData = [];

            switch ($userRole) {
                case 'admin':
                    $hierarchyData = $this->getAdminHierarchyData();
                    break;
                case 'integrator':
                    $hierarchyData = $this->getIntegratorHierarchyData($user);
                    break;
                case 'operator':
                    $hierarchyData = $this->getOperatorHierarchyData($user);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Rôle utilisateur non reconnu'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $hierarchyData,
                'message' => 'Données hiérarchiques récupérées avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des données hiérarchiques', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données hiérarchiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les données hiérarchiques pour un admin
     * 
     * @return array
     */
    private function getAdminHierarchyData(): array
    {
        // Récupérer tous les intégrateurs avec leurs performances
        $integrators = \DB::table('users as u')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('r.name', 'integrator')
            ->leftJoin('transaction_hierarchies as th', function($join) {
                $join->on('th.payee_id', '=', 'u.id')
                     ->where('th.status', '=', 'completed');
            })
            ->selectRaw('
                u.id,
                u.name,
                u.email,
                COUNT(th.id) as transaction_count,
                COALESCE(SUM(th.net_amount), 0) as total_commissions,
                MAX(th.created_at) as last_activity
            ')
            ->groupBy('u.id', 'u.name', 'u.email')
            ->get();

        return [
            'role' => 'admin',
            'integrators' => $integrators->map(function($integrator) {
                return [
                    'id' => $integrator->id,
                    'name' => $integrator->name,
                    'email' => $integrator->email,
                    'transaction_count' => (int) $integrator->transaction_count,
                    'total_commissions' => (float) $integrator->total_commissions,
                    'last_activity' => $integrator->last_activity,
                ];
            })->toArray(),
            'total_integrators' => $integrators->count(),
            'total_commissions_from_integrators' => $integrators->sum('total_commissions')
        ];
    }

    /**
     * Récupère les données hiérarchiques pour un intégrateur
     * 
     * @param User $user
     * @return array
     */
    private function getIntegratorHierarchyData(User $user): array
    {
        // Récupérer tous les opérateurs sous cet intégrateur
        $operators = \DB::table('users as u')
            ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')
            ->where('r.name', 'operator')
            ->where('u.integrator_id', $user->id)
            ->leftJoin('transaction_hierarchies as th', function($join) {
                $join->on('th.payee_id', '=', 'u.id')
                     ->where('th.status', '=', 'completed');
            })
            ->selectRaw('
                u.id,
                u.name,
                u.email,
                COUNT(th.id) as transaction_count,
                COALESCE(SUM(th.net_amount), 0) as total_earnings,
                MAX(th.created_at) as last_activity
            ')
            ->groupBy('u.id', 'u.name', 'u.email')
            ->get();

        return [
            'role' => 'integrator',
            'operators' => $operators->map(function($operator) {
                return [
                    'id' => $operator->id,
                    'name' => $operator->name,
                    'email' => $operator->email,
                    'transaction_count' => (int) $operator->transaction_count,
                    'total_earnings' => (float) $operator->total_earnings,
                    'last_activity' => $operator->last_activity,
                ];
            })->toArray(),
            'total_operators' => $operators->count(),
            'total_earnings_from_operators' => $operators->sum('total_earnings')
        ];
    }

    /**
     * Récupère les données hiérarchiques pour un opérateur
     * 
     * @param User $user
     * @return array
     */
    private function getOperatorHierarchyData(User $user): array
    {
        // Récupérer l'intégrateur parent
        $integrator = \DB::table('users as u')
            ->where('u.id', $user->integrator_id)
            ->select('id', 'name', 'email')
            ->first();

        // Récupérer les points de charge
        $chargingPoints = \DB::table('charging_points as cp')
            ->where('cp.operator_id', $user->id)
            ->selectRaw('
                cp.id,
                cp.name,
                cp.status,
                COUNT(t.id) as transaction_count,
                COALESCE(SUM(t.amount), 0) as total_revenue
            ')
            ->leftJoin('transactions as t', function($join) {
                $join->on('t.charging_point_id', '=', 'cp.id')
                     ->where('t.status', '=', 'completed');
            })
            ->groupBy('cp.id', 'cp.name', 'cp.status')
            ->get();

        return [
            'role' => 'operator',
            'integrator' => $integrator ? [
                'id' => $integrator->id,
                'name' => $integrator->name,
                'email' => $integrator->email,
            ] : null,
            'charging_points' => $chargingPoints->map(function($point) {
                return [
                    'id' => $point->id,
                    'name' => $point->name,
                    'status' => $point->status,
                    'transaction_count' => (int) $point->transaction_count,
                    'total_revenue' => (float) $point->total_revenue,
                ];
            })->toArray()
        ];
    }

    /**
     * Nettoie le cache des balances
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            // Vérifier les permissions (seuls les admins peuvent nettoyer le cache)
            $user = $request->user();
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé - Seuls les administrateurs peuvent nettoyer le cache'
                ], 403);
            }

            $userId = $request->input('user_id');
            $success = $this->balanceService->clearBalanceCache($userId);

            return response()->json([
                'success' => $success,
                'message' => $success 
                    ? 'Cache nettoyé avec succès'
                    : 'Erreur lors du nettoyage du cache'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage du cache', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage du cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
