<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les statuts de réponse Remote Start OCPP
 */
enum RemoteStartStatusEnum: string
{
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';

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
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Rejeté',
        };
    }

    /**
     * Obtient le message utilisateur en français
     */
    public function userMessage(): string
    {
        return match($this) {
            self::ACCEPTED => 'Le démarrage à distance a été accepté. La session de charge va commencer.',
            self::REJECTED => 'Le démarrage à distance a été rejeté. Le connecteur est peut-être indisponible.',
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
        };
    }

    /**
     * Crée depuis une chaîne (insensible à la casse)
     */
    public static function fromString(string $value): ?self
    {
        $normalized = strtoupper(trim($value));
        
        return match($normalized) {
            'ACCEPTED', 'OK', 'SUCCESS' => self::ACCEPTED,
            'REJECTED', 'ERROR', 'FAILED' => self::REJECTED,
            default => null,
        };
    }
}
