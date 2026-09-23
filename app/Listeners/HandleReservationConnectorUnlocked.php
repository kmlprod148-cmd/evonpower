<?php

namespace App\Listeners;

use App\Events\ReservationConnectorUnlocked;
use App\Models\Connector;
use Illuminate\Support\Facades\Log;

class HandleReservationConnectorUnlocked
{
    /**
     * Handle the event.
     */
    public function handle(ReservationConnectorUnlocked $event): void
    {
        $reservation = $event->reservation;
        $reason = $event->reason;

        Log::info('Listener: Connecteur déverrouillé pour réservation', [
            'reservation_id' => $reservation->id,
            'reason' => $reason,
        ]);

        // Mettre à jour le statut du connecteur
        try {
            if ($reservation->connector) {
                $reservation->connector->updateStatus(Connector::STATUS_AVAILABLE);
            }
        } catch (\Exception $e) {
            Log::error('Listener: Impossible de mettre à jour le statut du connecteur', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Si l'expiration était due à un timeout de paiement, notifier l'utilisateur
        if ($reason === 'payment_confirmation_timeout') {
            try {
                // TODO: $reservation->user->notify(new PaymentTimeoutNotification($reservation));
            } catch (\Exception $e) {
                Log::warning('Listener: Échec envoi notification timeout', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
