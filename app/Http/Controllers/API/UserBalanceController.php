<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\ClientBalanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserBalanceController extends Controller
{
    /**
     * Get current user's balance information
     * Used for real-time balance updates in the UI
     */
    public function getBalance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Get hierarchical transaction summary if available
            $hierarchicalSummary = [];
            if (method_exists($user, 'getHierarchicalTransactionSummary')) {
                $hierarchicalSummary = $user->getHierarchicalTransactionSummary();
            }

            $balanceService = app(ClientBalanceService::class);
            $balance = $balanceService->getBalance($user);
            $formatted = $balanceService->getFormatted($user);
            $currency = $user->getOrCreateWallet()->currency ?? 'EUR';

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'formatted_balance' => $formatted,
                'currency' => $currency,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first() ?? 'user',
                    'role_badge_color' => $user->getRoleBadgeColor(),
                ],
                'hierarchical_summary' => $hierarchicalSummary,
                'last_updated' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du solde',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get detailed balance information with transaction history
     */
    public function getDetailedBalance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            // Get hierarchical transaction details
            $hierarchicalDetails = [];
            if (method_exists($user, 'getHierarchicalTransactionDetails')) {
                $hierarchicalDetails = $user->getHierarchicalTransactionDetails();
            }

            // Get charging point reservations
            $reservations = [];
            if (method_exists($user, 'getChargingPointReservations')) {
                $reservations = $user->getChargingPointReservations();
            }

            // Get hierarchy information
            $hierarchy = [];
            if (method_exists($user, 'getCreatorHierarchy')) {
                $hierarchy = $user->getCreatorHierarchy();
            }

            $balanceService = app(ClientBalanceService::class);
            $balance = $balanceService->getBalance($user);
            $formatted = $balanceService->getFormatted($user);
            $currency = $user->getOrCreateWallet()->currency ?? 'EUR';

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first() ?? 'user',
                    'role_badge_color' => $user->getRoleBadgeColor(),
                ],
                'balance' => [
                    'current' => $balance,
                    'formatted_balance' => $formatted,
                    'currency' => $currency,
                ],
                'hierarchical_details' => $hierarchicalDetails,
                'reservations' => $reservations,
                'hierarchy' => [
                    'root_admin' => method_exists($user, 'getRootAdmin') ? $user->getRootAdmin() : null,
                    'direct_integrator' => method_exists($user, 'getDirectIntegrator') ? $user->getDirectIntegrator() : null,
                    'hierarchy_chain' => $hierarchy,
                    'stats' => [
                        'created_integrators' => $user->hasRole('admin') ? $user->createdUsers()->whereHas('roles', function($q) { $q->where('name', 'integrator'); })->count() : 0,
                        'created_operators' => $user->hasRole(['admin', 'integrator']) ? $user->createdUsers()->whereHas('roles', function($q) { $q->where('name', 'operator'); })->count() : 0,
                        'total_created_users' => $user->createdUsers()->count(),
                    ]
                ],
                'last_updated' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails du solde',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get balance history for the current user
     */
    public function getBalanceHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }

            $limit = max(1, (int) $request->get('limit', 20));
            $page = max(1, (int) $request->get('page', 1));
            $offset = ($page - 1) * $limit;

            $wallet = $user->getOrCreateWallet();

            $baseQuery = WalletTransaction::where('wallet_id', $wallet->id);
            $totalCount = $baseQuery->count();

            $transactions = $baseQuery->clone()
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $formattedTransactions = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'formatted_amount' => $transaction->formatted_amount ?? null,
                    'balance_before' => (float) ($transaction->balance_before ?? 0),
                    'balance_after' => (float) ($transaction->balance_after ?? 0),
                    'reservation_id' => $transaction->getReservationId(),
                    'created_at' => $transaction->created_at?->toISOString(),
                    'description' => $transaction->getDescriptiveLabel(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedTransactions,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalCount,
                    'last_page' => (int) ceil($totalCount / $limit),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
