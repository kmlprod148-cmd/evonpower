<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionNotificationService;
use App\Services\TransactionHistoryCacheService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionBulkController extends Controller
{
    protected $notificationService;
    protected $cacheService;

    public function __construct(
        TransactionNotificationService $notificationService,
        TransactionHistoryCacheService $cacheService
    ) {
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
    }

    /**
     * Applique des actions en lot sur les transactions sélectionnées
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:mark_completed,mark_cancelled,mark_failed,export_selected,delete_selected',
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'integer|exists:transactions,id'
        ]);

        try {
            $action = $request->action;
            $transactionIds = $request->transaction_ids;
            $user = Auth::user();

            // Vérifier les permissions pour chaque transaction
            $transactions = $this->getAuthorizedTransactions($transactionIds, $user);

            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune transaction autorisée trouvée'
                ], 403);
            }

            $results = [];

            switch ($action) {
                case 'mark_completed':
                    $results = $this->markTransactionsAsCompleted($transactions);
                    break;
                case 'mark_cancelled':
                    $results = $this->markTransactionsAsCancelled($transactions);
                    break;
                case 'mark_failed':
                    $results = $this->markTransactionsAsFailed($transactions);
                    break;
                case 'export_selected':
                    return $this->exportSelectedTransactions($transactions);
                case 'delete_selected':
                    $results = $this->deleteSelectedTransactions($transactions);
                    break;
            }

            // Invalider le cache
            $this->cacheService->invalidateGlobalCache();

            return response()->json([
                'success' => true,
                'message' => 'Action en lot exécutée avec succès',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'action en lot: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de l\'action en lot'
            ], 500);
        }
    }

    /**
     * Récupère les transactions autorisées pour l'utilisateur
     */
    private function getAuthorizedTransactions(array $transactionIds, User $user)
    {
        $query = Transaction::whereIn('id', $transactionIds);

        // Appliquer les permissions selon les rôles
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return $query->get();
        } elseif ($user->hasRole('integrator')) {
            return $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('user', function($userQuery) use ($user) {
                      $userQuery->where('created_by', $user->id);
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                      $cpQuery->where('integrator_id', $user->integrator_id);
                  });
            })->get();
        } elseif ($user->hasRole('operator')) {
            return $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('user', function($userQuery) use ($user) {
                      $userQuery->where('created_by', $user->id);
                  })
                  ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                      $cpQuery->where('user_id', $user->id);
                  });
            })->get();
        } else {
            return $query->where('user_id', $user->id)->get();
        }
    }

    /**
     * Marque les transactions comme terminées
     */
    private function markTransactionsAsCompleted($transactions): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::beginTransaction();
        try {
            $transactionFinalizationService = app(\App\Services\TransactionFinalizationService::class);
            
            foreach ($transactions as $transaction) {
                try {
                    $transaction->update([
                        'status' => 'completed',
                        'payment_status' => 'paid',
                        'updated_at' => now()
                    ]);

                    // Finaliser la transaction en créant/mettant à jour la répartition
                    try {
                        $repartition = $transactionFinalizationService->finalizeTransaction($transaction);
                        \Log::info('Répartition créée lors du marquage en batch', [
                            'transaction_id' => $transaction->id,
                            'repartition_id' => $repartition->id
                        ]);
                    } catch (\Exception $repartitionError) {
                        \Log::warning('Impossible de créer la répartition pour la transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $repartitionError->getMessage()
                        ]);
                        // On ne bloque pas le processus si la répartition échoue
                    }

                    $this->notificationService->notifyTransactionUpdated($transaction, ['status' => 'completed']);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Transaction #{$transaction->id}: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Marque les transactions comme annulées
     */
    private function markTransactionsAsCancelled($transactions): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                try {
                    $transaction->update([
                        'status' => 'cancelled',
                        'payment_status' => 'refunded',
                        'updated_at' => now()
                    ]);

                    $this->notificationService->notifyTransactionUpdated($transaction, ['status' => 'cancelled']);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Transaction #{$transaction->id}: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Marque les transactions comme échouées
     */
    private function markTransactionsAsFailed($transactions): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                try {
                    $transaction->update([
                        'status' => 'failed',
                        'payment_status' => 'failed',
                        'updated_at' => now()
                    ]);

                    $this->notificationService->notifyTransactionUpdated($transaction, ['status' => 'failed']);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Transaction #{$transaction->id}: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Supprime les transactions sélectionnées
     */
    private function deleteSelectedTransactions($transactions): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                try {
                    // Vérifier si la transaction peut être supprimée
                    if ($this->canDeleteTransaction($transaction)) {
                        $transactionId = $transaction->id;
                        $transaction->delete();
                        
                        $this->notificationService->notifyTransactionDeleted($transaction);
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Transaction #{$transaction->id}: Ne peut pas être supprimée";
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Transaction #{$transaction->id}: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Exporte les transactions sélectionnées
     */
    private function exportSelectedTransactions($transactions)
    {
        $filename = 'transactions_selection_' . date('Y-m-d_H-i-s') . '.csv';
        
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
                'ID Transaction',
                'Type',
                'Catégorie',
                'Statut',
                'Montant',
                'Client',
                'Point de Recharge',
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
                    $transaction->user->name ?? 'N/A',
                    $transaction->chargingPoint->name ?? 'N/A',
                    $transaction->created_at->format('d/m/Y H:i'),
                    $transaction->reason ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Vérifie si une transaction peut être supprimée
     */
    private function canDeleteTransaction(Transaction $transaction): bool
    {
        // Ne pas supprimer les transactions terminées ou payées
        if ($transaction->status === 'completed' || $transaction->payment_status === 'paid') {
            return false;
        }

        // Ne pas supprimer les transactions récentes (moins de 24h)
        if ($transaction->created_at->isAfter(now()->subDay())) {
            return false;
        }

        return true;
    }

    /**
     * Récupère les statistiques pour les transactions sélectionnées
     */
    public function getBulkStatistics(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'integer|exists:transactions,id'
        ]);

        try {
            $user = Auth::user();
            $transactions = $this->getAuthorizedTransactions($request->transaction_ids, $user);

            if ($transactions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune transaction autorisée trouvée'
                ], 403);
            }

            $statistics = [
                'total_count' => $transactions->count(),
                'total_amount' => $transactions->sum('price_total'),
                'by_status' => $transactions->groupBy('status')->map->count(),
                'by_type' => $transactions->groupBy('transaction_type')->map->count(),
                'by_category' => $transactions->groupBy('transaction_category')->map->count(),
                'average_amount' => $transactions->avg('price_total'),
                'min_amount' => $transactions->min('price_total'),
                'max_amount' => $transactions->max('price_total'),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des statistiques en lot: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }
}
