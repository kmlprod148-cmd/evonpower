<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Services\ReservationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessReservationTransactions implements ShouldQueue
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
    public function handle(ReservationCreated $event): void
    {
        try {
            Log::info('Traitement des transactions pour la réservation', [
                'reservation_id' => $event->reservation->id,
                'reservation_data' => $event->reservationData
            ]);

            // Préparer les données pour le service
            $reservationData = array_merge([
                'reservation_id' => $event->reservation->id,
                'amount' => $event->reservation->amount ?? 0,
                'operator_id' => $event->reservation->user_id ?? null,
                'charging_point_id' => $event->reservation->charging_point_id ?? null,
                'payment_status' => $this->determinePaymentStatus($event->reservation),
                'description' => 'Réservation de point de charge',
                'currency' => 'EUR',
                'location' => $this->extractLocation($event->reservation),
                'session_duration' => $event->reservation->duration ?? null,
                'energy_delivered' => $event->reservation->energy_delivered ?? null,
                'payment_method' => $event->reservation->payment_method ?? null,
                'payment_reference' => $event->reservation->payment_reference ?? null,
                'external_reference' => $event->reservation->external_reference ?? null,
                'metadata' => $event->reservationData
            ], $event->reservationData);

            // Traiter la réservation
            $result = $this->reservationService->processReservation($reservationData);

            Log::info('Transactions créées avec succès pour la réservation', [
                'reservation_id' => $event->reservation->id,
                'transactions_count' => count($result['transactions']),
                'success' => $result['success']
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement des transactions de réservation', [
                'reservation_id' => $event->reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Marquer la réservation comme échouée
            $event->reservation->status = 'failed';
            $event->reservation->metadata = array_merge($event->reservation->metadata ?? [], [
                'transaction_error' => $e->getMessage(),
                'failed_at' => now()->toISOString()
            ]);
            $event->reservation->save();

            throw $e;
        }
    }

    /**
     * Déterminer le statut de paiement basé sur la réservation
     */
    private function determinePaymentStatus($reservation): string
    {
        // Logique pour déterminer le statut de paiement
        if ($reservation->status === 'completed') {
            return 'completed';
        } elseif ($reservation->status === 'canceled') {
            return 'canceled';
        } elseif ($reservation->status === 'failed') {
            return 'failed';
        } else {
            return 'pending';
        }
    }

    /**
     * Extraire les informations de localisation
     */
    private function extractLocation($reservation): ?array
    {
        $location = null;

        if ($reservation->charging_point_id) {
            $chargingPoint = \App\Models\ChargingPoint::find($reservation->charging_point_id);
            if ($chargingPoint) {
                $location = [
                    'charging_point_id' => $chargingPoint->id,
                    'charging_point_name' => $chargingPoint->name,
                    'address' => $chargingPoint->address,
                    'city' => $chargingPoint->city,
                    'postal_code' => $chargingPoint->postal_code,
                    'country' => $chargingPoint->country,
                    'latitude' => $chargingPoint->latitude,
                    'longitude' => $chargingPoint->longitude
                ];
            }
        }

        return $location;
    }
}
