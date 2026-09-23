<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service de gestion de l'historique des transactions
 * 
 * Ce service gère l'enregistrement et l'affichage des transactions
 * avec des couleurs selon le type (débit/crédit) et filtre selon les rôles.
 */
class TransactionHistoryService
{
    /**
     * Enregistrer l'historique d'une transaction avec répartition
     *
     * @param Transaction $transaction
     * @param array $shares
     * @return bool
     */
    public function record(Transaction $transaction, array $shares): bool
    {
        try {
            DB::beginTransaction();

            // ---- ADMIN ----
            TransactionHistory::create([
                'transaction_id' => $transaction->id,
                'user_id' => 1, // super admin
                'role' => 'admin',
                'type' => 'credit',
                'amount' => $shares['admin'],
                'description' => 'Part admin sur la transaction #' . $transaction->id,
            ]);

            // ---- INTÉGRATEUR ----
            if ($transaction->chargingPoint && $transaction->chargingPoint->integrator) {
                $integrator = $transaction->chargingPoint->integrator;
                
                // Crédit pour l'intégrateur (sa part)
                TransactionHistory::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => $integrator->user_id,
                    'role' => 'integrator',
                    'type' => 'credit',
                    'amount' => $shares['integrator'],
                    'description' => 'Part intégrateur transaction #' . $transaction->id,
                ]);

                // Débit pour l'intégrateur (déduction part admin)
                TransactionHistory::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => $integrator->user_id,
                    'role' => 'integrator',
                    'type' => 'debit',
                    'amount' => $shares['admin'],
                    'description' => 'Déduction part admin transaction #' . $transaction->id,
                ]);
            }

            // ---- OPÉRATEUR ----
            if ($transaction->chargingPoint && $transaction->chargingPoint->user) {
                $operator = $transaction->chargingPoint->user;
                
                // Crédit pour l'opérateur (sa part)
                TransactionHistory::create([
                    'transaction_id' => $transaction->id,
                    'user_id' => $operator->user_id,
                    'role' => 'operator',
                    'type' => 'credit',
                    'amount' => $shares['operator'],
                    'description' => 'Gain opérateur transaction #' . $transaction->id,
                ]);
            }

            DB::commit();

            Log::info('Historique des transactions enregistré avec succès', [
                'transaction_id' => $transaction->id,
                'admin_share' => $shares['admin'],
                'integrator_share' => $shares['integrator'],
                'operator_share' => $shares['operator'],
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'enregistrement de l\'historique des transactions', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Obtenir l'historique des transactions pour un utilisateur
     *
     * @param User $user
     * @param array $filters
     * @return array
     */
    public function getTransactionHistory(User $user, array $filters = []): array
    {
        try {
            // Charger les relations nécessaires pour obtenir le montant collecté
            $query = TransactionHistory::with([
                'transaction' => function($q) {
                    $q->with(['reservation', 'chargingPoint']);
                },
                'user'
            ]);

            // Filtrer selon le rôle de l'utilisateur
            if ($user->hasRole('admin')) {
                // L'admin voit toutes les transactions
                // Pas de filtre supplémentaire
            } elseif ($user->hasRole('integrator')) {
                // L'intégrateur voit ses transactions
                $query->where('user_id', $user->id);
            } elseif ($user->hasRole('operator')) {
                // L'opérateur voit ses transactions
                $query->where('user_id', $user->id);
            } else {
                // Rôle non autorisé
                return [
                    'success' => false,
                    'message' => 'Rôle non autorisé pour voir l\'historique des transactions'
                ];
            }

            // Appliquer les filtres
            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            
            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
            
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }
            
            if (isset($filters['amount_min'])) {
                $query->where('amount', '>=', $filters['amount_min']);
            }
            
            if (isset($filters['amount_max'])) {
                $query->where('amount', '<=', $filters['amount_max']);
            }

            // Trier par date de création (plus récent en premier)
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $filters['per_page'] ?? 20;
            $histories = $query->paginate($perPage);

            // Traiter les transactions pour ajouter les couleurs et les détails
            $processedHistories = $this->processHistories($histories->items());

            return [
                'success' => true,
                'histories' => $processedHistories,
                'pagination' => [
                    'current_page' => $histories->currentPage(),
                    'last_page' => $histories->lastPage(),
                    'per_page' => $histories->perPage(),
                    'total' => $histories->total(),
                ],
                'summary' => $this->calculateSummary($processedHistories)
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des transactions', [
                'user_id' => $user->id,
                'user_role' => $user->getRoleNames(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique des transactions',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traiter les historiques pour ajouter les couleurs et les détails
     *
     * @param array $histories
     * @return array
     */
    private function processHistories(array $histories): array
    {
        $processed = [];

        foreach ($histories as $history) {
            $historyData = [
                'id' => $history->id,
                'transaction_id' => $history->transaction_id,
                'user_id' => $history->user_id,
                'user_name' => $history->user ? $history->user->name : 'Utilisateur inconnu',
                'user_email' => $history->user ? $history->user->email : 'N/A',
                'role' => $history->role,
                'role_label' => $history->role_label,
                'type' => $history->type,
                'type_label' => $history->type_label,
                'amount' => $history->amount,
                'formatted_amount' => $history->formatted_amount,
                'description' => $history->description,
                'created_at' => $history->created_at,
                'updated_at' => $history->updated_at,
                'color_class' => $history->color_class,
                'icon' => $history->icon,
                'is_credit' => $history->isCredit(),
                'is_debit' => $history->isDebit(),
            ];

            // Ajouter les informations de la transaction principale si disponible
            if ($history->transaction) {
                // Charger la réservation si elle existe
                if (!$history->transaction->relationLoaded('reservation')) {
                    $history->transaction->load('reservation');
                }
                
                // Montant réellement collecté = price_total ou estimated_cost de la réservation
                $collectedAmount = $history->transaction->price_total 
                    ?? ($history->transaction->reservation->estimated_cost ?? $history->transaction->amount ?? 0);
                
                $historyData['transaction'] = [
                    'id' => $history->transaction->id,
                    'amount' => $history->transaction->amount, // Montant original
                    'price_total' => $history->transaction->price_total, // Montant TTC collecté
                    'collected_amount' => $collectedAmount, // Montant réellement collecté (source de vérité)
                    'status' => $history->transaction->status,
                    'charging_point' => $history->transaction->chargingPoint ? [
                        'id' => $history->transaction->chargingPoint->id,
                        'name' => $history->transaction->chargingPoint->name,
                        'serial_number' => $history->transaction->chargingPoint->serial_number,
                    ] : null,
                    'reservation' => $history->transaction->reservation ? [
                        'id' => $history->transaction->reservation->id,
                        'estimated_cost' => $history->transaction->reservation->estimated_cost,
                    ] : null,
                ];
                
                // Pour l'affichage, utiliser le montant collecté si disponible
                // Sauf si c'est une TransactionHistory de type credit/debit (part intégrateur/admin)
                // Dans ce cas, on garde le montant de la TransactionHistory pour montrer la part
                // Mais on ajoute aussi le montant collecté pour référence
                if (!isset($historyData['collected_amount'])) {
                    $historyData['collected_amount'] = $collectedAmount;
                }
            }

            $processed[] = $historyData;
        }

        return $processed;
    }

    /**
     * Calculer le résumé des transactions
     *
     * @param array $histories
     * @return array
     */
    private function calculateSummary(array $histories): array
    {
        $summary = [
            'total_transactions' => count($histories),
            'total_credits' => 0,
            'total_debits' => 0,
            'net_balance' => 0,
            'by_type' => [],
            'by_role' => [],
        ];

        foreach ($histories as $history) {
            $amount = $history['amount'];
            
            if ($history['is_credit']) {
                $summary['total_credits'] += $amount;
            } elseif ($history['is_debit']) {
                $summary['total_debits'] += $amount;
            }

            // Compter par type
            $type = $history['type'];
            if (!isset($summary['by_type'][$type])) {
                $summary['by_type'][$type] = 0;
            }
            $summary['by_type'][$type]++;

            // Compter par rôle
            $role = $history['role'];
            if (!isset($summary['by_role'][$role])) {
                $summary['by_role'][$role] = 0;
            }
            $summary['by_role'][$role]++;
        }

        $summary['net_balance'] = $summary['total_credits'] - $summary['total_debits'];

        return $summary;
    }

    /**
     * Obtenir les statistiques des transactions pour un utilisateur
     *
     * @param User $user
     * @param string $period
     * @return array
     */
    public function getTransactionStats(User $user, string $period = 'month'): array
    {
        try {
            $query = TransactionHistory::query();

            // Filtrer selon le rôle
            if ($user->hasRole('admin')) {
                // L'admin voit toutes les transactions
            } elseif ($user->hasRole('integrator') || $user->hasRole('operator')) {
                $query->where('user_id', $user->id);
            }

            // Filtrer par période
            $startDate = match($period) {
                'day' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };

            $query->where('created_at', '>=', $startDate);

            $histories = $query->get();

            $stats = [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => now(),
                'total_transactions' => $histories->count(),
                'total_credits' => $histories->where('type', 'credit')->sum('amount'),
                'total_debits' => $histories->where('type', 'debit')->sum('amount'),
                'net_balance' => $histories->where('type', 'credit')->sum('amount') - $histories->where('type', 'debit')->sum('amount'),
                'by_type' => $histories->groupBy('type')->map->count(),
                'by_role' => $histories->groupBy('role')->map->count(),
            ];

            return [
                'success' => true,
                'stats' => $stats
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques des transactions', [
                'user_id' => $user->id,
                'period' => $period,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le solde net d'un utilisateur
     *
     * @param User $user
     * @return float
     */
    public function getUserBalance(User $user): float
    {
        $credits = TransactionHistory::where('user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');

        $debits = TransactionHistory::where('user_id', $user->id)
            ->where('type', 'debit')
            ->sum('amount');

        return $credits - $debits;
    }

    /**
     * Obtenir le solde par rôle pour un utilisateur
     *
     * @param User $user
     * @return array
     */
    public function getUserBalanceByRole(User $user): array
    {
        $balances = [];

        foreach (['admin', 'integrator', 'operator'] as $role) {
            $credits = TransactionHistory::where('user_id', $user->id)
                ->where('role', $role)
                ->where('type', 'credit')
                ->sum('amount');

            $debits = TransactionHistory::where('user_id', $user->id)
                ->where('role', $role)
                ->where('type', 'debit')
                ->sum('amount');

            $balances[$role] = [
                'credits' => $credits,
                'debits' => $debits,
                'net_balance' => $credits - $debits,
            ];
        }

        return $balances;
    }
}