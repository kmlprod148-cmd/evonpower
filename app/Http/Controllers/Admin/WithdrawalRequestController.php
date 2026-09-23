<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Contrôleur Admin pour les Demandes de Retrait
 */
class WithdrawalRequestController extends Controller
{
    public function __construct(
        protected WithdrawalRequestService $withdrawalService
    ) {}

    /**
     * Liste des demandes de retrait (vue)
     */
    public function index(): View
    {
        return view('admin.withdrawal-requests.index');
    }

    /**
     * Liste des demandes (API)
     */
    public function list(Request $request): JsonResponse
    {
        $query = WithdrawalRequest::with(['owner', 'wallet'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('owner', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $withdrawals = $query->paginate($request->per_page ?? 20);

        return response()->json($withdrawals);
    }

    /**
     * Détails d'une demande
     */
    public function show(WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $withdrawalRequest->load(['owner', 'wallet']);

        return response()->json([
            'withdrawal' => $withdrawalRequest,
            'status_history' => $withdrawalRequest->statusHistory,
        ]);
    }

    /**
     * Approuver une demande
     */
    public function approve(Request $request, WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->withdrawalService->approve(
                $withdrawalRequest,
                auth()->user(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande approuvée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rejeter une demande
     */
    public function reject(Request $request, WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->withdrawalService->reject(
                $withdrawalRequest,
                auth()->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande rejetée',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marquer comme en cours de traitement
     */
    public function markAsProcessing(WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        try {
            $withdrawalRequest->markAsProcessing(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marquer comme complété
     */
    public function markAsCompleted(Request $request, WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $request->validate([
            'external_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $withdrawalRequest->markAsCompleted(
                auth()->user(),
                $request->external_id,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Retrait marqué comme terminé',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(Request $request, WithdrawalRequest $withdrawalRequest): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $withdrawalRequest->markAsFailed(
                auth()->user(),
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Retrait marqué comme échoué',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Statistiques des retraits
     */
    public function statistics(): JsonResponse
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'pending' => WithdrawalRequest::where('status', 'pending')->count(),
            'approved' => WithdrawalRequest::where('status', 'approved')->count(),
            'processing' => WithdrawalRequest::where('status', 'processing')->count(),
            'completed' => WithdrawalRequest::where('status', 'completed')->count(),
            'failed' => WithdrawalRequest::where('status', 'failed')->count(),
            'cancelled' => WithdrawalRequest::where('status', 'cancelled')->count(),
            'today_count' => WithdrawalRequest::whereDate('created_at', $today)->count(),
            'today_amount' => WithdrawalRequest::whereDate('created_at', $today)->sum('amount'),
            'month_count' => WithdrawalRequest::where('created_at', '>=', $thisMonth)->count(),
            'month_amount' => WithdrawalRequest::where('created_at', '>=', $thisMonth)->sum('amount'),
            'completed_month_amount' => WithdrawalRequest::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('net_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Traitement en masse
     */
    public function bulkProcess(Request $request): JsonResponse
    {
        $request->validate([
            'withdrawal_ids' => 'required|array',
            'withdrawal_ids.*' => 'integer|exists:withdrawal_requests,id',
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject|string|max:500',
        ]);

        $results = [];
        $withdrawals = WithdrawalRequest::whereIn('id', $request->withdrawal_ids)->get();

        foreach ($withdrawals as $withdrawal) {
            try {
                if ($request->action === 'approve') {
                    $this->withdrawalService->approve($withdrawal, auth()->user());
                } else {
                    $this->withdrawalService->reject($withdrawal, auth()->user(), $request->reason);
                }
                $results[$withdrawal->id] = ['success' => true];
            } catch (\Exception $e) {
                $results[$withdrawal->id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'results' => $results,
            'success_count' => collect($results)->where('success', true)->count(),
            'failed_count' => collect($results)->where('success', false)->count(),
        ]);
    }
}
