<?php

namespace App\DTO\OCPP;

use App\Core\Enums\BaseEnum;

/**
 * Enum pour les types de réinitialisation OCPP
 * 
 * @see OCPP 1.6 Specification - Reset.req
 */
enum ResetTypeEnum: string
{
    case SOFT = 'Soft';
    case HARD = 'Hard';

    /**
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::SOFT => 'Soft Reset',
            self::HARD => 'Hard Reset',
        };
    }

    /**
     * Obtient la description en français
     */
    public function description(): string
    {
        return match($this) {
            self::SOFT => 'Réinitialisation douce - termine les transactions en cours proprement',
            self::HARD => 'Réinitialisation forcée - interrompt toutes les opérations immédiatement',
        };
    }

    /**
     * Obtient l'icône associée
     */
    public function icon(): string
    {
        return match($this) {
            self::SOFT => 'refresh',
            self::HARD => 'exclamation-triangle',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function color(): string
    {
        return match($this) {
            self::SOFT => 'blue',
            self::HARD => 'red',
        };
    }

    /**
     * Crée depuis une chaîne (insensible à la casse)
     */
    public static function fromString(string $value): ?self
    {
        $normalized = ucfirst(strtolower(trim($value)));
        
        return match($normalized) {
            'Soft' => self::SOFT,
            'Hard' => self::HARD,
            default => null,
        };
    }

    /**
     * Vérifie si c'est un soft reset
     */
    public function isSoft(): bool
    {
        return $this === self::SOFT;
    }

    /**
     * Vérifie si c'est un hard reset
     */
    public function isHard(): bool
    {
        return $this === self::HARD;
    }
}
