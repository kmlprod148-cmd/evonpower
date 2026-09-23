<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les statuts de verrouillage des connecteurs OCPP
 */
enum ConnectorLockStatusEnum: string
{
    case UNLOCKED = 'Unlocked';
    case LOCKED = 'Locked';

    /**
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::UNLOCKED => 'Débloqué',
            self::LOCKED => 'Bloqué',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function color(): string
    {
        return match($this) {
            self::UNLOCKED => 'green',
            self::LOCKED => 'orange',
        };
    }

    /**
     * Obtient l'icône associée
     */
    public function icon(): string
    {
        return match($this) {
            self::UNLOCKED => 'lock-open',
            self::LOCKED => 'lock',
        };
    }

    /**
     * Vérifie si le connecteur est verrouillé
     */
    public function isLocked(): bool
    {
        return $this === self::LOCKED;
    }

    /**
     * Vérifie si le connecteur est déverrouillé
     */
    public function isUnlocked(): bool
    {
        return $this === self::UNLOCKED;
    }

    /**
     * Crée depuis une chaîne (insensible à la casse)
     */
    public static function fromString(string $value): ?self
    {
        $normalized = ucfirst(strtolower(trim($value)));
        
        return match($normalized) {
            'Unlocked', 'Available', 'Free' => self::UNLOCKED,
            'Locked', 'Blocked', 'Occupied' => self::LOCKED,
            default => null,
        };
    }
}
