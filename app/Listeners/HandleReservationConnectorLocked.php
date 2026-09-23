<?php

namespace App\Listeners;

use App\Events\ReservationConnectorLocked;
use App\Models\Connector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HandleReservationConnectorLocked
{
    /**
     * Handle the event.
     */
    public function handle(ReservationConnectorLocked $event): void
    {
        $reservation = $event->reservation;

        Log::info('Listener: Connecteur verrouillé pour réservation', [
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'connector_id' => $reservation->connector_id,
        ]);

        // Mettre à jour le statut du connecteur
        try {
            if ($reservation->connector) {
                $reservation->connector->updateStatus(Connector::STATUS_RESERVED);
            }
        } catch (\Exception $e) {
            Log::error('Listener: Impossible de mettre à jour le statut du connecteur', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Envoyer notification à l'utilisateur
        try {
            // TODO: $reservation->user->notify(new ConnectorLockedNotification($reservation));
        } catch (\Exception $e) {
            Log::warning('Listener: Échec envoi notification verrouillage', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
