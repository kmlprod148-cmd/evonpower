<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les statuts de réponse OCPP ChangeAvailability
 * 
 * @see OCPP 1.6 Specification - ChangeAvailability.conf
 */
enum AvailabilityStatusEnum: string
{
    /**
     * La demande a été acceptée et le changement sera effectué immédiatement
     */
    case ACCEPTED = 'Accepted';

    /**
     * La demande a été rejetée
     */
    case REJECTED = 'Rejected';

    /**
     * La demande a été acceptée mais le changement sera effectué plus tard
     * (ex: après la fin de la transaction en cours)
     */
    case SCHEDULED = 'Scheduled';

    /**
     * Vérifie si la demande a été acceptée
     */
    public function isAccepted(): bool
    {
        return $this === self::ACCEPTED;
    }

    /**
     * Vérifie si la demande a été rejetée
     */
    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Vérifie si la demande a été planifiée
     */
    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    /**
     * Vérifie si la demande est un succès (acceptée ou planifiée)
     */
    public function isSuccess(): bool
    {
        return $this === self::ACCEPTED || $this === self::SCHEDULED;
    }

    /**
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Rejeté',
            self::SCHEDULED => 'Planifié',
        };
    }

    /**
     * Obtient le message utilisateur
     */
    public function userMessage(): string
    {
        return match($this) {
            self::ACCEPTED => 'Le changement de disponibilité a été effectué avec succès.',
            self::REJECTED => 'La demande de changement de disponibilité a été rejetée par la borne.',
            self::SCHEDULED => 'Le changement sera effectué après la fin de la session en cours.',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function color(): string
    {
        return match($this) {
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::SCHEDULED => 'yellow',
        };
    }

    /**
     * Crée depuis une chaîne (insensible à la casse)
     */
    public static function fromString(string $value): ?self
    {
        $normalized = ucfirst(strtolower(trim($value)));
        
        return match($normalized) {
            'Accepted' => self::ACCEPTED,
            'Rejected' => self::REJECTED,
            'Scheduled' => self::SCHEDULED,
            default => null,
        };
    }
}
