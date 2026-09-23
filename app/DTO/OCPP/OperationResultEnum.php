<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les résultats génériques d'opérations OCPP
 */
enum OperationResultEnum: string
{
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case PENDING = 'PENDING';
    case TIMEOUT = 'TIMEOUT';

    /**
     * Vérifie si l'opération a réussi
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Vérifie si l'opération a échoué
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::SUCCESS => 'Succès',
            self::FAILED => 'Échec',
            self::PENDING => 'En attente',
            self::TIMEOUT => 'Timeout',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function color(): string
    {
        return match($this) {
            self::SUCCESS => 'green',
            self::FAILED => 'red',
            self::PENDING => 'yellow',
            self::TIMEOUT => 'orange',
        };
    }

    /**
     * Crée depuis une chaîne (insensible à la casse)
     */
    public static function fromString(string $value): ?self
    {
        $normalized = strtoupper(trim($value));
        
        return match($normalized) {
            'SUCCESS', 'OK', 'ACCEPTED' => self::SUCCESS,
            'FAILED', 'ERROR', 'REJECTED' => self::FAILED,
            'PENDING', 'IN_PROGRESS' => self::PENDING,
            'TIMEOUT', 'TIMED_OUT' => self::TIMEOUT,
            default => null,
        };
    }
}
