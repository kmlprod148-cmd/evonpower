<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PostpaidPaymentService;
use App\Models\User;
use App\Enums\PostpaidStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Contrôleur Admin pour la gestion du mode Postpayé
 */
class PostpaidManagementController extends Controller
{
    public function __construct(
        protected PostpaidPaymentService $postpaidService
    ) {}

    /**
     * Liste des utilisateurs postpayés
     * 
     * GET /api/admin/postpaid/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::whereNotNull('postpaid_status')
            ->where('postpaid_status', '!=', PostpaidStatus::NOT_AUTHORIZED->value);

        // Filtrer par statut
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('postpaid_status', $request->status);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Détails d'un utilisateur postpayé
     * 
     * GET /api/admin/postpaid/users/{userId}
     */
    public function show(int $userId): JsonResponse
    {
        $user = User::with(['transactions', 'reservations'])->findOrFail($userId);

        $creditInfo = [
            'status' => $user->postpaid_status,
            'status_label' => PostpaidStatus::from($user->postpaid_status)->getLabel(),
            'credit_limit' => $user->postpaid_credit_limit ?? 0,
            'available_credit' => $this->postpaidService->getAvailableCredit($user),
            'current_usage' => $this->postpaidService->getCurrentUsage($user),
            'approved_at' => $user->postpaid_approved_at,
            'billing_day' => $user->postpaid_billing_day,
        ];

        $usageHistory = $this->postpaidService->getUsageHistory($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'created_at' => $user->created_at,
                ],
                'credit' => $creditInfo,
                'usage' => $usageHistory,
            ],
        ]);
    }

    /**
     * Autoriser un utilisateur pour le postpayé
     * 
     * POST /api/admin/postpaid/users/{userId}/authorize
     */
    public function authorize(Request $request, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_limit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $creditLimit = $request->input('credit_limit');

            $this->postpaidService->authorizeUser(
                $user,
                $creditLimit,
                auth()->user()
            );

            Log::info('PostpaidManagementController: Utilisateur autorisé', [
                'user_id' => $userId,
                'credit_limit' => $creditLimit,
                'authorized_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur autorisé pour le mode postpayé',
                'data' => [
                    'user_id' => $user->id,
                    'postpaid_status' => $user->fresh()->postpaid_status,
                    'credit_limit' => $creditLimit,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('PostpaidManagementController: Erreur autorisation', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTHORIZE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Révoquer l'autorisation postpayé
     * 
     * POST /api/admin/postpaid/users/{userId}/revoke
     */
    public function revoke(Request $request, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $reason = $request->input('reason');

            $this->postpaidService->revokeUser($user, $reason, auth()->user());

            Log::info('PostpaidManagementController: Autorisation révoquée', [
                'user_id' => $userId,
                'reason' => $reason,
                'revoked_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Autorisation postpayé révoquée',
                'data' => [
                    'user_id' => $user->id,
                    'postpaid_status' => $user->fresh()->postpaid_status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REVOKE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Suspendre temporairement l'autorisation
     * 
     * POST /api/admin/postpaid/users/{userId}/suspend
     */
    public function suspend(Request $request, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $reason = $request->input('reason');

            $this->postpaidService->suspendUser($user, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur postpayé suspendu',
                'data' => [
                    'user_id' => $user->id,
                    'postpaid_status' => $user->fresh()->postpaid_status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SUSPEND_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Réactiver un utilisateur suspendu
     * 
     * POST /api/admin/postpaid/users/{userId}/reactivate
     */
    public function reactivate(int $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $this->postpaidService->reactivateUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur postpayé réactivé',
                'data' => [
                    'user_id' => $user->id,
                    'postpaid_status' => $user->fresh()->postpaid_status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REACTIVATE_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Mettre à jour la limite de crédit
     * 
     * POST /api/admin/postpaid/users/{userId}/update-limit
     */
    public function updateCreditLimit(Request $request, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'credit_limit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($userId);
            $creditLimit = $request->input('credit_limit');

            $user->update([
                'postpaid_credit_limit' => $creditLimit,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Limite de crédit mise à jour',
                'data' => [
                    'user_id' => $user->id,
                    'credit_limit' => $creditLimit,
                    'available_credit' => $this->postpaidService->getAvailableCredit($user),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_LIMIT_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Générer les factures mensuelles
     * 
     * POST /api/admin/postpaid/generate-invoices
     */
    public function generateInvoices(): JsonResponse
    {
        try {
            $count = $this->postpaidService->generateMonthlyInvoices();

            return response()->json([
                'success' => true,
                'message' => "{$count} factures générées",
                'data' => [
                    'invoices_count' => $count,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVOICE_GENERATION_FAILED',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
