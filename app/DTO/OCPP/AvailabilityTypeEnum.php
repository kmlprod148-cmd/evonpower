<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les types de disponibilité OCPP 1.6
 * 
 * @see OCPP 1.6 Specification - ChangeAvailability.req
 */
enum AvailabilityTypeEnum: string
{
    /**
     * La borne/connecteur est opérationnel et peut accepter des sessions de charge
     */
    case OPERATIVE = 'Operative';

    /**
     * La borne/connecteur est hors service et ne peut pas accepter de nouvelles sessions
     * Les sessions en cours peuvent continuer jusqu'à leur fin
     */
    case INOPERATIVE = 'Inoperative';

    /**
     * Vérifie si le type est opérationnel
     */
    public function isOperative(): bool
    {
        return $this === self::OPERATIVE;
    }

    /**
     * Vérifie si le type est inopérationnel
     */
    public function isInoperative(): bool
    {
        return $this === self::INOPERATIVE;
    }

    /**
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::OPERATIVE => 'Opérationnel',
            self::INOPERATIVE => 'Hors service',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function color(): string
    {
        return match($this) {
            self::OPERATIVE => 'green',
            self::INOPERATIVE => 'red',
        };
    }

    /**
     * Obtient l'icône associée
     */
    public function icon(): string
    {
        return match($this) {
            self::OPERATIVE => 'check-circle',
            self::INOPERATIVE => 'x-circle',
        };
    }

    /**
     * Crée depuis une valeur booléenne
     */
    public static function fromBoolean(bool $operative): self
    {
        return $operative ? self::OPERATIVE : self::INOPERATIVE;
    }

    /**
     * Crée depuis une chaîne (insensible à la casse)
     */
    public static function fromString(string $value): ?self
    {
        $normalized = ucfirst(strtolower(trim($value)));
        
        return match($normalized) {
            'Operative', 'Available', 'Online', 'Enabled', 'Active' => self::OPERATIVE,
            'Inoperative', 'Unavailable', 'Offline', 'Disabled', 'Inactive' => self::INOPERATIVE,
            default => null,
        };
    }
}
