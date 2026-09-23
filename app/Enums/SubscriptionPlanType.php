<?php

namespace App\Enums;

/**
 * Types de plans d'abonnement pour véhicules électriques
 */
enum SubscriptionPlanType: string
{
    // Abonnement basé sur le nombre de recharges
    case PER_CHARGE = 'per_charge';
    
    // Abonnement avec quota kWh
    case PER_KWH = 'per_kwh';
    
    // Abonnement basé sur le temps d'utilisation global
    case PER_TIME = 'per_time';
    
    // Abonnement basé sur la durée/session
    case PER_SESSION = 'per_session';
    
    // Abonnement mensuel (1 mois)
    case MONTHLY = 'monthly';
    
    // Abonnement trimestriel (3 mois)
    case QUARTERLY = 'quarterly';
    
    // Abonnement semestriel (6 mois)
    case SEMI_ANNUAL = 'semi_annual';
    
    // Abonnement annuel (12 mois)
    case ANNUAL = 'annual';

    /**
     * Obtenir le label traduit du type de plan
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PER_CHARGE => 'Par recharge',
            self::PER_KWH => 'Par kWh',
            self::PER_TIME => 'Par temps global',
            self::PER_SESSION => 'Par session',
            self::MONTHLY => 'Mensuel (1 mois)',
            self::QUARTERLY => 'Trimestriel (3 mois)',
            self::SEMI_ANNUAL => 'Semestriel (6 mois)',
            self::ANNUAL => 'Annuel (12 mois)',
        };
    }

    /**
     * Obtenir la durée en mois
     */
    public function getDurationMonths(): int
    {
        return match($this) {
            self::PER_CHARGE, self::PER_KWH, self::PER_TIME, self::PER_SESSION => 0, // Pas de cycle
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::SEMI_ANNUAL => 6,
            self::ANNUAL => 12,
        };
    }

    /**
     * Vérifie si le plan est basé sur un cycle temporel
     */
    public function isCyclical(): bool
    {
        return in_array($this, [
            self::MONTHLY,
            self::QUARTERLY,
            self::SEMI_ANNUAL,
            self::ANNUAL,
        ]);
    }

    /**
     * Vérifie si le plan est basé sur les quotas
     */
    public function isQuotaBased(): bool
    {
        return in_array($this, [
            self::PER_CHARGE,
            self::PER_KWH,
            self::PER_TIME,
            self::PER_SESSION,
        ]);
    }

    /**
     * Obtenir le type de quota géré par ce plan
     */
    public function getQuotaType(): ?string
    {
        return match($this) {
            self::PER_CHARGE => 'sessions',
            self::PER_KWH => 'kwh',
            self::PER_TIME, self::PER_SESSION => 'duration',
            self::MONTHLY, self::QUARTERLY, self::SEMI_ANNUAL, self::ANNUAL => null,
        };
    }

    /**
     * Obtenir tous les types disponibles
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
