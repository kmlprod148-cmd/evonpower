<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché quand une réservation est approuvée
 * 
 * Cet événement peut déclencher le démarrage automatique de la transaction
 * si toutes les conditions sont remplies.
 */
class ReservationApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Reservation $reservation;
    public ?string $approvedBy;
    public string $approvalMethod;

    /**
     * Créer une nouvelle instance de l'événement
     *
     * @param Reservation $reservation
     * @param string|null $approvedBy User ID ou system
     * @param string $approvalMethod 'payment'|'admin'|'automatic'
     */
    public function __construct(Reservation $reservation, ?string $approvedBy = null, string $approvalMethod = 'payment')
    {
        $this->reservation = $reservation;
        $this->approvedBy = $approvedBy;
        $this->approvalMethod = $approvalMethod;
    }
}

