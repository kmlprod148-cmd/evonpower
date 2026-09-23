<?php

namespace App\Enums;

/**
 * Statuts du mode Postpayé
 */
enum PostpaidStatus: string
{
    // Utilisateur non autorisé pour le postpayé
    case NOT_AUTHORIZED = 'not_authorized';
    
    // En attente d'approbation
    case PENDING = 'pending';
    
    // Approuvé et actif
    case APPROVED = 'approved';
    
    // Suspendu temporairement
    case SUSPENDED = 'suspended';
    
    // Révoqué
    case REVOKED = 'revoked';

    /**
     * Obtenir le label traduit du statut
     */
    public function getLabel(): string
    {
        return match($this) {
            self::NOT_AUTHORIZED => 'Non autorisé',
            self::PENDING => 'En attente d\'approbation',
            self::APPROVED => 'Approuvé',
            self::SUSPENDED => 'Suspendu',
            self::REVOKED => 'Révoqué',
        };
    }

    /**
     * Vérifie si l'utilisateur peut utiliser le postpayé
     */
    public function canUsePostpaid(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Obtenir la couleur du badge
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::NOT_AUTHORIZED => 'gray',
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::SUSPENDED => 'warning',
            self::REVOKED => 'danger',
        };
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
