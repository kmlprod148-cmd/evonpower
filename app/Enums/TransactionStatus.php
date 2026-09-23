<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::CONFIRMED => 'Confirmé',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
            self::FAILED => 'Échoué',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::IN_PROGRESS => 'info',
            self::CONFIRMED => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::FAILED => 'danger',
        };
    }
}