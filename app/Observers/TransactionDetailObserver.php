<?php

namespace App\Observers;

use App\Models\TransactionDetail;
use App\Services\BalanceSynchronizationService;
use Illuminate\Support\Facades\Log;

class TransactionDetailObserver
{
    /**
     * Handle the TransactionDetail "created" event.
     * 
     * Synchronise automatiquement les balances après création d'un TransactionDetail
     */
    public function created(TransactionDetail $transactionDetail): void
    {
        $this->synchronizeBalances($transactionDetail, 'created');
    }

    /**
     * Handle the TransactionDetail "updated" event.
     * 
     * Synchronise automatiquement les balances après mise à jour d'un TransactionDetail
     * Surtout important si admin_share_amount, integrator_share_amount ou operator_share_amount changent
     */
    public function updated(TransactionDetail $transactionDetail): void
    {
        // Vérifier si les montants de parts ont changé
        $relevantFields = ['admin_share_amount', 'integrator_share_amount', 'operator_share_amount'];
        $hasRelevantChanges = $transactionDetail->wasChanged($relevantFields);
        
        if ($hasRelevantChanges) {
            Log::info('TransactionDetail mis à jour avec changement de parts - Synchronisation automatique', [
                'transaction_detail_id' => $transactionDetail->id,
                'transaction_id' => $transactionDetail->transaction_id,
                'changed_fields' => array_filter($relevantFields, function($field) use ($transactionDetail) {
                    return $transactionDetail->wasChanged($field);
                })
            ]);
            
            $this->synchronizeBalances($transactionDetail, 'updated');
        }
    }

    /**
     * Synchroniser les balances après création/mise à jour d'un TransactionDetail
     * 
     * @param TransactionDetail $transactionDetail
     * @param string $event
     * @return void
     */
    protected function synchronizeBalances(TransactionDetail $transactionDetail, string $event): void
    {
        try {
            $transaction = $transactionDetail->transaction;
            if (!$transaction) {
                return;
            }

            $balanceSyncService = app(BalanceSynchronizationService::class);

            // CORRECTION : Synchroniser automatiquement le solde admin
            // Peu importe si admin_share_amount > 0, on synchronise toujours pour garantir la cohérence
            // Utiliser plusieurs méthodes de fallback pour garantir la synchronisation
            
            $adminSynchronized = false;
            
            // MÉTHODE 1 : Via admin_creator_id si défini
            if ($transactionDetail->admin_creator_id) {
                $adminUser = \App\Models\User::find($transactionDetail->admin_creator_id);
                if ($adminUser && $adminUser->hasRole(['admin', 'super_admin'])) {
                    try {
                        $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                        Log::info('Balance admin synchronisée automatiquement après TransactionDetail ' . $event, [
                            'transaction_detail_id' => $transactionDetail->id,
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'admin_share_amount' => $transactionDetail->admin_share_amount ?? 0,
                            'event' => $event,
                            'sync_result' => $adminSyncResult
                        ]);
                        $adminSynchronized = true;
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin via admin_creator_id (TransactionDetailObserver)', [
                            'transaction_detail_id' => $transactionDetail->id,
                            'admin_user_id' => $adminUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // MÉTHODE 2 : Recherche globale si admin_share_amount > 0 et pas encore synchronisé
            if (!$adminSynchronized && ($transactionDetail->admin_share_amount ?? 0) > 0) {
                $adminUsers = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin']);
                })->get();
                
                foreach ($adminUsers as $adminUser) {
                    try {
                        $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                        Log::info('Balance admin synchronisée automatiquement après TransactionDetail ' . $event . ' (recherche globale)', [
                            'transaction_detail_id' => $transactionDetail->id,
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'admin_share_amount' => $transactionDetail->admin_share_amount ?? 0,
                            'event' => $event,
                            'sync_result' => $adminSyncResult
                        ]);
                        $adminSynchronized = true;
                        // Synchroniser seulement le premier admin trouvé (généralement il n'y en a qu'un)
                        break;
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin via recherche globale (TransactionDetailObserver)', [
                            'transaction_detail_id' => $transactionDetail->id,
                            'admin_user_id' => $adminUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Synchroniser l'intégrateur si nécessaire
            if (($transactionDetail->integrator_share_amount ?? 0) > 0 || ($transactionDetail->admin_share_amount ?? 0) > 0) {
                $integratorSynchronized = false;
                
                // MÉTHODE 1 : Via integrator_creator_id
                if ($transactionDetail->integrator_creator_id) {
                    $integratorUser = \App\Models\User::find($transactionDetail->integrator_creator_id);
                    // Vérifier aussi via le modèle Integrator
                    if (!$integratorUser) {
                        $integratorModel = \App\Models\Integrator::find($transactionDetail->integrator_creator_id);
                        if ($integratorModel && $integratorModel->user) {
                            $integratorUser = $integratorModel->user;
                        }
                    }
                    
                    if ($integratorUser && $integratorUser->hasRole('integrator')) {
                        try {
                            $integratorSyncResult = $balanceSyncService->synchronizeUserBalance($integratorUser);
                            Log::info('Balance intégrateur synchronisée automatiquement après TransactionDetail ' . $event, [
                                'transaction_detail_id' => $transactionDetail->id,
                                'transaction_id' => $transaction->id,
                                'integrator_user_id' => $integratorUser->id,
                                'integrator_share_amount' => $transactionDetail->integrator_share_amount ?? 0,
                                'event' => $event,
                                'sync_result' => $integratorSyncResult
                            ]);
                            $integratorSynchronized = true;
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation intégrateur (TransactionDetailObserver)', [
                                'transaction_detail_id' => $transactionDetail->id,
                                'integrator_user_id' => $integratorUser->id ?? null,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // Synchroniser l'opérateur si nécessaire
            if (($transactionDetail->operator_share_amount ?? 0) > 0) {
                if ($transactionDetail->operator_id) {
                    $operatorUser = \App\Models\User::find($transactionDetail->operator_id);
                    if ($operatorUser && $operatorUser->hasRole(['operator', 'partner'])) {
                        try {
                            $operatorSyncResult = $balanceSyncService->synchronizeUserBalance($operatorUser);
                            Log::info('Balance opérateur synchronisée automatiquement après TransactionDetail ' . $event, [
                                'transaction_detail_id' => $transactionDetail->id,
                                'transaction_id' => $transaction->id,
                                'operator_user_id' => $operatorUser->id,
                                'operator_share_amount' => $transactionDetail->operator_share_amount ?? 0,
                                'event' => $event,
                                'sync_result' => $operatorSyncResult
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation opérateur (TransactionDetailObserver)', [
                                'transaction_detail_id' => $transactionDetail->id,
                                'operator_user_id' => $operatorUser->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation automatique des balances (TransactionDetailObserver)', [
                'transaction_detail_id' => $transactionDetail->id,
                'transaction_id' => $transactionDetail->transaction_id ?? null,
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

