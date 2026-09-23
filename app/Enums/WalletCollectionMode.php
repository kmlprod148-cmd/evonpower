<?php

namespace App\Enums;

/**
 * Modes de collecte du wallet (admin, intégrateur, partenaire)
 */
enum WalletCollectionMode: string
{
    case MANUAL = 'manual';
    case AUTO = 'auto';
    case NONE = 'none';

    /**
     * Obtenir le label traduit du mode
     */
    public function getLabel(): string
    {
        return match($this) {
            self::MANUAL => 'Collecte manuelle',
            self::AUTO => 'Collecte automatique',
            self::NONE => 'Pas de collecte',
        };
    }

    /**
     * Obtenir la couleur du badge
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::MANUAL => 'info',
            self::AUTO => 'success',
            self::NONE => 'secondary',
        };
    }

    /**
     * Vérifie si la collecte automatique est activée
     */
    public function isAutoEnabled(): bool
    {
        return $this === self::AUTO;
    }

    /**
     * Obtenir toutes les valeurs
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtenir toutes les options pour un select
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }
        return $options;
    }
}
