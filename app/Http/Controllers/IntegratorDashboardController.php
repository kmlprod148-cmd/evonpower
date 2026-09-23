<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\TransactionQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IntegratorDashboardController extends Controller
{
    protected TransactionQueryService $queryService;

    public function __construct(TransactionQueryService $queryService)
    {
        $this->middleware('auth');
        $this->queryService = $queryService;
    }

    /**
     * Affiche le dashboard specifique aux integrateurs.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $integrator = $user->integrator;

        if (!$integrator || !$user->hasRole('integrator')) {
            return redirect()->route('dashboard')->with('error', 'Acces non autorise. Vous devez etre un integrateur.');
        }

        $scope = $this->getScopeContext($user, $integrator);

        // Apply filters if any
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'search' => $request->get('search'),
        ];

        $stats = $this->getIntegratorStats($scope);
        $walletStats = $this->getWalletStats($scope);
        $operatorStats = $this->getOperatorStats($scope);
        $rechargesActivesList = $this->getActiveRecharges($scope);
        $chartData = $this->getChartData($scope);
        
        // Use Query Service for recent transactions to ensure consistency
        $recentTransactions = $this->queryService->applyFilters(
            $this->queryService->buildRoleBasedQuery($user),
            $filters
        )->latest()->limit(10)->get();

        $recentWalletTransactions = $this->getRecentWalletTransactions($scope);

        return view('dashboard.integrator', compact(
            'stats',
            'walletStats',
            'operatorStats',
            'rechargesActivesList',
            'chartData',
            'recentTransactions',
            'recentWalletTransactions',
            'filters'
        ));
    }

    /**
     * Calcule les statistiques du wallet pour l'integrateur sur tout son scope.
     */
    private function getWalletStats(array $scope): array
    {
        $integratorId = $scope['integrator']->id;
        $wallets = Wallet::getIntegratorScopeWallets($integratorId);
        $walletIds = $wallets->pluck('id');

        $totalBalance = (float) $wallets->sum('balance');

        $walletTransactions = WalletTransaction::whereIn('wallet_id', $walletIds);

        $totalCredits = (float) (clone $walletTransactions)->where('type', 'credit')->sum('amount');
        $totalDebits = (float) (clone $walletTransactions)->where('type', 'debit')->sum('amount');
        $transactionCount = (clone $walletTransactions)->count();

        // Stats from Query Service (reservation-based transactions)
        $reservationQuery = $this->queryService->buildRoleBasedQuery($scope['user']);
        $reservationCount = (clone $reservationQuery)->count();
        $reservationRevenue = (float) (clone $reservationQuery)->sum(DB::raw('COALESCE(price_total, amount, 0)'));

        // FALLBACK: Calculate expected balance from TransactionDetails if wallets are empty
        // This ensures we show the correct balance even if wallet creation/credit failed
        $calculatedBalanceFromDetails = $this->calculateIntegratorBalanceFromTransactionDetails($integratorId);
        
        // If wallet balance is 0 but we have TransactionDetails, use the calculated balance
        if ($totalBalance < 0.01 && $calculatedBalanceFromDetails > 0) {
            $totalBalance = $calculatedBalanceFromDetails;
            Log::info('Wallet balance corrected from TransactionDetails', [
                'integrator_id' => $integratorId,
                'calculated_balance' => $calculatedBalanceFromDetails
            ]);
        }

        return [
            'total_balance' => $totalBalance,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'wallet_transaction_count' => $transactionCount,
            'reservation_count' => $reservationCount,
            'reservation_revenue' => $reservationRevenue,
            'total_revenue' => $totalCredits + $reservationRevenue,
            'wallets_count' => $wallets->count(),
            'calculated_balance_from_details' => $calculatedBalanceFromDetails,
        ];
    }

    /**
     * Calculate integrator balance from TransactionDetails as fallback
     * This provides accurate balance even when wallet creation failed
     */
    private function calculateIntegratorBalanceFromTransactionDetails(int $integratorId): float
    {
        // Get integrator model to find the user_id
        $integratorModel = \App\Models\Integrator::find($integratorId);
        if (!$integratorModel) {
            return 0;
        }

        // Get user ID (could be user->id or integratorModel->user_id)
        $userId = $integratorModel->user_id;
        
        // Query TransactionDetails for this integrator
        $details = \App\Models\TransactionDetail::whereHas('transaction', function($q) use ($integratorId) {
                $q->whereHas('chargingPoint', function($cp) use ($integratorId) {
                    $cp->where('integrator_id', $integratorId);
                });
            })
            ->where(function($q) use ($userId, $integratorId) {
                $q->where('integrator_creator_id', $userId)
                  ->orWhere('integrator_creator_id', $integratorId);
            })
            ->get();

        // Sum integrator_share_amount (net amount for integrator)
        $totalIntegratorShare = $details->sum(function($detail) {
            return (float) ($detail->integrator_share_amount ?? 0);
        });

        return round($totalIntegratorShare, 2);
    }

    /**
     * Recupere les dernieres wallet transactions visibles dans le scope integrateur.
     */
    private function getRecentWalletTransactions(array $scope): array
    {
        $integratorId = $scope['integrator']->id;
        $wallets = Wallet::getIntegratorScopeWallets($integratorId);
        $walletIds = $wallets->pluck('id');

        return WalletTransaction::whereIn('wallet_id', $walletIds)
            ->with('wallet.owner')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (WalletTransaction $wt) {
                return [
                    'id' => $wt->id,
                    'type' => $wt->type,
                    'amount' => (float) $wt->amount,
                    'balance_after' => (float) $wt->balance_after,
                    'description' => $wt->description ?: $wt->getDescriptiveLabel(),
                    'wallet_owner' => $wt->wallet?->owner?->name ?? 'N/A',
                    'wallet_id' => $wt->wallet_id,
                    'status' => $wt->status,
                    'created_at' => $wt->created_at,
                    'currency' => $wt->metadata['currency'] ?? 'EUR',
                ];
            })
            ->toArray();
    }

    /**
     * Export all transactions visible to the integrator in CSV format.
     */
    public function exportTransactions(Request $request)
    {
        $user = auth()->user();
        $integrator = $user->integrator;

        if (!$integrator || !$user->hasRole('integrator')) {
            return redirect()->route('dashboard')->with('error', 'Acces non autorise.');
        }

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'search' => $request->get('search'),
        ];

        $scope = $this->getScopeContext($user, $integrator);

        // 1. Get Scoped Reservation/CP Transactions
        $transactionQuery = $this->queryService->buildRoleBasedQuery($user);
        $transactionQuery = $this->queryService->applyFilters($transactionQuery, $filters);
        
        $reservationTransactions = $transactionQuery->with([
            'chargingPoint.partner',
            'chargingPoint.group',
            'chargingPoint.user',
            'reservation.user',
            'businessProfile',
            'transactionDetail'
        ])->get();

        // 2. Get Scoped Wallet Transactions
        $walletTransactionsQuery = WalletTransaction::whereIn('wallet_id', function($q) use ($integrator) {
            $q->select('id')->from('wallets')
              ->where(function($sub) use ($integrator) {
                  $sub->where('owner_type', 'App\\Models\\Integrator')->where('owner_id', $integrator->id)
                      ->orWhere(function($usr) use ($integrator) {
                          $usr->where('owner_type', 'App\\Models\\User')
                              ->whereIn('owner_id', function($uid) use ($integrator) {
                                  $uid->select('id')->from('users')->where('integrator_id', $integrator->id);
                              });
                      })
                      ->orWhere(function($prt) use ($integrator) {
                          $prt->where('owner_type', 'App\\Models\\Partner')
                              ->whereIn('owner_id', function($pid) use ($integrator) {
                                  $pid->select('id')->from('partners')->where('integrator_id', $integrator->id);
                              });
                      });
              });
        });

        if (!empty($filters['date_from'])) {
            $walletTransactionsQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $walletTransactionsQuery->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['type'])) {
            $walletTransactionsQuery->where('type', $filters['type']);
        }

        $walletItems = $walletTransactionsQuery->with('wallet.owner')->latest()->get();

        $filename = 'integrator_transactions_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($reservationTransactions, $walletItems) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'ID',
                'Catégorie',
                'Type',
                'Statut',
                'Client',
                'Email Client',
                'Point de Charge',
                'Station ID',
                'Partner',
                'Opérateur',
                'Wallet Propriétaire',
                'Wallet ID',
                'Business Profile',
                'Montant Total',
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

            foreach ($reservationTransactions as $transaction) {
                $detail = $transaction->transactionDetail;
                fputcsv($file, [
                    $transaction->id,
                    'Réservation',
                    $transaction->transaction_type ?? 'client',
                    $transaction->status ?? '',
                    $transaction->reservation?->user?->name ?? $transaction->user?->name ?? 'N/A',
                    $transaction->reservation?->user?->email ?? $transaction->user?->email ?? '',
                    $transaction->chargingPoint?->name ?? 'N/A',
                    $transaction->chargingPoint?->station_id ?? '',
                    $transaction->chargingPoint?->partner?->name ?? $transaction->chargingPoint?->group?->partner?->name ?? 'N/A',
                    $transaction->chargingPoint?->user?->name ?? 'N/A',
                    '', // Pas de wallet spécifique pour une réservation CP
                    '', 
                    $transaction->businessProfile?->name ?? 'N/A',
                    $transaction->price_total ?? $transaction->amount ?? 0,
                    $transaction->price_energy ?? 0,
                    $transaction->price_time ?? 0,
                    $transaction->price_service ?? 0,
                    $transaction->price_tax ?? 0,
                    $detail?->admin_share_amount ?? 0,
                    $detail?->integrator_share_amount ?? 0,
                    $detail?->operator_share_amount ?? 0,
                    $transaction->currency ?? 'EUR',
                    $transaction->payment_method ?? '',
                    $transaction->reservation_id ?? '',
                    $transaction->start_timestamp ?? '',
                    $transaction->stop_timestamp ?? '',
                    $transaction->duration_minutes ?? $transaction->duration ?? '',
                    $transaction->energy_consumed_wh ? ($transaction->energy_consumed_wh / 1000) : (($transaction->meter_stop - $transaction->meter_start) ?? 0),
                    $transaction->description ?? '',
                    $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            foreach ($walletItems as $wt) {
                fputcsv($file, [
                    $wt->id,
                    'Wallet',
                    $wt->type,
                    $wt->status,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $wt->wallet?->owner?->name ?? 'N/A',
                    $wt->wallet_id,
                    '',
                    $wt->type === 'credit' ? $wt->amount : -$wt->amount,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    $wt->metadata['currency'] ?? 'EUR',
                    '',
                    $wt->metadata['reservation_id'] ?? '',
                    '',
                    '',
                    '',
                    '',
                    $wt->description ?? $wt->getDescriptiveLabel(),
                    $wt->created_at ? $wt->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get filtered wallet transactions for integrator scope.
     */
    private function getFilteredWalletTransactions(array $scope, array $filters): \Illuminate\Support\Collection
    {
        $integratorId = $scope['integrator']->id;
        $wallets = Wallet::getIntegratorScopeWallets($integratorId);
        $walletIds = $wallets->pluck('id');

        $walletTransactions = WalletTransaction::whereIn('wallet_id', $walletIds);

        if (!empty($filters['date_from'])) {
            $walletTransactions->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $walletTransactions->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['type'])) {
            $walletTransactions->where('type', $filters['type']);
        }

        return $walletTransactions->latest()->get();
    }

    /**
     * Calcule les statistiques generales de l'integrateur sur tout son scope.
     */
    private function getIntegratorStats(array $scope): array
    {
        $transactionQuery = $this->queryService->buildRoleBasedQuery($scope['user']);
        $reservationQuery = $this->getScopedReservationQuery($scope);

        $revenusTotaux = (float) (clone $transactionQuery)
            ->sum(DB::raw('COALESCE(price_total, amount, 0)'));

        $bornesActives = $this->queryService->getScopedChargingPoints($scope['user'])
            ->where('status', 'online')
            ->count();

        $totalPartenaires = User::where('integrator_id', $scope['integrator']->id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'operator');
            })
            ->count();

        $rechargesActives = (clone $reservationQuery)
            ->whereIn('status', ['confirmed', 'active'])
            ->count();

        $totalGroupes = DB::table('groups')
            ->where(function ($query) use ($scope) {
                $query->where('integrator_id', $scope['integrator']->id);

                if ($scope['partner_ids']->isNotEmpty()) {
                    $query->orWhereIn('partner_id', $scope['partner_ids']);
                }
            })
            ->count();

        $totalRecharges = (clone $reservationQuery)->count();

        $abonnementsActifs = User::whereHas('reservations', function ($query) use ($scope) {
            $this->applyScopedChargingPointFilter($query, $scope['charging_point_ids']);
        })
            ->whereDate('last_login', Carbon::today())
            ->count();

        return [
            'revenus_totaux' => $revenusTotaux,
            'bornes_actives' => $bornesActives,
            'total_partenaires' => $totalPartenaires,
            'recharges_actives' => $rechargesActives,
            'total_groupes' => $totalGroupes,
            'total_recharges' => $totalRecharges,
            'abonnements_actifs' => $abonnementsActifs,
        ];
    }

    /**
     * Calcule les statistiques des operateurs rattaches a l'integrateur.
     */
    private function getOperatorStats(array $scope): array
    {
        $operators = User::where('integrator_id', $scope['integrator']->id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'operator');
            })
            ->with(['partner.businessProfile'])
            ->get();

        $operatorStats = [];

        foreach ($operators as $operator) {
            $operatorChargingPointIds = ChargingPoint::query()
                ->visibleToUser($operator)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $operatorTransactionQuery = Transaction::query()
                ->where(function ($query) use ($operatorChargingPointIds) {
                    if (empty($operatorChargingPointIds)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('charging_point_id', $operatorChargingPointIds)
                        ->orWhereHas('reservation', function ($reservationQuery) use ($operatorChargingPointIds) {
                            $reservationQuery->whereIn('charging_point_id', $operatorChargingPointIds);
                        });
                });

            $operatorStats[] = [
                'id' => $operator->id,
                'name' => $operator->name,
                'email' => $operator->email,
                'is_active' => $operator->is_active,
                'business_profile' => $operator->partner?->businessProfile?->name ?? 'Aucun',
                'charging_points_count' => count($operatorChargingPointIds),
                'transactions_count' => (clone $operatorTransactionQuery)->count(),
                'revenue' => (float) (clone $operatorTransactionQuery)->sum(DB::raw('COALESCE(price_total, amount, 0)')),
                'created_at' => $operator->created_at,
            ];
        }

        return $operatorStats;
    }

    /**
     * Recupere les recharges actives visibles dans le scope integrateur.
     */
    private function getActiveRecharges(array $scope): array
    {
        return $this->getScopedReservationQuery($scope)
            ->whereIn('status', ['confirmed', 'active'])
            ->with(['chargingPoint', 'user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($reservation) {
                return [
                    'name' => $reservation->chargingPoint->name ?? 'N/A',
                    'user' => $reservation->user->name ?? 'Invite',
                    'kwh' => $reservation->reservation_value ?? 0,
                    'duration' => $reservation->start_time ? $reservation->start_time->diffInMinutes(now()) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Genere les donnees du graphique pour toutes les reservations visibles.
     */
    private function getChartData(array $scope): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');

            $values[] = $this->getScopedReservationQuery($scope)
                ->whereDate('created_at', $date)
                ->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Recupere les dernieres transactions visibles dans le scope integrateur.
     */
    private function getRecentTransactions(array $scope)
    {
        return $this->queryService->buildRoleBasedQuery($scope['user'])
            ->with(['chargingPoint', 'user'])
            ->latest()
            ->limit(10)
            ->get();
    }

    private function getScopeContext($user, $integrator): array
    {
        return [
            'user' => $user,
            'integrator' => $integrator,
            'charging_point_ids' => $this->queryService->getScopedChargingPointIds($user),
            'partner_ids' => \App\Models\Partner::where('integrator_id', $integrator->id)->pluck('id'),
        ];
    }

    private function getScopedReservationQuery(array $scope)
    {
        return Reservation::query()
            ->with(['chargingPoint', 'user'])
            ->where(function ($query) use ($scope) {
                $this->applyScopedChargingPointFilter($query, $scope['charging_point_ids']);
            });
    }

    private function applyScopedChargingPointFilter($query, array $chargingPointIds): void
    {
        if (empty($chargingPointIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('charging_point_id', $chargingPointIds);
    }
}
