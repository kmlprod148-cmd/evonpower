<?php

namespace App\Enums;

/**
 * Type d'utilisateur qui collecte les fonds
 */
enum CollectUserType: string
{
    case ADMIN = 'admin';
    case INTEGRATOR = 'integrator';
    case PARTNER = 'partner';

    /**
     * Get translated label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ADMIN => 'Administrateur',
            self::INTEGRATOR => 'Intégrateur',
            self::PARTNER => 'Partenaire',
        };
    }

    /**
     * Get badge color
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ADMIN => 'danger',
            self::INTEGRATOR => 'info',
            self::PARTNER => 'success',
        };
    }

    /**
     * Get all values
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for select
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
