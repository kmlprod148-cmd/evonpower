<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Account;
use App\Models\Reservation;
use App\Services\TransactionService;
use App\Services\RevenueDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ChargingPointFeeCalculationService;

class AdminTransactionController extends Controller
{
    protected $transactionService;
    protected $revenueDistributionService;
    protected $chargingPointFeeCalculationService;

    public function __construct(
        TransactionService $transactionService,
        RevenueDistributionService $revenueDistributionService,
        ChargingPointFeeCalculationService $chargingPointFeeCalculationService
    ) {
        $this->transactionService = $transactionService;
        $this->revenueDistributionService = $revenueDistributionService;
        $this->chargingPointFeeCalculationService = $chargingPointFeeCalculationService;
    }

    /**
     * Vérifier si l'utilisateur a les droits d'administration
     */
    private function checkAdminAccess()
    {
        Gate::authorize('view_admin_transactions');
    }

    /**
     * Affiche la vue admin complète des transactions avec suivi des soldes
     */
    public function index(Request $request)
    {
        $this->checkAdminAccess();

        // Récupération des transactions avec filtres
        $filters = $request->only([
            'transaction_type',
            'transaction_category',
            'business_profile_id',
            'business_profile_owner_type',
            'start_date',
            'end_date',
            'status'
        ]);

        // CORRECTION: L'admin doit voir TOUTES les transactions du système
        // Pas seulement celles avec réservation approuvée
        $query = Transaction::with([
            'user',
            'chargingPoint',
            'reservation',
            'transactionDetail',
            'businessProfile'
        ])
        ->latest('created_at');
        
        // Appliquer les filtres
        if (!empty($filters['transaction_type'])) {
            $query->where('type', $filters['transaction_type']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['business_profile_id'])) {
            $query->whereHas('chargingPoint', function($cpQuery) use ($filters) {
                $cpQuery->where('business_profile_id', $filters['business_profile_id']);
            });
        }
        
        // Pagination
        $transactions = $query->paginate(50)->appends($filters);
        
        // Charger toutes les relations nécessaires pour afficher les détails complets
        $transactions->load([
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator',
            'reservation',
            'chargingPoint.businessProfile',
            'user'
        ]);
        
        // Calcul des statistiques complètes (sur toutes les transactions, pas seulement la page actuelle)
        // CORRECTION: L'admin doit voir TOUTES les transactions
        $statisticsQuery = Transaction::query();
        
        // Appliquer les mêmes filtres que la requête principale (sauf pagination)
        if (!empty($filters['transaction_type'])) {
            $statisticsQuery->where('type', $filters['transaction_type']);
        }
        
        if (!empty($filters['status'])) {
            $statisticsQuery->where('status', $filters['status']);
        }
        
        if (!empty($filters['start_date'])) {
            $statisticsQuery->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $statisticsQuery->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['business_profile_id'])) {
            $statisticsQuery->whereHas('chargingPoint', function($cpQuery) use ($filters) {
                $cpQuery->where('business_profile_id', $filters['business_profile_id']);
            });
        }
        
        $hasFilters = array_filter($filters) !== [];
        $statistics = $this->calculateAdminStatistics($statisticsQuery, $hasFilters);
        
        // Calcul des soldes
        $balanceSummary = $this->calculateBalanceSummary();
        
        // Ajouter le solde admin aux statistiques pour l'affichage
        $statistics['admin_balance'] = $balanceSummary['total_admin_balance'];
        $statistics['admin_balance_calculated_from'] = $balanceSummary['admin_balance_calculated_from'] ?? 'TransactionDetail';
        $statistics['admin_transaction_details_count'] = $balanceSummary['admin_transaction_details_count'] ?? 0;

        // Données pour les filtres
        $businessProfiles = BusinessProfile::with(['integrator', 'partner'])->get();

        return view('transactions.admin', compact(
            'transactions',
            'statistics',
            'balanceSummary',
            'businessProfiles'
        ));
    }

    /**
     * Affiche les détails d'une transaction
     */
    public function show(Transaction $transaction)
    {
        Gate::authorize('view_admin_transaction_details');

        $transaction->load([
            'chargingPoint.station.owner.businessProfile.integrator',
            'chargingPoint.station.owner.businessProfile.partner',
            'chargingPoint.businessProfile',
            'chargingPoint.group',
            'user',
            'reservation',
            'pricingPlan',
            'commissionPlan',
            'businessProfileOwner',
            'activationFeeBusinessProfile',
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator'
        ]);

        return view('admin.transactions.show', compact('transaction'));
    }

    /**
     * Calculer les statistiques admin
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Support\Collection $query
     * @param bool $hasFilters  True when at least one filter was supplied by the request.
     *                          When false and no transactions exist the method falls back to
     *                          aggregating all transaction_details, which is the intended
     *                          "no filter" behaviour.  When true an empty result must
     *                          produce zeros, not a sum of the whole table.
     */
    protected function calculateAdminStatistics($query, bool $hasFilters = false)
    {
        // Si c'est une requête de base, créer une copie pour les calculs
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            $baseQuery = $query;
            $transactionIds = $baseQuery->pluck('id');
        } else {
            // Si c'est déjà une collection de transactions
            $transactionIds = $query->pluck('id');
            $baseQuery = Transaction::whereIn('id', $transactionIds);
        }

        $totalTransactions = $baseQuery->count();
        $totalAmount = $baseQuery->sum('amount');
        // Les transactions confirmées (réservations approuvées) et complétées sont considérées comme terminées
        $completedTransactions = (clone $baseQuery)->whereIn('status', ['completed', 'confirmed'])->count();
        $pendingTransactions = (clone $baseQuery)->where('status', 'pending')->count();

        // Calculer les parts totales depuis TransactionDetail.
        // When a filter was applied but returned no rows the share totals must be
        // zero, not the sum of the entire transaction_details table.
        if ($transactionIds->isNotEmpty()) {
            $totalAdminShare = DB::table('transaction_details')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->whereIn('transactions.id', $transactionIds)
                ->sum('transaction_details.admin_share_amount');

            $totalIntegratorShare = DB::table('transaction_details')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->whereIn('transactions.id', $transactionIds)
                ->sum('transaction_details.integrator_share_amount');

            $totalOperatorShare = DB::table('transaction_details')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->whereIn('transactions.id', $transactionIds)
                ->sum('transaction_details.operator_share_amount');
        } elseif (!$hasFilters) {
            // No filters were applied at all: aggregate across ALL transaction_details
            $totalAdminShare = DB::table('transaction_details')
                ->where('admin_share_amount', '>', 0)
                ->sum('admin_share_amount');

            $totalIntegratorShare = DB::table('transaction_details')
                ->where('integrator_share_amount', '>', 0)
                ->sum('integrator_share_amount');

            $totalOperatorShare = DB::table('transaction_details')
                ->where('operator_share_amount', '>', 0)
                ->sum('operator_share_amount');
        } else {
            // Filters were applied but matched no transactions → all shares are zero
            $totalAdminShare = 0;
            $totalIntegratorShare = 0;
            $totalOperatorShare = 0;
        }
        
        return [
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
            'completed_transactions' => $completedTransactions,
            'pending_transactions' => $pendingTransactions,
            'total_admin_share' => $totalAdminShare ?? 0,
            'total_integrator_share' => $totalIntegratorShare ?? 0,
            'total_operator_share' => $totalOperatorShare ?? 0,
        ];
    }

    /**
     * Calculer le résumé des soldes
     * CORRECTION: Calculer le solde admin à partir de toutes les TransactionDetail avec admin_share_amount > 0
     */
    protected function calculateBalanceSummary()
    {
        // CORRECTION: Calculer le solde admin depuis TransactionDetail (source de vérité)
        // IMPORTANT: Pour l'admin, inclure TOUTES les TransactionDetail avec admin_share_amount > 0
        // car si un TransactionDetail existe avec des parts admin, la transaction doit être comptabilisée
        // Peu importe le statut de la transaction ou de la réservation
        $adminDetails = \App\Models\TransactionDetail::where('admin_share_amount', '>', 0)
            ->with('transaction.reservation')
            ->get();
        
        // Calculer le solde admin total depuis toutes les parts
        // Inclure toutes les parts admin, même si la transaction est pending
        // car si TransactionDetail existe, les parts ont été calculées
        $totalAdminBalance = 0;
        $paidAdminBalance = 0;
        $pendingAdminBalance = 0;
        
        foreach ($adminDetails as $detail) {
            $adminShare = (float) $detail->admin_share_amount;
            $transaction = $detail->transaction;
            
            // Total inclut toutes les parts
            $totalAdminBalance += $adminShare;
            
            // Séparer en payé/en attente selon le statut
            if ($transaction && (
                in_array($transaction->status, ['completed', 'confirmed']) ||
                ($transaction->reservation && in_array($transaction->reservation->status instanceof \App\Enums\ReservationStatus ? $transaction->reservation->status->value : $transaction->reservation->status, ['confirmed', 'completed']))
            )) {
                // Transactions complétées ou réservations approuvées = payé
                $paidAdminBalance += $adminShare;
            } else {
                // Autres = en attente
                $pendingAdminBalance += $adminShare;
            }
        }
        
        // Le solde actuel = parts payées (complétées/confirmées/approuvées)
        $totalAdminBalance = $paidAdminBalance;
        
        // Calculer les soldes depuis les wallets pour les autres rôles
        $totalIntegratorBalance = DB::table('wallets')
            ->join('users', 'wallets.user_id', '=', 'users.id')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', 'integrator')
            ->sum('wallets.balance');
            
        $totalOperatorBalance = DB::table('wallets')
            ->join('users', 'wallets.user_id', '=', 'users.id')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.name', 'operator')
            ->sum('wallets.balance');
        
        // Récupérer aussi les intégrateurs et partenaires pour l'affichage
        $integrators = \App\Models\Integrator::with('user')->get();
        $partners = \App\Models\Partner::with('user')->get();
        $businessProfiles = \App\Models\BusinessProfile::with('account')->get();
        
        return [
            'total_admin_balance' => $totalAdminBalance,
            'total_integrator_balance' => $totalIntegratorBalance ?? 0,
            'total_operator_balance' => $totalOperatorBalance ?? 0,
            'total_system_balance' => ($totalAdminBalance + ($totalIntegratorBalance ?? 0) + ($totalOperatorBalance ?? 0)),
            'admin_balance_calculated_from' => 'TransactionDetail',
            'admin_transaction_details_count' => $adminDetails->count(),
            'integrators' => $integrators->map(function($integrator) {
                $wallet = $integrator->user->wallet ?? null;
                $wallet?->refresh();
                return [
                    'id' => $integrator->id,
                    'name' => $integrator->name,
                    'balance' => $wallet ? (float) $wallet->balance : 0,
                    'currency' => 'EUR'
                ];
            }),
            'partners' => $partners->map(function($partner) {
                $wallet = $partner->user->wallet ?? null;
                $wallet?->refresh();
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'balance' => $wallet ? (float) $wallet->balance : 0,
                    'currency' => 'EUR'
                ];
            }),
            'business_profiles' => $businessProfiles->map(function($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'balance' => $profile->account->balance ?? 0,
                    'currency' => 'EUR'
                ];
            }),
        ];
    }

    /**
     * Approuve une réservation et crée automatiquement la transaction avec calcul avancé des frais
     */
    public function approveReservation(Request $request, Reservation $reservation)
    {
        $this->checkAdminAccess();

        try {
            DB::beginTransaction();

            // Vérifier que la réservation est en attente (gérer enum ou string)
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
                ? $reservation->status->value
                : $reservation->status;
            if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
                throw new \Exception('La réservation n\'est pas en attente d\'approbation');
            }

            // Récupérer la borne de recharge
            $chargingPoint = $reservation->chargingPoint;
            if (!$chargingPoint) {
                throw new \Exception('Borne de recharge non trouvée pour cette réservation');
            }

            // Calculer le coût total de la réservation basé sur les données de réservation
            $reservationTotal = $this->calculateReservationTotalFromData($reservation);
            
            // Utiliser le coût estimé de la réservation s'il existe, sinon utiliser le total calculé
            $baseCost = $reservation->estimated_cost ?? $reservationTotal;
            
            // Si le coût de base est toujours nul, calculer à partir du plan tarifaire
            if ($baseCost <= 0) {
                $baseCost = $this->calculateCostFromPricingPlan($reservation);
            }

            // Vérifier que nous avons un montant valide
            if ($baseCost <= 0) {
                throw new \Exception('Impossible de calculer un montant valide pour cette réservation. Vérifiez le plan tarifaire et les données de réservation.');
            }

            // GARDER LE MONTANT TOTAL AFFICHÉ - Ne pas ajouter de frais cachés
            // Le montant total doit rester exactement le même que celui affiché au client
            $totalCost = $baseCost; // Garder le montant exact affiché

            // Mettre à jour la réservation avec le montant total
            $reservation->update([
                'status' => \App\Enums\ReservationStatus::CONFIRMED->value,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'estimated_cost' => $baseCost,
                'actual_cost' => $totalCost,
                'total_cost' => $totalCost,
                'total_amount' => $totalCost // Nécessaire pour ReservationTransactionService
            ]);

            // CRÉER TOUTES LES PARTITIONS AVEC ReservationTransactionService
            // Ce service crée automatiquement :
            // - Transaction principale
            // - TransactionDetail avec admin_share_amount, integrator_share_amount, operator_share_amount
            // - TransactionHierarchy pour les relations Admin↔Intégrateur et Intégrateur↔Opérateur
            $reservationTransactionService = app(\App\Services\ReservationTransactionService::class);
            $transactionResult = $reservationTransactionService->processReservationTransaction($reservation);

            if (!$transactionResult['success']) {
                throw new \Exception('Échec de la création des transactions avec répartition: ' . ($transactionResult['error'] ?? 'Erreur inconnue'));
            }

            $mainTransaction = $transactionResult['main_transaction'];
            $transactionDetail = $transactionResult['transaction_detail'];

            Log::info('Transaction complète créée avec toutes les parts lors de l\'approbation de réservation', [
                'transaction_id' => $mainTransaction->id,
                'reservation_id' => $reservation->id,
                'transaction_detail_id' => $transactionDetail->id,
                'admin_share' => $transactionDetail->admin_share_amount,
                'integrator_share' => $transactionDetail->integrator_share_amount,
                'operator_share' => $transactionDetail->operator_share_amount,
                'total_amount' => $mainTransaction->amount
            ]);

            // Enregistrer dans les logs avec tous les détails de répartition
            Log::info('Réservation approuvée avec toutes les parts créées', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $mainTransaction->id,
                'transaction_detail_id' => $transactionDetail->id,
                'base_cost' => $baseCost,
                'total_cost' => $totalCost,
                'admin_share' => $transactionDetail->admin_share_amount,
                'integrator_share' => $transactionDetail->integrator_share_amount,
                'operator_share' => $transactionDetail->operator_share_amount,
                'admin_percentage' => $transactionDetail->admin_share_percentage,
                'integrator_percentage' => $transactionDetail->integrator_share_percentage,
                'approved_by' => auth()->id(),
                'calculation' => $transactionResult['calculation'] ?? null,
                'hierarchy' => $transactionResult['hierarchy'] ?? null,
                'note' => 'Toutes les parts (Admin, Intégrateur, Opérateur) ont été créées avec succès'
            ]);

            // Synchroniser les balances pour tous les acteurs (Admin, Intégrateur, Opérateur)
            // Cela garantit que les balances reflètent correctement les parts sur les transactions approuvées
            try {
                $balanceSyncService = app(\App\Services\BalanceSynchronizationService::class);
                
                // Récupérer les utilisateurs depuis la hiérarchie
                $hierarchy = $transactionResult['hierarchy'] ?? null;
                
                if ($hierarchy) {
                    // Synchroniser la balance admin
                    // La hiérarchie retourne un objet User pour admin
                    if (isset($hierarchy['admin']) && $hierarchy['admin']) {
                        $adminUser = is_object($hierarchy['admin']) ? $hierarchy['admin'] : User::find($hierarchy['admin']);
                        if ($adminUser && $adminUser instanceof User) {
                            $balanceSyncService->synchronizeUserBalance($adminUser);
                            Log::info('Balance admin synchronisée après approbation de réservation', [
                                'admin_id' => $adminUser->id,
                                'transaction_id' => $mainTransaction->id
                            ]);
                        }
                    }
                    
                    // Synchroniser la balance intégrateur
                    // La hiérarchie retourne un objet Integrator, il faut récupérer son user
                    if (isset($hierarchy['integrator']) && $hierarchy['integrator']) {
                        $integrator = $hierarchy['integrator'];
                        $integratorUser = null;
                        
                        if (is_object($integrator)) {
                            // Si c'est un objet Integrator, récupérer son user
                            if (method_exists($integrator, 'user') && $integrator->user) {
                                $integratorUser = $integrator->user;
                            } elseif (isset($integrator->user_id)) {
                                $integratorUser = User::find($integrator->user_id);
                            } elseif (isset($integrator->created_by)) {
                                // Fallback: utiliser created_by si c'est un User
                                $potentialUser = User::find($integrator->created_by);
                                if ($potentialUser && $potentialUser->hasRole('integrator')) {
                                    $integratorUser = $potentialUser;
                                }
                            }
                        } else {
                            // Si c'est un ID, chercher l'intégrateur puis son user
                            $integratorObj = \App\Models\Integrator::find($integrator);
                            if ($integratorObj) {
                                if ($integratorObj->user) {
                                    $integratorUser = $integratorObj->user;
                                } elseif ($integratorObj->user_id) {
                                    $integratorUser = User::find($integratorObj->user_id);
                                }
                            }
                        }
                        
                        if ($integratorUser && $integratorUser instanceof User) {
                            $balanceSyncService->synchronizeUserBalance($integratorUser);
                            Log::info('Balance intégrateur synchronisée après approbation de réservation', [
                                'integrator_id' => $integratorUser->id,
                                'transaction_id' => $mainTransaction->id
                            ]);
                        }
                    }
                    
                    // Synchroniser la balance opérateur
                    // La hiérarchie retourne un objet User pour operator
                    if (isset($hierarchy['operator']) && $hierarchy['operator']) {
                        $operatorUser = is_object($hierarchy['operator']) ? $hierarchy['operator'] : User::find($hierarchy['operator']);
                        if ($operatorUser && $operatorUser instanceof User) {
                            $balanceSyncService->synchronizeUserBalance($operatorUser);
                            Log::info('Balance opérateur synchronisée après approbation de réservation', [
                                'operator_id' => $operatorUser->id,
                                'transaction_id' => $mainTransaction->id
                            ]);
                        }
                    }
                }
            } catch (\Exception $syncError) {
                // Ne pas faire échouer l'approbation si la synchronisation échoue
                // Mais logger l'erreur pour investigation
                Log::error('Erreur lors de la synchronisation des balances après approbation', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $mainTransaction->id,
                    'error' => $syncError->getMessage(),
                    'trace' => $syncError->getTraceAsString()
                ]);
            }

            DB::commit();

            return redirect()->route('admin.transactions.index')
                ->with('success', 'Réservation approuvée - Montant: ' . number_format($totalCost, 2) . ' EUR | Parts: Admin: ' . number_format($transactionDetail->admin_share_amount, 2) . ' EUR, Intégrateur: ' . number_format($transactionDetail->integrator_share_amount, 2) . ' EUR, Opérateur: ' . number_format($transactionDetail->operator_share_amount, 2) . ' EUR');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'approbation de la réservation: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Erreur lors de l\'approbation: ' . $e->getMessage());
        }
    }

    /**
     * Calcule le total de la réservation à partir des données de réservation
     */
    private function calculateReservationTotalFromData(Reservation $reservation): float
    {
        $costService = app(\App\Services\ReservationCostCalculationService::class);
        return $costService->calculateReservationCost($reservation);
    }

    /**
     * Calcule le coût à partir du plan tarifaire si les données de réservation sont insuffisantes
     */
    private function calculateCostFromPricingPlan(Reservation $reservation): float
    {
        $costService = app(\App\Services\ReservationCostCalculationService::class);
        return $costService->calculateCostWithDefaults($reservation, 1.0);
    }

    /**
     * Calcule le coût total d'une réservation
     */
    private function calculateReservationTotalCost(Reservation $reservation): float
    {
        $baseCost = $reservation->estimated_cost ?? 0;
        
        // Ajouter les frais d'activation si applicable
        $activationFee = $this->calculateActivationFee($reservation);
        
        return $baseCost + $activationFee;
    }

    /**
     * Calcule les frais d'activation pour une réservation
     */
    private function calculateActivationFee(Reservation $reservation): float
    {
        $chargingPoint = $reservation->chargingPoint;
        
        // Vérifier le business profile de la borne
        if ($chargingPoint->business_profile_id) {
            $businessProfile = BusinessProfile::find($chargingPoint->business_profile_id);
            if ($businessProfile && $businessProfile->base_fee_amount > 0) {
                return (float) $businessProfile->base_fee_amount;
            }
        }
        
        // Vérifier l'intégrateur
        if ($chargingPoint->integrator_id) {
            $integrator = Integrator::find($chargingPoint->integrator_id);
            if ($integrator && $integrator->businessProfile && $integrator->businessProfile->base_fee_amount > 0) {
                return (float) $integrator->businessProfile->base_fee_amount;
            }
        }
        
        // Vérifier le partenaire
        if ($chargingPoint->partner_id) {
            $partner = Partner::find($chargingPoint->partner_id);
            if ($partner && $partner->businessProfile && $partner->businessProfile->base_fee_amount > 0) {
                return (float) $partner->businessProfile->base_fee_amount;
            }
        }
        
        return 0.0;
    }

    /**
     * Calcule la répartition des revenus basée sur les frais du business profile
     */
    private function calculateRevenueBreakdown(Reservation $reservation, float $totalCost): array
    {
        $revenueDistributionService = app(\App\Services\RevenueDistributionCalculationService::class);
        return $revenueDistributionService->calculateRevenueDistribution($reservation, $totalCost);
    }



    /**
     * Crée une transaction de débit admin
     */
    public function createAdminDebit(Request $request)
    {
        $this->checkAdminAccess();

        $request->validate([
            'business_profile_id' => 'required|exists:business_profiles,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'transaction_category' => 'required|in:debit,integrator_debit,periodic_fee',
        ]);

        try {
            DB::beginTransaction();

            $transactionData = [
                'user_id' => auth()->id(),
                'charging_point_id' => null, // Pas de borne pour les débits admin
                'business_profile_id' => $request->business_profile_id,
                'transaction_type' => 'admin',
                'transaction_category' => $request->transaction_category,
                'price_total' => $request->amount,
                'amount' => $request->amount,
                'status' => 'completed',
                'payment_status' => 'paid',
                'currency' => 'EUR',
                'reason' => $request->description
            ];

            $transaction = $this->transactionService->create($transactionData);

            // Mettre à jour le solde du compte
            $this->updateAccountBalance($request->business_profile_id, -$request->amount);

            DB::commit();

            Log::info('Transaction de débit admin créée', [
                'transaction_id' => $transaction->id,
                'amount' => $request->amount,
                'category' => $request->transaction_category,
                'business_profile_id' => $request->business_profile_id
            ]);

            return redirect()->route('transactions.admin')
                ->with('success', 'Transaction de débit créée avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la transaction de débit: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors de la création de la transaction: ' . $e->getMessage());
        }
    }

    /**
     * Met à jour le solde d'un compte
     */
    private function updateAccountBalance(int $businessProfileId, float $amount): void
    {
        $businessProfile = BusinessProfile::find($businessProfileId);
        if ($businessProfile) {
            $account = $businessProfile->account()->firstOrCreate([
                'accountable_type' => BusinessProfile::class,
                'accountable_id' => $businessProfileId,
            ], [
                'balance' => 0,
                'currency' => 'EUR'
            ]);

            $account->increment('balance', $amount);
        }
    }

    /**
     * Exporte les transactions en CSV
     */
    public function export(Request $request)
    {
        Gate::authorize('export_admin_transactions');

        $filters = $request->only([
            'transaction_type',
            'transaction_category',
            'business_profile_id',
            'start_date',
            'end_date',
            'status'
        ]);

        $transactions = $this->transactionService->getComprehensiveAdminTransactions($filters);
        $transactions = $transactions->getCollection(); // Récupérer la collection sans pagination

        $filename = 'transactions_admin_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes CSV
            fputcsv($file, [
                'ID',
                'Type',
                'Catégorie',
                'Statut',
                'Montant Total',
                'Montant Base',
                'Frais d\'Activation',
                'Client',
                'Point de Recharge',
                'Business Profile',
                'Propriétaire',
                'Date de Création',
                'Description'
            ]);

            // Données
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->getTransactionTypeLabel(),
                    $transaction->getTransactionCategoryLabel(),
                    $transaction->getStatusLabel(),
                    number_format($transaction->price_total, 2) . ' EUR',
                    number_format($transaction->getBaseAmount(), 2) . ' EUR',
                    number_format($transaction->getActivationFeeAmount(), 2) . ' EUR',
                    $transaction->user->name ?? 'N/A',
                    $transaction->chargingPoint->name ?? 'N/A',
                    $transaction->businessProfile->name ?? 'N/A',
                    $transaction->getBusinessProfileOwnerTypeLabel(),
                    $transaction->created_at->format('d/m/Y H:i'),
                    $transaction->reason ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Affiche les statistiques détaillées
     */
    public function statistics(Request $request)
    {
        $this->checkAdminAccess();

        $filters = $request->only([
            'start_date',
            'end_date',
            'business_profile_id',
            'business_profile_owner_type'
        ]);

        $statistics = $this->calculateAdminStatistics(
            Transaction::query()->where(function($q) {
                $q->whereHas('reservation', function($resQuery) {
                    $resQuery->whereIn('status', ['confirmed', 'completed']);
                })->orWhereNull('reservation_id');
            })
        );
        $balanceSummary = $this->calculateBalanceSummary();

        return view('admin.transactions.statistics', compact('statistics', 'balanceSummary'));
    }

    /**
     * Affiche les frais d'activation
     */
    public function activationFees(Request $request)
    {
        $this->checkAdminAccess();

        $filters = $request->only([
            'business_profile_id',
            'start_date',
            'end_date'
        ]);

        $transactions = $this->transactionService->getActivationFeeTransactions($filters);
        $statistics = $this->transactionService->getComprehensiveAdminStatistics(array_merge($filters, ['transaction_type' => 'activation_fee']));
        
        // Données pour les filtres
        $businessProfiles = BusinessProfile::with(['integrator', 'partner'])->get();

        return view('admin.transactions.activation-fees', compact('transactions', 'statistics', 'businessProfiles'));
    }

    /**
     * Affiche les détails des frais des créateurs de bornes
     */
    public function chargingPointCreatorFees(Request $request)
    {
        $this->checkAdminAccess();

        try {
            $chargingPointId = $request->get('charging_point_id');
            $creatorType = $request->get('creator_type'); // 'integrator' ou 'partner'
            $creatorId = $request->get('creator_id');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $feeDetails = [];

            if ($chargingPointId) {
                // Détails pour une borne spécifique
                $chargingPoint = \App\Models\ChargingPoint::find($chargingPointId);
                if ($chargingPoint) {
                    try {
                        $feeDetails = $this->chargingPointFeeCalculationService->calculateChargingPointCreatorFees(
                            $chargingPoint, 
                            100 // Montant de base pour l'exemple
                        );
                    } catch (\Exception $e) {
                        Log::error('Erreur lors du calcul des frais pour la borne', [
                            'charging_point_id' => $chargingPointId,
                            'error' => $e->getMessage()
                        ]);
                        $feeDetails = [];
                    }
                }
            } elseif ($creatorType && $creatorId) {
                // Rapport pour un créateur sur une période
                try {
                    $feeDetails = $this->chargingPointFeeCalculationService->getFeeReportForPeriod(
                        $creatorId,
                        $creatorType,
                        $startDate ?? now()->subMonth(),
                        $endDate ?? now()
                    );
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la génération du rapport de frais', [
                        'creator_id' => $creatorId,
                        'creator_type' => $creatorType,
                        'error' => $e->getMessage()
                    ]);
                    $feeDetails = [];
                }
            }

            // Récupérer les listes pour les filtres
            $chargingPoints = \App\Models\ChargingPoint::with(['integrator', 'partner'])->get();
            $integrators = \App\Models\Integrator::with('businessProfile')->get();
            $partners = \App\Models\Partner::with('businessProfile')->get();

            return view('admin.transactions.charging-point-creator-fees', compact(
                'feeDetails',
                'chargingPoints',
                'integrators',
                'partners',
                'chargingPointId',
                'creatorType',
                'creatorId',
                'startDate',
                'endDate'
            ));

        } catch (\Exception $e) {
            Log::error('Erreur dans chargingPointCreatorFees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retourner une vue simple en cas d'erreur
            return view('admin.transactions.charging-point-creator-fees', [
                'feeDetails' => [],
                'chargingPoints' => [],
                'integrators' => [],
                'partners' => [],
                'chargingPointId' => null,
                'creatorType' => null,
                'creatorId' => null,
                'startDate' => null,
                'endDate' => null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calcule les frais pour une borne spécifique (API)
     */
    public function calculateFeesForChargingPoint(Request $request)
    {
        $this->checkAdminAccess();

        $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'base_amount' => 'nullable|numeric|min:0'
        ]);

        $chargingPoint = \App\Models\ChargingPoint::findOrFail($request->charging_point_id);
        $baseAmount = $request->get('base_amount', 0);

        $feeBreakdown = $this->chargingPointFeeCalculationService->calculateChargingPointCreatorFees(
            $chargingPoint, 
            $baseAmount
        );

        return response()->json([
            'success' => true,
            'data' => $feeBreakdown
        ]);
    }
}
