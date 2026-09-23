<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\User;
use App\Services\HierarchicalTransactionService;
use App\Services\ReservationTransactionService;
use App\Services\UnifiedHierarchicalTransactionService;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionHierarchyController extends Controller
{
    protected HierarchicalTransactionService $transactionService;
    protected ReservationTransactionService $reservationTransactionService;
    protected UnifiedHierarchicalTransactionService $unifiedService;

    public function __construct(
        HierarchicalTransactionService $transactionService,
        ReservationTransactionService $reservationTransactionService,
        UnifiedHierarchicalTransactionService $unifiedService
    ) {
        $this->transactionService = $transactionService;
        $this->reservationTransactionService = $reservationTransactionService;
        $this->unifiedService = $unifiedService;
    }

    /**
     * Traiter une transaction avec la logique hiérarchique UNIFIÉE
     * 
     * LOGIQUE IMPLÉMENTÉE :
     * 1. Borne → Groupe → Opérateur → Intégrateur → Admin
     * 2. Admin → Intégrateur : L'Admin prend sa part selon BusinessProfile, déduite de l'intégrateur
     * 3. Intégrateur → Opérateur : L'intégrateur prend sa part, le reste va à l'opérateur
     * 4. Sauvegarde complète dans les tables transactions avec vérification de cohérence
     */
    public function processUnifiedHierarchicalTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'total_amount' => 'required|numeric|min:0.01',
            'metadata' => 'nullable|array'
        ]);

        try {
            $result = $this->unifiedService->processHierarchicalTransaction(
                $request->charging_point_id,
                $request->total_amount,
                $request->metadata ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction hiérarchique traitée avec succès',
                    'data' => [
                        'transaction_id' => $result['main_transaction']->id,
                        'reference_id' => $result['main_transaction']->reference_id,
                        'total_amount' => $result['main_transaction']->amount,
                        'admin_share' => $result['calculation']['admin_share'],
                        'integrator_share' => $result['calculation']['integrator_share'],
                        'operator_share' => $result['calculation']['operator_share'],
                        'hierarchy' => [
                            'admin_id' => $result['hierarchy']['admin']->id,
                            'integrator_id' => $result['hierarchy']['integrator']->id,
                            'operator_id' => $result['hierarchy']['operator']->id,
                            'charging_point_id' => $result['hierarchy']['charging_point']->id
                        ],
                        'business_profiles' => $result['calculation']['business_profiles']
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du traitement de la transaction hiérarchique',
                    'error' => $result['error'],
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Erreur dans processUnifiedHierarchicalTransaction', [
                'charging_point_id' => $request->charging_point_id,
                'total_amount' => $request->total_amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors du traitement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traiter une transaction avec la logique hiérarchique (ancienne méthode)
     */
    public function processTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);
        
        $result = $this->transactionService->processTransaction($transaction);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction traitée avec succès',
                'data' => $result,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la transaction',
                'error' => $result['error'],
            ], 500);
        }
    }

    /**
     * Obtenir le résumé d'une transaction hiérarchique unifiée
     */
    public function getUnifiedTransactionSummary(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $summary = $this->unifiedService->getTransactionSummary($request->transaction_id);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Simuler une transaction hiérarchique (sans persistance ni mise à jour des wallets)
     */
    public function simulateUnifiedTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'total_amount' => 'nullable|numeric|min:0.01'
        ]);

        try {
            $chargingPointId = (int) $request->charging_point_id;
            $totalAmount = $request->has('total_amount') ? (float) $request->total_amount : 200.0;

            // Récupérer la hiérarchie
            $hierarchy = $this->unifiedService->getCompleteHierarchy($chargingPointId);
            if (!$hierarchy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hiérarchie introuvable ou incomplète pour cette borne.'
                ], 422);
            }

            // Récupérer les business profiles
            $businessProfiles = $this->unifiedService->getBusinessProfilesForHierarchy($hierarchy);

            // Calculer les parts
            $calculation = $this->unifiedService->calculateSharesWithBusinessProfiles($totalAmount, $businessProfiles, $hierarchy);

            return response()->json([
                'success' => true,
                'message' => 'Simulation réalisée avec succès',
                'data' => [
                    'total_amount' => $totalAmount,
                    'calculation' => $calculation,
                    'hierarchy' => [
                        'admin_id' => $hierarchy['admin']->id,
                        'integrator_id' => $hierarchy['integrator']->id,
                        'operator_id' => $hierarchy['operator']->id,
                        'charging_point_id' => $hierarchy['charging_point']->id
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans simulateUnifiedTransaction', [
                'charging_point_id' => $request->charging_point_id,
                'total_amount' => $request->total_amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la simulation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir le résumé d'une transaction (ancienne méthode)
     */
    public function getTransactionSummary(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        $transaction = Transaction::findOrFail($request->transaction_id);
        $summary = $this->transactionService->getTransactionSummary($transaction);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Obtenir les détails des parts Admin
     */
    public function getAdminShares(Request $request): JsonResponse
    {
        $query = TransactionDetail::with(['transaction', 'adminCreator', 'integratorCreator', 'operator'])
            ->whereNotNull('admin_creator_id');

        // Filtres optionnels
        if ($request->has('admin_id')) {
            $query->where('admin_creator_id', $request->admin_id);
        }

        if ($request->has('date_from')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('created_at', '>=', $request->date_from);
            });
        }

        if ($request->has('date_to')) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('created_at', '<=', $request->date_to);
            });
        }

        $adminShares = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $adminShares,
            'summary' => [
                'total_admin_shares' => $adminShares->sum('admin_share_amount'),
                'total_transactions' => $adminShares->count(),
                'paid_transactions' => $adminShares->where('admin_paid', true)->count(),
            ],
        ]);
    }

    /**
     * Obtenir les transactions hiérarchiques
     */
    public function getHierarchicalTransactions(Request $request): JsonResponse
    {
        $query = TransactionHierarchy::with(['originalTransaction', 'payer', 'payee']);

        // Filtres
        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('payer_id', $request->user_id)
                  ->orWhere('payee_id', $request->user_id);
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Obtenir les soldes des utilisateurs
     */
    public function getUserBalances(Request $request): JsonResponse
    {
        $query = User::with(['roles']);

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->select(['id', 'name', 'email', 'balance', 'currency', 'created_at'])
            ->orderBy('balance', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
            'summary' => [
                'total_balance' => $users->sum('balance'),
                'admin_balance' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'admin');
                })->sum('balance'),
                'integrator_balance' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'integrator');
                })->sum('balance'),
                'operator_balance' => User::whereHas('roles', function ($q) {
                    $q->where('name', 'partner');
                })->sum('balance'),
            ],
        ]);
    }

    /**
     * Marquer une part comme payée
     */
    public function markShareAsPaid(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_detail_id' => 'required|exists:transaction_details,id',
            'share_type' => 'required|in:admin,integrator,operator',
        ]);

        $transactionDetail = TransactionDetail::findOrFail($request->transaction_detail_id);

        try {
            DB::beginTransaction();

            switch ($request->share_type) {
                case 'admin':
                    $transactionDetail->update([
                        'admin_paid' => true,
                        'admin_paid_at' => now(),
                    ]);
                    break;
                case 'integrator':
                    $transactionDetail->update([
                        'integrator_paid' => true,
                        'integrator_paid_at' => now(),
                    ]);
                    break;
                case 'operator':
                    $transactionDetail->update([
                        'operator_paid' => true,
                        'operator_paid_at' => now(),
                    ]);
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Part {$request->share_type} marquée comme payée",
                'data' => $transactionDetail->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir l'exemple de calcul selon vos spécifications
     */
    public function getExampleCalculation(): JsonResponse
    {
        $example = $this->transactionService->processExampleTransaction();

        return response()->json([
            'success' => true,
            'data' => $example,
        ]);
    }

    /**
     * Obtenir les business profiles utilisés dans les transactions
     */
    public function getBusinessProfilesUsed(): JsonResponse
    {
        $businessProfiles = TransactionDetail::with(['transaction'])
            ->get()
            ->groupBy(function ($detail) {
                return $detail->calculation_details['business_profile_id'] ?? 'unknown';
            })
            ->map(function ($group, $profileId) {
                if ($profileId === 'unknown') {
                    return [
                        'id' => 'unknown',
                        'name' => 'Non défini',
                        'transaction_count' => $group->count(),
                        'total_fees' => $group->sum('transaction_fee_total'),
                        'total_admin_shares' => $group->sum('admin_share_amount'),
                        'total_integrator_shares' => $group->sum('integrator_share_amount'),
                        'total_operator_shares' => $group->sum('operator_share_amount'),
                    ];
                }

                $businessProfile = \App\Models\BusinessProfile::find($profileId);
                return [
                    'id' => $profileId,
                    'name' => $businessProfile ? $businessProfile->name : 'Inconnu',
                    'description' => $businessProfile ? $businessProfile->description : null,
                    'transaction_count' => $group->count(),
                    'total_fees' => $group->sum('transaction_fee_total'),
                    'total_admin_shares' => $group->sum('admin_share_amount'),
                    'total_integrator_shares' => $group->sum('integrator_share_amount'),
                    'total_operator_shares' => $group->sum('operator_share_amount'),
                    'business_profile' => $businessProfile,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $businessProfiles,
        ]);
    }

    /**
     * Obtenir les statistiques des transactions
     */
    public function getTransactionStats(): JsonResponse
    {
        $stats = [
            'total_transactions' => Transaction::count(),
            'total_amount' => Transaction::sum('amount'),
            'total_fees' => TransactionDetail::sum('transaction_fee_total'),
            'total_admin_shares' => TransactionDetail::sum('admin_share_amount'),
            'total_integrator_shares' => TransactionDetail::sum('integrator_share_amount'),
            'total_operator_shares' => TransactionDetail::sum('operator_share_amount'),
            'paid_admin_shares' => TransactionDetail::where('admin_paid', true)->sum('admin_share_amount'),
            'paid_integrator_shares' => TransactionDetail::where('integrator_paid', true)->sum('integrator_share_amount'),
            'paid_operator_shares' => TransactionDetail::where('operator_paid', true)->sum('operator_share_amount'),
            'hierarchical_transactions' => [
                'admin_integrator' => TransactionHierarchy::where('transaction_type', 'admin_integrator')->count(),
                'integrator_operator' => TransactionHierarchy::where('transaction_type', 'integrator_operator')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Traiter une réservation avec répartition des parts
     */
    public function processReservationTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);
        
        $result = $this->reservationTransactionService->processReservationTransaction($reservation);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Transaction de réservation traitée avec succès',
                'data' => $result,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la transaction de réservation',
                'error' => $result['error'],
            ], 500);
        }
    }

    /**
     * Obtenir le résumé d'une transaction de réservation
     */
    public function getReservationTransactionSummary(Request $request): JsonResponse
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);
        $summary = $this->reservationTransactionService->getReservationTransactionSummary($reservation);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Obtenir toutes les transactions de réservation avec détails
     */
    public function getReservationTransactions(Request $request): JsonResponse
    {
        $query = Transaction::with(['transactionDetail', 'hierarchicalTransactions'])
            ->whereNotNull('reservation_id');

        // Filtres optionnels
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'summary' => [
                'total_transactions' => $transactions->total(),
                'total_amount' => $transactions->sum('amount'),
                'total_admin_shares' => $transactions->sum(function($transaction) {
                    return $transaction->transactionDetail?->admin_share_amount ?? 0;
                }),
                'total_integrator_shares' => $transactions->sum(function($transaction) {
                    return $transaction->transactionDetail?->integrator_share_amount ?? 0;
                }),
                'total_operator_shares' => $transactions->sum(function($transaction) {
                    return $transaction->transactionDetail?->operator_share_amount ?? 0;
                }),
            ],
        ]);
    }

    /**
     * Traiter toutes les réservations confirmées non encore traitées (batch)
     */
    public function processAllConfirmedReservations(Request $request): JsonResponse
    {
        $processed = 0;
        $skipped = 0;
        $errors = [];

        Reservation::whereNotNull('confirmed_at')
            ->orderBy('id')
            ->chunk(100, function ($reservations) use (&$processed, &$skipped, &$errors) {
                foreach ($reservations as $reservation) {
                    try {
                        // Idempotence: ignorer si transaction déjà créée pour cette réservation
                        $alreadyExists = Transaction::where('reservation_id', $reservation->id)->exists();
                        if ($alreadyExists) {
                            $skipped++;
                            continue;
                        }

                        $result = $this->reservationTransactionService->processReservationTransaction($reservation);
                        if (!($result['success'] ?? false)) {
                            $errors[] = [
                                'reservation_id' => $reservation->id,
                                'error' => $result['error'] ?? 'unknown'
                            ];
                            continue;
                        }
                        $processed++;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'reservation_id' => $reservation->id,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            });

        return response()->json([
            'success' => true,
            'message' => 'Traitement des réservations terminé',
            'data' => [
                'processed' => $processed,
                'skipped' => $skipped,
                'errors_count' => count($errors),
                'errors' => $errors,
            ]
        ]);
    }
}
