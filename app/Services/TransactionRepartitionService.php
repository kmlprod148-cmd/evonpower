<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionRepartitionService
{
    protected $transactionCalculator;

    public function __construct(TransactionCalculator $transactionCalculator)
    {
        $this->transactionCalculator = $transactionCalculator;
    }

    /**
     * Crée automatiquement la répartition pour une transaction
     * 
     * @param Transaction $transaction
     * @return TransactionRepartition
     */
    public function createRepartition(Transaction $transaction): TransactionRepartition
    {
        try {
            DB::beginTransaction();

            // Vérifier si une répartition existe déjà
            $existingRepartition = $transaction->repartitions()->first();
            if ($existingRepartition) {
                Log::info('Répartition déjà existante pour la transaction', ['transaction_id' => $transaction->id]);
                return $existingRepartition;
            }

            // Calculer la répartition
            $calculation = $this->transactionCalculator->calculate($transaction);

            // Créer la répartition
            $repartition = TransactionRepartition::create([
                'transaction_id' => $transaction->id,
                'admin_amount' => $calculation['admin'],
                'integrator_amount' => $calculation['integrator'],
                'operator_amount' => $calculation['operator'],
            ]);

            // Valider la cohérence
            if (!$repartition->isConsistent()) {
                Log::warning('Répartition incohérente créée', [
                    'transaction_id' => $transaction->id,
                    'transaction_amount' => $transaction->amount,
                    'repartition_total' => $repartition->getTotalAmount(),
                ]);
            }

            DB::commit();

            Log::info('Répartition créée avec succès', [
                'transaction_id' => $transaction->id,
                'admin_amount' => $calculation['admin'],
                'integrator_amount' => $calculation['integrator'],
                'operator_amount' => $calculation['operator'],
            ]);

            return $repartition;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de la répartition', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Met à jour la répartition d'une transaction
     * 
     * @param Transaction $transaction
     * @return TransactionRepartition
     */
    public function updateRepartition(Transaction $transaction): TransactionRepartition
    {
        try {
            DB::beginTransaction();

            // Recalculer la répartition
            $calculation = $this->transactionCalculator->calculate($transaction);

            // Trouver ou créer la répartition
            $repartition = $transaction->repartitions()->first();
            if (!$repartition) {
                $repartition = new TransactionRepartition();
                $repartition->transaction_id = $transaction->id;
            }

            // Mettre à jour les montants
            $repartition->admin_amount = $calculation['admin'];
            $repartition->integrator_amount = $calculation['integrator'];
            $repartition->operator_amount = $calculation['operator'];
            $repartition->save();

            DB::commit();

            Log::info('Répartition mise à jour avec succès', [
                'transaction_id' => $transaction->id,
                'admin_amount' => $calculation['admin'],
                'integrator_amount' => $calculation['integrator'],
                'operator_amount' => $calculation['operator'],
            ]);

            return $repartition;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de la répartition', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Crée les répartitions pour toutes les transactions existantes qui n'en ont pas
     * 
     * @return int Nombre de répartitions créées
     */
    public function createMissingRepartitions(): int
    {
        $transactionsWithoutRepartition = Transaction::whereDoesntHave('repartitions')->get();
        $createdCount = 0;

        foreach ($transactionsWithoutRepartition as $transaction) {
            try {
                $this->createRepartition($transaction);
                $createdCount++;
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de la répartition pour la transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Répartitions manquantes créées', ['count' => $createdCount]);
        return $createdCount;
    }

    /**
     * Valide toutes les répartitions existantes
     * 
     * @return array Résultats de validation
     */
    public function validateAllRepartitions(): array
    {
        $repartitions = TransactionRepartition::with('transaction')->get();
        $results = [
            'total' => $repartitions->count(),
            'consistent' => 0,
            'inconsistent' => 0,
            'inconsistent_details' => [],
        ];

        foreach ($repartitions as $repartition) {
            if ($repartition->isConsistent()) {
                $results['consistent']++;
            } else {
                $results['inconsistent']++;
                $results['inconsistent_details'][] = [
                    'transaction_id' => $repartition->transaction_id,
                    'transaction_amount' => $repartition->transaction->amount,
                    'repartition_total' => $repartition->getTotalAmount(),
                    'difference' => abs($repartition->transaction->amount - $repartition->getTotalAmount()),
                ];
            }
        }

        return $results;
    }

    /**
     * Obtient les statistiques de répartition pour un utilisateur
     * 
     * @param \App\Models\User $user
     * @param string $period 'day', 'week', 'month', 'year'
     * @return array
     */
    public function getUserRepartitionStats($user, string $period = 'month'): array
    {
        $role = $user->getRoleNames()->first();
        
        // Définir la période
        $startDate = now()->startOfDay();
        switch ($period) {
            case 'day':
                $startDate = now()->startOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                break;
        }

        // Construire la requête selon le rôle
        $query = TransactionRepartition::whereHas('transaction', function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        });

        if ($role === 'integrator') {
            $query->whereHas('transaction.chargingPoint', function ($q) use ($user) {
                $q->where('integrator_id', $user->id);
            });
        } elseif ($role === 'operator') {
            $query->whereHas('transaction.chargingPoint', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $repartitions = $query->get();

        $totalAmount = $repartitions->sum(function ($repartition) use ($role) {
            return $repartition->getAmountForRole($role);
        });

        $creditCount = $repartitions->filter(function ($repartition) use ($role) {
            return $repartition->getAmountForRole($role) > 0;
        })->count();

        $debitCount = $repartitions->filter(function ($repartition) use ($role) {
            return $repartition->getAmountForRole($role) < 0;
        })->count();

        return [
            'period' => $period,
            'role' => $role,
            'total_amount' => $totalAmount,
            'credit_count' => $creditCount,
            'debit_count' => $debitCount,
            'total_transactions' => $repartitions->count(),
            'average_amount' => $repartitions->count() > 0 ? $totalAmount / $repartitions->count() : 0,
        ];
    }
}