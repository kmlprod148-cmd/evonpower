<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case PENDING_CONFIRMATION = 'pending_confirmation';
    case APPROVED = 'approved';
    case RESERVED = 'reserved';
    case SESSION_INITIATED = 'session_initiated';
    case CONFIRMED = 'confirmed';
    case CANCELED = 'canceled';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case TIMEOUT_NO_TRANSACTION = 'timeout_no_transaction';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PENDING_CONFIRMATION => 'Pending Confirmation',
            self::APPROVED => 'Approved',
            self::RESERVED => 'Reserved',
            self::SESSION_INITIATED => 'Session Initiated',
            self::CONFIRMED => 'Confirmed',
            self::CANCELED => 'Canceled',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::EXPIRED => 'Expired',
            self::TIMEOUT_NO_TRANSACTION => 'Timeout No Transaction',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PENDING_CONFIRMATION => 'info',
            self::APPROVED => 'primary',
            self::RESERVED => 'info',
            self::SESSION_INITIATED => 'info',
            self::CONFIRMED => 'success',
            self::CANCELED => 'danger',
            self::ACTIVE => 'primary',
            self::COMPLETED => 'secondary',
            self::EXPIRED => 'warning',
            self::TIMEOUT_NO_TRANSACTION => 'danger',
            self::FAILED => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELED,
            self::EXPIRED,
            self::TIMEOUT_NO_TRANSACTION,
            self::FAILED,
        ]);
    }

    public function canTransitionTo(ReservationStatus $newStatus): bool
    {
        $allowedTransitions = [
            self::PENDING => [self::PENDING_CONFIRMATION, self::CANCELED],
            self::PENDING_CONFIRMATION => [self::APPROVED, self::CANCELED],
            self::APPROVED => [self::RESERVED, self::CANCELED, self::EXPIRED],
            self::RESERVED => [self::SESSION_INITIATED, self::TIMEOUT_NO_TRANSACTION, self::CANCELED],
            self::SESSION_INITIATED => [self::ACTIVE, self::CANCELED],
            self::ACTIVE => [self::COMPLETED, self::EXPIRED],
        ];

        return in_array($newStatus, $allowedTransitions[$this->value] ?? []);
    }
}