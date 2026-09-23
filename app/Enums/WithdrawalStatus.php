<?php

namespace App\Enums;

/**
 * Statuts des demandes de retrait
 */
enum WithdrawalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Obtenir le label traduit du statut
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::PROCESSING => 'En cours',
            self::COMPLETED => 'Terminé',
            self::FAILED => 'Échoué',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * Obtenir la couleur du badge
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'info',
            self::REJECTED => 'danger',
            self::PROCESSING => 'primary',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    /**
     * Vérifie si le statut est terminal
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
        ]);
    }

    /**
     * Vérifie si le statut permet une action
     */
    public function canApprove(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Vérifie si le statut permet un rejet
     */
    public function canReject(): bool
    {
        return in_array($this, [self::PENDING, self::APPROVED]);
    }

    /**
     * Vérifie si le statut permet le traitement
     */
    public function canProcess(): bool
    {
        return $this === self::APPROVED;
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
