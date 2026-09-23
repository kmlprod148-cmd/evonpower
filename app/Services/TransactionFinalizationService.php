<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service centralisé pour la finalisation des transactions
 * Garantit que chaque transaction finalisée a une répartition détaillée enregistrée
 * 
 * Ce service peut être réutilisé partout où les transactions sont finalisées
 */
class TransactionFinalizationService
{
    protected $unifiedRevenueDistributionService;

    public function __construct(UnifiedRevenueDistributionService $unifiedRevenueDistributionService)
    {
        $this->unifiedRevenueDistributionService = $unifiedRevenueDistributionService;
    }

    /**
     * Finalise une transaction en créant/mettant à jour sa répartition
     * 
     * @param Transaction $transaction
     * @param array $options Options supplémentaires (force_update, skip_validation, etc.)
     * @return TransactionRepartition
     * @throws \Exception
     */
    public function finalizeTransaction(Transaction $transaction, array $options = []): TransactionRepartition
    {
        try {
            DB::beginTransaction();

            // Vérifier que la transaction est dans un état valide
            if (!$this->isTransactionFinalizable($transaction)) {
                throw new \Exception("La transaction #{$transaction->id} n'est pas dans un état finalisable. Statut: {$transaction->status}");
            }

            // Vérifier si une répartition existe déjà
            $existingRepartition = $this->getExistingRepartition($transaction);

            // Si une répartition existe et qu'on ne force pas la mise à jour, retourner l'existante
            if ($existingRepartition && !($options['force_update'] ?? false)) {
                Log::info('Répartition déjà existante pour la transaction', [
                    'transaction_id' => $transaction->id,
                    'repartition_id' => $existingRepartition->id
                ]);
                
                DB::commit();
                return $existingRepartition;
            }

            // Calculer la répartition correcte
            $distribution = $this->unifiedRevenueDistributionService->calculateCorrectRevenueDistribution($transaction);

            // Créer ou mettre à jour la répartition
            if ($existingRepartition) {
                // Mettre à jour la répartition existante
                // Ne pas inclure total_amount car la colonne n'existe pas dans la base de données
                $existingRepartition->update([
                    'admin_amount' => $distribution['admin'],
                    'integrator_amount' => $distribution['integrator'],
                    'operator_amount' => $distribution['operator'] ?? 0,
                    // 'total_amount' retiré - calculé dynamiquement via getTotalAmount()
                    'integrator_fee' => $distribution['fees_breakdown']['integrator_amount'] ?? 0,
                    'admin_fee' => $distribution['fees_breakdown']['admin_amount'] ?? 0,
                    'updated_at' => now()
                ]);

                $repartition = $existingRepartition;
                
                Log::info('Répartition mise à jour pour la transaction', [
                    'transaction_id' => $transaction->id,
                    'repartition_id' => $repartition->id,
                    'distribution' => $distribution
                ]);
            } else {
                // Créer une nouvelle répartition
                // Ne pas inclure total_amount car la colonne n'existe pas dans la base de données
                $repartition = TransactionRepartition::create([
                    'transaction_id' => $transaction->id,
                    'admin_amount' => $distribution['admin'],
                    'integrator_amount' => $distribution['integrator'],
                    'operator_amount' => $distribution['operator'] ?? 0,
                    // 'total_amount' retiré - calculé dynamiquement via getTotalAmount()
                    'integrator_fee' => $distribution['fees_breakdown']['integrator_amount'] ?? 0,
                    'admin_fee' => $distribution['fees_breakdown']['admin_amount'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info('Nouvelle répartition créée pour la transaction', [
                    'transaction_id' => $transaction->id,
                    'repartition_id' => $repartition->id,
                    'distribution' => $distribution
                ]);
            }

            // Valider la cohérence si demandé
            if (!($options['skip_validation'] ?? false)) {
                $this->validateRepartition($repartition, $transaction);
            }

            DB::commit();

            return $repartition;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la finalisation de la transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Vérifie si une transaction peut être finalisée
     * 
     * @param Transaction $transaction
     * @return bool
     */
    protected function isTransactionFinalizable(Transaction $transaction): bool
    {
        // Transactions qui peuvent être finalisées
        $finalizableStatuses = ['completed', 'confirmed', 'paid'];
        
        return in_array($transaction->status, $finalizableStatuses);
    }

    /**
     * Récupère la répartition existante pour une transaction
     * 
     * @param Transaction $transaction
     * @return TransactionRepartition|null
     */
    protected function getExistingRepartition(Transaction $transaction): ?TransactionRepartition
    {
        // Essayer d'abord avec la relation repartition (HasOne)
        if ($transaction->relationLoaded('repartition') && $transaction->repartition) {
            return $transaction->repartition;
        }
        
        // Requête directe si les relations ne sont pas chargées
        return TransactionRepartition::where('transaction_id', $transaction->id)->first();
    }

    /**
     * Valide que la répartition est cohérente avec la transaction
     * 
     * @param TransactionRepartition $repartition
     * @param Transaction $transaction
     * @return void
     * @throws \Exception
     */
    protected function validateRepartition(TransactionRepartition $repartition, Transaction $transaction): void
    {
        $transactionAmount = $transaction->price_total ?? $transaction->amount ?? 0;
        $repartitionTotal = $repartition->admin_amount + $repartition->integrator_amount + $repartition->operator_amount;
        
        $difference = abs($transactionAmount - $repartitionTotal);
        
        // Tolérance de 0.01€ pour les erreurs d'arrondi
        if ($difference > 0.01) {
            Log::warning('Répartition potentiellement incohérente détectée', [
                'transaction_id' => $transaction->id,
                'transaction_amount' => $transactionAmount,
                'repartition_total' => $repartitionTotal,
                'difference' => $difference
            ]);
            
            // On ne lance pas d'exception par défaut pour éviter de bloquer les transactions
            // mais on log un avertissement
        }
    }

    /**
     * Finalise plusieurs transactions en batch
     * 
     * @param \Illuminate\Database\Eloquent\Collection|array $transactions
     * @param array $options
     * @return array Résultats avec succès/échecs
     */
    public function finalizeTransactions($transactions, array $options = []): array
    {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        foreach ($transactions as $transaction) {
            $results['total']++;
            
            try {
                // Vérifier si une répartition existe déjà
                $existingRepartition = $this->getExistingRepartition($transaction);
                
                if ($existingRepartition && !($options['force_update'] ?? false)) {
                    $results['skipped']++;
                    $results['details'][] = [
                        'transaction_id' => $transaction->id,
                        'status' => 'skipped',
                        'reason' => 'Répartition déjà existante'
                    ];
                    continue;
                }

                $repartition = $this->finalizeTransaction($transaction, $options);
                
                $results['success']++;
                $results['details'][] = [
                    'transaction_id' => $transaction->id,
                    'status' => 'success',
                    'repartition_id' => $repartition->id
                ];
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'transaction_id' => $transaction->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Finalisation en batch terminée', $results);

        return $results;
    }

    /**
     * Vérifie et finalise toutes les transactions finalisées qui n'ont pas de répartition
     * 
     * @param array $options
     * @return array Résultats
     */
    public function finalizeAllMissingRepartitions(array $options = []): array
    {
        $finalizableStatuses = ['completed', 'confirmed', 'paid'];
        
        // Récupérer toutes les transactions finalisées sans répartition
        $transactionsWithoutRepartition = Transaction::whereIn('status', $finalizableStatuses)
            ->whereDoesntHave('repartition')
            ->get();

        Log::info('Transactions sans répartition trouvées', [
            'count' => $transactionsWithoutRepartition->count()
        ]);

        return $this->finalizeTransactions($transactionsWithoutRepartition, $options);
    }
}

