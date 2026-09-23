<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Reservation;
use App\Services\HierarchicalTransactionService;
use Illuminate\Support\Facades\Log;

class AutoTransactionService
{
    protected $hierarchicalTransactionService;
    
    public function __construct(HierarchicalTransactionService $hierarchicalTransactionService)
    {
        $this->hierarchicalTransactionService = $hierarchicalTransactionService;
    }
    
    /**
     * Déclencher automatiquement les transactions hiérarchiques lors d'une réservation
     */
    public function processReservationTransaction(Reservation $reservation): array
    {
        try {
            Log::info('Début du traitement automatique des transactions hiérarchiques pour la réservation', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'charging_point_id' => $reservation->charging_point_id,
                'amount' => $reservation->total_amount
            ]);
            
            // Créer la transaction principale
            $transaction = $this->createMainTransaction($reservation);
            
            if (!$transaction) {
                throw new \Exception('Impossible de créer la transaction principale');
            }
            
            // Traiter la transaction avec la logique hiérarchique
            $result = $this->hierarchicalTransactionService->processTransaction($transaction);
            
            if (!$result['success']) {
                throw new \Exception('Erreur lors du traitement hiérarchique: ' . $result['error']);
            }
            
            Log::info('Transactions hiérarchiques créées avec succès', [
                'reservation_id' => $reservation->id,
                'main_transaction_id' => $transaction->id,
                'hierarchical_transactions_count' => count($result['hierarchical_transactions']),
                'admin_to_integrator_transaction_id' => $result['main_transactions']['admin_to_integrator']->id ?? null,
                'integrator_to_operator_transaction_id' => $result['main_transactions']['integrator_to_operator']->id ?? null
            ]);
            
            return [
                'success' => true,
                'reservation_id' => $reservation->id,
                'main_transaction' => $transaction,
                'hierarchical_result' => $result,
                'message' => 'Transactions hiérarchiques créées automatiquement'
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement automatique des transactions hiérarchiques', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id
            ];
        }
    }
    
    /**
     * Créer la transaction principale à partir d'une réservation
     */
    private function createMainTransaction(Reservation $reservation): ?Transaction
    {
        try {
            $transaction = new Transaction([
                'user_id' => $reservation->user_id,
                'charging_point_id' => $reservation->charging_point_id,
                'reservation_id' => $reservation->id,
                'pricing_plan_id' => $reservation->pricing_plan_id,
                'business_profile_id' => $reservation->chargingPoint->business_profile_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'amount' => $reservation->actual_cost ?? $reservation->estimated_cost ?? $reservation->amount ?? 0,
                'price_total' => $reservation->actual_cost ?? $reservation->estimated_cost ?? $reservation->amount ?? 0,
                'currency' => $reservation->currency ?? 'EUR',
                'status' => 'completed',
                'payment_status' => 'paid',
                'start_timestamp' => $reservation->start_time,
                'stop_timestamp' => $reservation->end_time,
                'energy_delivered' => $reservation->energy_delivered,
                'duration' => $reservation->duration_minutes,
                'description' => "Transaction automatique pour réservation #{$reservation->id}",
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'auto_generated' => true,
                    'generated_at' => now()->toISOString(),
                    'reservation_details' => [
                        'start_time' => $reservation->start_time,
                        'end_time' => $reservation->end_time,
                        'energy_delivered' => $reservation->energy_delivered,
                        'duration_minutes' => $reservation->duration_minutes
                    ]
                ]
            ]);
            
            $transaction->save();
            
            Log::info('Transaction principale créée automatiquement', [
                'transaction_id' => $transaction->id,
                'reservation_id' => $reservation->id,
                'amount' => $transaction->amount,
                'user_id' => $transaction->user_id,
                'charging_point_id' => $transaction->charging_point_id
            ]);
            
            return $transaction;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la transaction principale', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * Traiter toutes les réservations en attente
     */
    public function processPendingReservations(): array
    {
        $pendingReservations = Reservation::where('status', 'completed')
            ->whereDoesntHave('transactions')
            ->with(['user', 'chargingPoint', 'pricingPlan'])
            ->get();
        
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($pendingReservations as $reservation) {
            $result = $this->processReservationTransaction($reservation);
            $results[] = $result;
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        Log::info('Traitement des réservations en attente terminé', [
            'total_reservations' => $pendingReservations->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount
        ]);
        
        return [
            'total_processed' => $pendingReservations->count(),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'results' => $results
        ];
    }
    
    /**
     * Vérifier et corriger les transactions manquantes
     */
    public function checkAndFixMissingTransactions(): array
    {
        $completedReservationsWithoutTransactions = Reservation::where('status', 'completed')
            ->whereDoesntHave('transactions')
            ->count();
        
        $transactionsWithoutHierarchy = Transaction::where('transaction_type', 'client')
            ->whereDoesntHave('transactionHierarchies')
            ->count();
        
        $issues = [];
        
        if ($completedReservationsWithoutTransactions > 0) {
            $issues[] = [
                'type' => 'missing_transactions',
                'count' => $completedReservationsWithoutTransactions,
                'description' => 'Réservations terminées sans transactions'
            ];
        }
        
        if ($transactionsWithoutHierarchy > 0) {
            $issues[] = [
                'type' => 'missing_hierarchy',
                'count' => $transactionsWithoutHierarchy,
                'description' => 'Transactions client sans hiérarchie'
            ];
        }
        
        return [
            'issues_found' => count($issues),
            'issues' => $issues,
            'reservations_without_transactions' => $completedReservationsWithoutTransactions,
            'transactions_without_hierarchy' => $transactionsWithoutHierarchy
        ];
    }
}
