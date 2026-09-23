<?php

namespace App\Enums;

/**
 * Statuts des abonnements utilisateur
 */
enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';

    /**
     * Obtenir le label traduit du statut
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Actif',
            self::EXPIRED => 'Expiré',
            self::CANCELLED => 'Annulé',
            self::SUSPENDED => 'Suspendu',
            self::PENDING => 'En attente',
        };
    }

    /**
     * Obtenir la couleur du badge
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::EXPIRED => 'warning',
            self::CANCELLED => 'danger',
            self::SUSPENDED => 'warning',
            self::PENDING => 'info',
        };
    }

    /**
     * Vérifie si l'abonnement permet l'utilisation
     */
    public function allowsUsage(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Vérifie si le statut permet une annulation
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::ACTIVE, self::PENDING]);
    }

    /**
     * Vérifie si le statut permet une réactivation
     */
    public function canReactivate(): bool
    {
        return in_array($this, [self::EXPIRED, self::CANCELLED, self::SUSPENDED]);
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
