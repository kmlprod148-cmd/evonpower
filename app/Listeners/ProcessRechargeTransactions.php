<?php

namespace App\Listeners;

use App\Events\RechargeRequested;
use App\Services\ReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessRechargeTransactions implements ShouldQueue
{
    use InteractsWithQueue;

    protected ReservationService $reservationService;

    /**
     * Create the event listener.
     */
    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Handle the event.
     */
    public function handle(RechargeRequested $event): void
    {
        try {
            Log::info('Traitement des transactions pour la recharge', [
                'operator_id' => $event->operator->id,
                'recharge_data' => $event->rechargeData
            ]);

            // Préparer les données pour le service
            $rechargeData = array_merge([
                'operator_id' => $event->operator->id,
                'amount' => $event->rechargeData['amount'] ?? 0,
                'payment_status' => $event->rechargeData['payment_status'] ?? 'pending',
                'description' => $event->rechargeData['description'] ?? 'Recharge de compte',
                'currency' => $event->rechargeData['currency'] ?? 'EUR',
                'recharge_type' => $event->rechargeData['recharge_type'] ?? 'manual',
                'location' => $event->rechargeData['location'] ?? null,
                'charging_point_id' => $event->rechargeData['charging_point_id'] ?? null,
                'external_reference' => $event->rechargeData['external_reference'] ?? null,
                'metadata' => $event->rechargeData
            ], $event->rechargeData);

            // Traiter la recharge
            $result = $this->reservationService->processRecharge($rechargeData);

            Log::info('Transaction de recharge créée avec succès', [
                'operator_id' => $event->operator->id,
                'transaction_id' => $result['transaction']->id,
                'success' => $result['success']
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la transaction de recharge', [
                'operator_id' => $event->operator->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
