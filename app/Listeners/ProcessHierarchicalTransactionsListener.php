<?php

namespace App\Listeners;

use App\Events\ReservationCompleted;
use App\Services\AutoTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessHierarchicalTransactionsListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $autoTransactionService;

    /**
     * Create the event listener.
     */
    public function __construct(AutoTransactionService $autoTransactionService)
    {
        $this->autoTransactionService = $autoTransactionService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationCompleted $event): void
    {
        $reservation = $event->reservation;
        
        Log::info('Événement ReservationCompleted déclenché - Traitement automatique des transactions hiérarchiques', [
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'charging_point_id' => $reservation->charging_point_id,
            'amount' => $reservation->total_amount
        ]);
        
        try {
            $result = $this->autoTransactionService->processReservationTransaction($reservation);
            
            if ($result['success']) {
                Log::info('Transactions hiérarchiques créées automatiquement avec succès', [
                    'reservation_id' => $reservation->id,
                    'main_transaction_id' => $result['main_transaction']->id,
                    'hierarchical_transactions_count' => count($result['hierarchical_result']['hierarchical_transactions'])
                ]);
            } else {
                Log::error('Échec du traitement automatique des transactions hiérarchiques', [
                    'reservation_id' => $reservation->id,
                    'error' => $result['error']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Exception lors du traitement automatique des transactions hiérarchiques', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ReservationCompleted $event, $exception): void
    {
        Log::error('Échec du listener ProcessHierarchicalTransactionsListener', [
            'reservation_id' => $event->reservation->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
