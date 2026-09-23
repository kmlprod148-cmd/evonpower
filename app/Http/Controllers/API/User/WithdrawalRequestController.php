<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur API pour les Demandes de Retrait (Utilisateur)
 */
class WithdrawalRequestController extends Controller
{
    public function __construct(
        protected WithdrawalRequestService $withdrawalService
    ) {}

    /**
     * Soumettre une nouvelle demande de retrait
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
            'bank_name' => 'required|string|max:255',
            'bank_account' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'method' => 'nullable|in:bank_transfer,card',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        try {
            $withdrawal = $this->withdrawalService->createWithdrawalRequest(
                $user,
                $request->amount,
                [
                    'method' => $request->method ?? 'bank_transfer',
                    'bank_name' => $request->bank_name,
                    'bank_account' => $request->bank_account,
                    'bank_code' => $request->bank_code,
                    'iban' => $request->iban,
                ],
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait soumise avec succès',
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'fee' => $withdrawal->fee,
                    'net_amount' => $withdrawal->net_amount,
                    'currency' => $withdrawal->currency,
                    'status' => $withdrawal->status,
                    'status_label' => $withdrawal->statusEnum->getLabel(),
                    'created_at' => $withdrawal->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Historique des demandes de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $withdrawals = WithdrawalRequest::where('owner_id', $user->id)
            ->where('owner_type', get_class($user))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($withdrawals);
    }

    /**
     * Détails d'une demande
     */
    public function show(WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $user = Auth::user();

        // Vérifier que la demande appartient à l'utilisateur
        if ($withdrawalRequest->owner_id !== $user->id || 
            $withdrawalRequest->owner_type !== get_class($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        return response()->json([
            'withdrawal' => [
                'id' => $withdrawalRequest->id,
                'amount' => $withdrawalRequest->amount,
                'fee' => $withdrawalRequest->fee,
                'net_amount' => $withdrawalRequest->net_amount,
                'currency' => $withdrawalRequest->currency,
                'status' => $withdrawalRequest->status,
                'status_label' => $withdrawalRequest->statusEnum->getLabel(),
                'bank_name' => $withdrawalRequest->bank_name,
                'masked_account' => $withdrawalRequest->masked_bank_account,
                'created_at' => $withdrawalRequest->created_at,
                'processed_at' => $withdrawalRequest->processed_at,
                'notes' => $withdrawalRequest->notes,
                'rejection_reason' => $withdrawalRequest->rejection_reason,
            ],
        ]);
    }

    /**
     * Annuler une demande en attente
     */
    public function cancel(WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $user = Auth::user();

        // Vérifier que la demande appartient à l'utilisateur
        if ($withdrawalRequest->owner_id !== $user->id || 
            $withdrawalRequest->owner_type !== get_class($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        if (!$withdrawalRequest->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette demande ne peut pas être annulée',
            ], 422);
        }

        try {
            $withdrawalRequest->cancel($user);

            return response()->json([
                'success' => true,
                'message' => 'Demande annulée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtenir les limites de retrait
     */
    public function limits(): JsonResponse
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();

        return response()->json([
            'min_amount' => WithdrawalRequestService::MIN_WITHDRAWAL_AMOUNT,
            'max_amount' => WithdrawalRequestService::MAX_WITHDRAWAL_AMOUNT,
            'fee_percentage' => WithdrawalRequestService::WITHDRAWAL_FEE_PERCENTAGE,
            'daily_limit' => 5000.00, // Par défaut pour les utilisateurs
            'available_balance' => $wallet?->balance ?? 0,
        ]);
    }

    /**
     * Calculer les frais pour un montant
     */
    public function calculateFee(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
        ]);

        $fee = $this->withdrawalService->calculateFee($request->amount);
        $netAmount = $request->amount - $fee;

        return response()->json([
            'amount' => $request->amount,
            'fee' => $fee,
            'net_amount' => $netAmount,
        ]);
    }
}
