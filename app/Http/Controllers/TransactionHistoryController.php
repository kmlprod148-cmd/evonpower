<?php

namespace App\Http\Controllers;

use App\Models\Integrator;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\TransactionCalculator;
use App\Services\TransactionQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionHistoryController extends Controller
{
    protected $transactionCalculator;
    protected TransactionQueryService $queryService;

    public function __construct(TransactionCalculator $transactionCalculator, TransactionQueryService $queryService)
    {
        $this->transactionCalculator = $transactionCalculator;
        $this->queryService = $queryService;
    }

    /**
     * Affiche l'historique des transactions selon le rôle de l'utilisateur
     * Chaque utilisateur voit uniquement ses propres transactions/répartitions
     */
    public function history()
    {
        $user = Auth::user();
        $displayService = app(\App\Services\TransactionDisplayService::class);

        // Construire la requête de base
        $query = Transaction::with(['chargingPoint', 'repartition', 'user']);

        // Filtrer selon le rôle de l'utilisateur (chacun voit ses propres enregistrements)
        $query = $displayService->filterTransactionsByUserRole($query, $user);

        // Paginer
        $transactions = $query->latest()->paginate(20);

        // Ajouter les montants à afficher selon le rôle pour chaque transaction
        $transactions->getCollection()->transform(function ($transaction) use ($user, $displayService) {
            // S'assurer que la répartition existe
            if (!$transaction->repartition && in_array($transaction->status, ['completed', 'approved', 'paid', 'confirmed'])) {
                try {
                    $finalizationService = app(\App\Services\TransactionFinalizationService::class);
                    $finalizationService->finalizeTransaction($transaction);
                    $transaction->load('repartition');
                } catch (\Exception $e) {
                    \Log::warning('Impossible de créer la répartition dans l\'historique', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtenir les montants à afficher selon le rôle
            $amounts = $displayService->getDisplayAmounts($transaction, $user);
            $transaction->display_amounts = $amounts;
            $transaction->user_amount = $amounts['your_amount'];
            $transaction->display_type = $amounts['display_type'];
            
            return $transaction;
        });

        return view('transactions.history', compact('transactions', 'user'));
    }

    /**
     * Affiche les détails d'une transaction
     * Les détails visibles dépendent du rôle de l'utilisateur :
     * - Admin: Voit tous les détails (admin, integrator, operator)
     * - Integrator: Voit ses propres détails et ceux de ses opérateurs
     * - Operator: Voit uniquement ses propres détails
     */
    public function show(Transaction $transaction)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur peut accéder à cette transaction
        if (!$this->canViewTransaction($transaction, $user)) {
            abort(403, 'Vous n\'avez pas accès à cette transaction.');
        }

        // Charger les relations nécessaires
        $transaction->load([
            'repartition', 
            'repartitions', 
            'transactionDetail', 
            'chargingPoint',
            'chargingPoint.group',
            'chargingPoint.group.partner',
            'chargingPoint.group.partner.integrator',
            'chargingPoint.group.user',
            'user'
        ]);

        // Utiliser le nouveau service pour créer/mettre à jour la répartition
        try {
            $repartition = $this->transactionCalculator->createRepartition($transaction);
            
            // Recharger après création
            $transaction->load('repartition', 'repartitions');
        } catch (\Exception $e) {
            \Log::warning('Impossible de créer la répartition', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            $repartition = null;
        }

        // Obtenir les détails de calcul
        try {
            $calculationDetails = $this->transactionCalculator->calculateDetailed($transaction);
        } catch (\Exception $e) {
            \Log::warning('Impossible de calculer les détails', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            $calculationDetails = null;
        }

        // Obtenir les montants à afficher selon le rôle
        $displayService = app(\App\Services\TransactionDisplayService::class);
        $displayAmounts = $displayService->getDisplayAmounts($transaction, $user);

        // Déterminer quels détails afficher selon le rôle
        $visibleDetails = $this->getVisibleDetailsForRole($user, $transaction);

        return view('transactions.show', compact(
            'transaction', 
            'repartition', 
            'calculationDetails',
            'displayAmounts',
            'visibleDetails'
        ));
    }

    /**
     * Vérifie si l'utilisateur peut voir cette transaction
     * 
     * @param Transaction $transaction
     * @param User $user
     * @return bool
     */
    protected function canViewTransaction(Transaction $transaction, User $user): bool
    {
        // Admin peut tout voir
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Utiliser le scope visibleToUser du modèle Transaction pour vérifier l'accès
        return Transaction::visibleToUser($user)
            ->where('id', $transaction->id)
            ->exists();
    }

    /**
     * Détermine quels détails sont visibles selon le rôle de l'utilisateur
     * 
     * @param User $user
     * @param Transaction $transaction
     * @return array
     */
    protected function getVisibleDetailsForRole(User $user, Transaction $transaction): array
    {
        $details = [
            'show_admin_details' => false,
            'show_integrator_details' => false,
            'show_operator_details' => false,
            'show_partner_details' => false,
            'show_hierarchy' => false,
            'show_business_profiles' => false,
            'show_calculation_breakdown' => false,
            'show_repartition_details' => false,
        ];

        // Admin voit tous les détails
        if ($user->hasRole(['admin', 'super_admin'])) {
            $details['show_admin_details'] = true;
            $details['show_integrator_details'] = true;
            $details['show_operator_details'] = true;
            $details['show_partner_details'] = true;
            $details['show_hierarchy'] = true;
            $details['show_business_profiles'] = true;
            $details['show_calculation_breakdown'] = true;
            $details['show_repartition_details'] = true;
            return $details;
        }

        // Intégrateur voit ses détails et ceux de ses opérateurs
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id ?? null;
            
            // Vérifier si cette transaction concerne cet intégrateur
            $isRelatedTransaction = false;
            
            if ($transaction->chargingPoint) {
                $cp = $transaction->chargingPoint;
                if ($cp->integrator_id == $integratorId) {
                    $isRelatedTransaction = true;
                } elseif ($cp->group && $cp->group->partner && $cp->group->partner->integrator_id == $integratorId) {
                    $isRelatedTransaction = true;
                }
            }
            
            if ($isRelatedTransaction) {
                $details['show_integrator_details'] = true;
                $details['show_operator_details'] = true;
                $details['show_repartition_details'] = true;
                $details['show_hierarchy'] = true;
                
                // Voir les Business Profiles utilisés
                if ($transaction->transactionDetail || $transaction->repartition) {
                    $details['show_business_profiles'] = true;
                }
            }
            
            return $details;
        }

        // Operator voit uniquement ses propres détails
        if ($user->hasRole('operator')) {
            $isUserTransaction = $transaction->user_id == $user->id;
            
            // Vérifier aussi via le charging point
            if (!$isUserTransaction && $transaction->chargingPoint) {
                $cp = $transaction->chargingPoint;
                $isUserTransaction = (
                    $cp->user_id == $user->id ||
                    ($cp->group && $cp->group->user_id == $user->id) ||
                    ($cp->created_by == $user->id || $cp->created_by_id == $user->id)
                );
            }
            
            if ($isUserTransaction) {
                $details['show_operator_details'] = true;
                $details['show_repartition_details'] = true;
                
                // Peut voir les frais qui lui ont été déduits
                if ($transaction->transactionDetail || $transaction->repartition) {
                    $details['show_calculation_breakdown'] = true;
                }
            }
            
            return $details;
        }

        // Partner voit ses détails
        if ($user->hasRole('partner') && $user->partner_id) {
            $isPartnerTransaction = false;
            
            if ($transaction->chargingPoint) {
                $cp = $transaction->chargingPoint;
                $isPartnerTransaction = (
                    $cp->partner_id == $user->partner_id ||
                    ($cp->group && $cp->group->partner_id == $user->partner_id)
                );
            }
            
            if ($isPartnerTransaction) {
                $details['show_partner_details'] = true;
                $details['show_operator_details'] = true;
                $details['show_repartition_details'] = true;
            }
            
            return $details;
        }

        return $details;
    }

    /**
     * API pour obtenir l'historique des transactions (JSON)
     * Chaque utilisateur voit uniquement ses propres transactions/répartitions
     */
    public function apiHistory(Request $request)
    {
        $user = Auth::user();
        $displayService = app(\App\Services\TransactionDisplayService::class);
        $finalizationService = app(\App\Services\TransactionFinalizationService::class);

        // Construire la requête de base
        $query = Transaction::with(['chargingPoint', 'repartition', 'user']);

        // Filtrer selon le rôle de l'utilisateur
        $query = $displayService->filterTransactionsByUserRole($query, $user);

        // Récupérer les transactions
        $transactions = $query->latest()->limit(50)->get();

        // Transformer les données pour l'API avec les montants corrects selon le rôle
        $transactions = $transactions->map(function ($transaction) use ($user, $displayService, $finalizationService) {
            // S'assurer que la répartition existe
            if (!$transaction->repartition && in_array($transaction->status, ['completed', 'approved', 'paid', 'confirmed'])) {
                try {
                    $finalizationService->finalizeTransaction($transaction);
                    $transaction->load('repartition');
                } catch (\Exception $e) {
                    \Log::warning('Impossible de créer la répartition dans l\'API', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Obtenir les montants à afficher selon le rôle
            $amounts = $displayService->getDisplayAmounts($transaction, $user);

            return [
                'id' => $transaction->id,
                'reference' => $transaction->id,
                'amount' => $transaction->amount,
                'total_amount' => $amounts['total_amount'],
                'user_amount' => $amounts['your_amount'], // Montant que l'utilisateur doit voir
                'display_type' => $amounts['display_type'],
                'fees_breakdown' => $amounts['fees_breakdown'] ?? [],
                'created_at' => $transaction->created_at->format('d/m/Y H:i'),
                'status' => $transaction->status,
                'charging_point' => $transaction->chargingPoint ? $transaction->chargingPoint->name : 'N/A',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'user_role' => $user->getRoleNames()->first(),
        ]);
    }

    /**
     * Obtient les statistiques des transactions pour l'utilisateur connecté
     */
    public function statistics()
    {
        $user = Auth::user();
        $role = $user->getRoleNames()->first();

        $query = Transaction::query();

        // Filtrer selon le rôle
        if ($role === 'integrator') {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('integrator_id', $user->id);
            });
        } elseif ($role === 'operator') {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $totalTransactions = $query->count();
        $totalAmount = $query->sum('amount');
        
        // Calculer les montants nets selon le rôle
        $netAmount = 0;
        $transactions = $query->with('repartitions')->get();
        
        foreach ($transactions as $transaction) {
            $repartition = $transaction->repartitions->first();
            if ($repartition) {
                $netAmount += $repartition->getAmountForRole($role);
            }
        }

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_transactions' => $totalTransactions,
                'total_amount' => $totalAmount,
                'net_amount' => $netAmount,
                'role' => $role,
            ]
        ]);
    }

    /**
     * Affiche l'historique des transactions pour les intégrateurs.
     *
     * Utilise TransactionQueryService pour appliquer correctement la portée hiérarchique :
     * - Points de charge détenus directement par l'intégrateur
     * - Points de charge des opérateurs créés par l'intégrateur
     * - Points de charge des partenaires créés par l'intégrateur
     * - Toutes les transactions de réservation associées à ces ressources
     */
    public function integratorIndex(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('integrator')) {
            abort(403, 'Accès non autorisé. Vous devez être un intégrateur.');
        }

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
            'type'      => $request->get('type'),
            'status'    => $request->get('status'),
            'search'    => $request->get('search'),
        ];

        // Build hierarchically-scoped transaction query
        $baseQuery  = $this->queryService->buildRoleBasedQuery($user);
        $baseQuery  = $this->queryService->applyFilters($baseQuery, $filters);

        // Paginate
        $paginator  = $baseQuery->latest()->paginate(20);
        $models     = $paginator->getCollection();

        // Format Transaction models into the array structure expected by the view
        $transactions = $models->map(function (Transaction $t) {
            // Resolve the best available amount — check all stored fields in priority order.
            // price_total / amount / total_amount are set by different code paths; also fall
            // back to the linked reservation's actual/estimated cost when none is set.
            $reservation   = $t->reservation;
            $priceTotal = (float) ($t->price_total
                ?? $t->amount
                ?? $t->total_amount
                ?? $reservation?->actual_cost
                ?? $reservation?->estimated_cost
                ?? 0);

            $detail = $t->transactionDetail;

            // Use the integrator's share when it is explicitly > 0 (meaning the commission
            // calculation ran). Otherwise fall back to the full transaction amount so the
            // integrator can still see the business volume even when share details are missing.
            $integratorShare = ($detail && (float) $detail->integrator_share_amount > 0)
                ? (float) $detail->integrator_share_amount
                : $priceTotal;

            $chargingPoint = $t->chargingPoint;
            $clientUser    = $t->user ?? $reservation?->user;
            $operatorUser  = $chargingPoint?->user;

            return [
                'id'          => $t->id,
                'created_at'  => $t->created_at,
                'user_id'     => $clientUser?->id,
                'user_name'   => $operatorUser?->name ?? $clientUser?->name ?? 'N/A',
                'user_email'  => $operatorUser?->email ?? $clientUser?->email ?? '',
                'type'        => $t->transaction_type ?? 'payment',
                'amount'      => $integratorShare,
                'is_credit'   => true,
                'is_debit'    => false,
                'status'      => $t->status ?? 'pending',
                'description' => $t->description ?? '',
                'reference'   => $t->reference_id ?? (string) $t->id,
                'charging_point' => $chargingPoint ? [
                    'id'            => $chargingPoint->id,
                    'name'          => $chargingPoint->name,
                    'serial_number' => $chargingPoint->serial_number ?? $chargingPoint->station_id ?? '',
                ] : null,
                'reservation' => $reservation ? [
                    'id'             => $reservation->id,
                    'estimated_cost' => $reservation->estimated_cost ?? 0,
                ] : null,
                // Nested 'transaction' key used by the amount display block in the view
                'transaction' => [
                    'id'               => $t->id,
                    'amount'           => (float) ($t->amount ?? $t->price_total ?? 0),
                    'price_total'      => $priceTotal,
                    'collected_amount' => $priceTotal,
                    'status'           => $t->status ?? '',
                    'charging_point'   => $chargingPoint ? [
                        'id'            => $chargingPoint->id,
                        'name'          => $chargingPoint->name,
                        'serial_number' => $chargingPoint->serial_number ?? '',
                    ] : null,
                    'reservation'      => $reservation ? [
                        'id'             => $reservation->id,
                        'estimated_cost' => $reservation->estimated_cost ?? 0,
                    ] : null,
                ],
            ];
        })->all();

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
        ];

        // Summary totals — re-query without pagination so numbers reflect all filtered rows.
        // Include all columns needed to resolve the best available amount per transaction.
        $integratorId   = $this->resolveIntegratorId($user);
        $summaryQuery   = $this->queryService->applyFilters(
            $this->queryService->buildRoleBasedQuery($user),
            $filters
        );
        $allFiltered    = $summaryQuery->with('reservation:id,estimated_cost,actual_cost')
            ->get(['id', 'price_total', 'amount', 'total_amount', 'status', 'reservation_id']);
        $totalRevenue   = (float) $allFiltered->sum(function ($t) {
            return (float) ($t->price_total
                ?? $t->amount
                ?? $t->total_amount
                ?? $t->reservation?->actual_cost
                ?? $t->reservation?->estimated_cost
                ?? 0);
        });
        $walletBalance  = $integratorId
            ? (float) Wallet::getIntegratorScopeWallets($integratorId)->sum('balance')
            : 0.0;

        $summary = [
            'total_transactions' => $paginator->total(),
            'total_credits'      => $totalRevenue,
            'total_debits'       => 0.0,
            'net_balance'        => $walletBalance ?: $totalRevenue,
        ];

        return view('integrator.transaction-history.index', compact(
            'transactions',
            'pagination',
            'summary',
            'filters'
        ));
    }

    /**
     * Export all transactions visible to the integrator as CSV.
     * Includes both reservation-based transactions and wallet transactions.
     */
    public function integratorExport(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('integrator')) {
            abort(403, 'Accès non autorisé.');
        }

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to'   => $request->get('date_to'),
            'type'      => $request->get('type'),
            'status'    => $request->get('status'),
            'search'    => $request->get('search'),
        ];

        // 1. Hierarchically-scoped reservation/CP transactions
        $txQuery = $this->queryService->applyFilters(
            $this->queryService->buildRoleBasedQuery($user),
            $filters
        );

        $reservationTransactions = $txQuery->with([
            'chargingPoint.partner',
            'chargingPoint.group',
            'chargingPoint.user',
            'reservation.user',
            'transactionDetail',
        ])->latest()->get();

        // 2. Wallet transactions scoped to the integrator's hierarchy
        $integratorId = $this->resolveIntegratorId($user);
        $walletItems  = collect();

        if ($integratorId) {
            $integratorModel = Integrator::find($integratorId);

            $walletQuery = WalletTransaction::whereIn('wallet_id', function ($q) use ($integratorId, $integratorModel) {
                $q->select('id')->from('wallets')->where(function ($sub) use ($integratorId, $integratorModel) {
                    // Integrator's own Integrator-model wallet
                    $sub->where('owner_type', 'App\\Models\\Integrator')
                        ->where('owner_id', $integratorId);

                    // Integrator user wallet
                    if ($integratorModel?->user_id) {
                        $sub->orWhere(function ($u) use ($integratorModel) {
                            $u->where('owner_type', 'App\\Models\\User')
                              ->where('owner_id', $integratorModel->user_id);
                        });
                    }

                    // All operator-user wallets under this integrator
                    $sub->orWhere(function ($u) use ($integratorId) {
                        $u->where('owner_type', 'App\\Models\\User')
                          ->whereIn('owner_id', function ($uid) use ($integratorId) {
                              $uid->select('id')->from('users')->where('integrator_id', $integratorId);
                          });
                    });

                    // All partner wallets under this integrator
                    $sub->orWhere(function ($p) use ($integratorId) {
                        $p->where('owner_type', 'App\\Models\\Partner')
                          ->whereIn('owner_id', function ($pid) use ($integratorId) {
                              $pid->select('id')->from('partners')->where('integrator_id', $integratorId);
                          });
                    });
                });
            });

            if (!empty($filters['date_from'])) {
                $walletQuery->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $walletQuery->whereDate('created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['type'])) {
                $walletQuery->where('type', $filters['type']);
            }

            $walletItems = $walletQuery->with('wallet.owner')->latest()->get();
        }

        $filename = 'integrator_transactions_' . date('Y-m-d_H-i-s') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($reservationTransactions, $walletItems) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID',
                'Catégorie',
                'Type',
                'Statut',
                'Client / Utilisateur',
                'Email Client',
                'Point de Charge',
                'Station ID',
                'Partenaire',
                'Opérateur',
                'Wallet Propriétaire',
                'Wallet ID',
                'Business Profile',
                'Montant Total (TTC)',
                'Prix Énergie',
                'Prix Temps',
                'Prix Service',
                'Taxes',
                'Commission Admin',
                'Commission Intégrateur',
                'Commission Opérateur',
                'Devise',
                'Méthode Paiement',
                'Réservation ID',
                'Début Session',
                'Fin Session',
                'Durée (min)',
                'Énergie (kWh)',
                'Description',
                'Date Création',
            ]);

            foreach ($reservationTransactions as $t) {
                $detail = $t->transactionDetail;
                fputcsv($file, [
                    $t->id,
                    'Réservation/CP',
                    $t->transaction_type ?? 'payment',
                    $t->status ?? '',
                    $t->reservation?->user?->name ?? $t->user?->name ?? 'N/A',
                    $t->reservation?->user?->email ?? $t->user?->email ?? '',
                    $t->chargingPoint?->name ?? 'N/A',
                    $t->chargingPoint?->station_id ?? '',
                    $t->chargingPoint?->partner?->name
                        ?? $t->chargingPoint?->group?->partner?->name
                        ?? 'N/A',
                    $t->chargingPoint?->user?->name ?? 'N/A',
                    '',
                    '',
                    $t->businessProfile?->name ?? 'N/A',
                    $t->price_total ?? $t->amount ?? 0,
                    $t->price_energy ?? 0,
                    $t->price_time ?? 0,
                    $t->price_service ?? 0,
                    $t->price_tax ?? 0,
                    $detail?->admin_share_amount ?? 0,
                    $detail?->integrator_share_amount ?? 0,
                    $detail?->operator_share_amount ?? 0,
                    $t->currency ?? 'EUR',
                    $t->payment_method ?? '',
                    $t->reservation_id ?? '',
                    $t->start_timestamp ?? '',
                    $t->stop_timestamp ?? '',
                    $t->duration_minutes ?? $t->duration ?? '',
                    $t->energy_consumed_wh
                        ? round($t->energy_consumed_wh / 1000, 4)
                        : (isset($t->meter_stop, $t->meter_start)
                            ? round(($t->meter_stop - $t->meter_start) / 1000, 4)
                            : 0),
                    $t->description ?? '',
                    $t->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            foreach ($walletItems as $wt) {
                $amount = $wt->type === 'credit' ? (float) $wt->amount : -(float) $wt->amount;
                fputcsv($file, [
                    $wt->id,
                    'Wallet',
                    $wt->type,
                    $wt->status ?? '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $wt->wallet?->owner?->name ?? 'N/A',
                    $wt->wallet_id,
                    '',
                    $amount,
                    0, 0, 0, 0, 0, 0, 0,
                    $wt->metadata['currency'] ?? 'EUR',
                    '',
                    $wt->metadata['reservation_id'] ?? '',
                    '', '', '', '',
                    $wt->description ?? (method_exists($wt, 'getDescriptiveLabel') ? $wt->getDescriptiveLabel() : ''),
                    $wt->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Resolve integrator model ID for the given user.
     */
    protected function resolveIntegratorId(User $user): ?int
    {
        if ($user->integrator_id) {
            return $user->integrator_id;
        }

        return Integrator::where('user_id', $user->id)->value('id');
    }
}