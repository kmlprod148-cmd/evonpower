<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les statuts de réponse Remote Stop OCPP
 */
enum RemoteStopStatusEnum: string
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
            self::ACCEPTED => 'L\'arrêt à distance a été accepté. La session de charge va être terminée.',
            self::REJECTED => 'L\'arrêt à distance a été rejeté. Aucune transaction active sur ce connecteur.',
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
