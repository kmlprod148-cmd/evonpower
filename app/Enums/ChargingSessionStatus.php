<?php

namespace App\Enums;

/**
 * Enum unifié pour les statuts de session de recharge
 * Utilisé pour harmoniser les statuts entre reservations, transactions et charging_sessions
 */
enum ChargingSessionStatus: string
{
    // Statuts de base
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    
    // Statuts spécifiques au prépayé
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    
    // Statuts spécifiques au postpayé
    case AUTHORIZED = 'authorized';
    case DEBIT_PENDING = 'debit_pending';
    case DEBITED = 'debit_failed';
    case DEBT_COLLECTION = 'debt_collection';
    
    // Statuts de validation
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    
    /**
     * Obtenir le label lisible du statut
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
            self::FAILED => 'Échoué',
            self::EXPIRED => 'Expiré',
            self::REFUNDED => 'Remboursé',
            self::PARTIALLY_REFUNDED => 'Partiellement remboursé',
            self::AUTHORIZED => 'Autorisé',
            self::DEBIT_PENDING => 'Débit en attente',
            self::DEBITED => 'Débité',
            self::DEBT_COLLECTION => 'Recouvrement',
            self::VALIDATED => 'Validé',
            self::REJECTED => 'Rejeté',
        };
    }

    /**
     * Obtenir la couleur pour l'interface utilisateur
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::FAILED => 'danger',
            self::EXPIRED => 'secondary',
            self::REFUNDED => 'success',
            self::PARTIALLY_REFUNDED => 'warning',
            self::AUTHORIZED => 'info',
            self::DEBIT_PENDING => 'warning',
            self::DEBITED => 'success',
            self::DEBT_COLLECTION => 'danger',
            self::VALIDATED => 'success',
            self::REJECTED => 'danger',
        };
    }

    /**
     * Vérifier si le statut est terminal (fin de cycle de vie)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::FAILED,
            self::EXPIRED,
            self::REFUNDED,
            self::REJECTED,
        ]);
    }

    /**
     * Vérifier si le statut permet une transition vers un autre statut
     */
    public function canTransitionTo(ChargingSessionStatus $newStatus): bool
    {
        $allowedTransitions = [
            self::PENDING => [
                self::IN_PROGRESS,
                self::AUTHORIZED,
                self::CANCELLED,
                self::EXPIRED,
                self::REJECTED,
            ],
            self::AUTHORIZED => [
                self::IN_PROGRESS,
                self::CANCELLED,
                self::DEBT_COLLECTION,
            ],
            self::IN_PROGRESS => [
                self::COMPLETED,
                self::CANCELLED,
                self::FAILED,
            ],
            self::DEBIT_PENDING => [
                self::DEBITED,
                self::DEBT_COLLECTION,
                self::FAILED,
            ],
            self::PARTIALLY_REFUNDED => [
                self::REFUNDED,
                self::COMPLETED,
            ],
        ];

        return in_array($newStatus, $allowedTransitions[$this->value] ?? []);
    }

    /**
     * Obtenir le type de paiement associé au statut
     */
    public function getPaymentType(): ?string
    {
        return match ($this) {
            self::PENDING, self::IN_PROGRESS, self::COMPLETED, self::CANCELLED, self::FAILED,
            self::EXPIRED, self::VALIDATED, self::REJECTED => null,
            self::REFUNDED, self::PARTIALLY_REFUNDED => 'prepaid',
            self::AUTHORIZED, self::DEBIT_PENDING, self::DEBITED, self::DEBT_COLLECTION => 'postpaid',
        };
    }

    /**
     * Convertir depuis un statut de réservation
     */
    public static function fromReservationStatus(ReservationStatus $status): self
    {
        return match ($status) {
            ReservationStatus::PENDING, ReservationStatus::PENDING_CONFIRMATION => self::PENDING,
            ReservationStatus::CONFIRMED => self::AUTHORIZED,
            ReservationStatus::ACTIVE => self::IN_PROGRESS,
            ReservationStatus::COMPLETED => self::COMPLETED,
            ReservationStatus::CANCELED => self::CANCELLED,
        };
    }

    /**
     * Convertir depuis un statut de transaction
     */
    public static function fromTransactionStatus(TransactionStatus $status): self
    {
        return match ($status) {
            TransactionStatus::PENDING => self::PENDING,
            TransactionStatus::IN_PROGRESS => self::IN_PROGRESS,
            TransactionStatus::CONFIRMED => self::AUTHORIZED,
            TransactionStatus::COMPLETED => self::COMPLETED,
            TransactionStatus::CANCELLED => self::CANCELLED,
            TransactionStatus::FAILED => self::FAILED,
        };
    }
}
