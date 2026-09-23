<?php

namespace App\Enums;

/**
 * Statut de collecte des fonds pour une transaction
 */
enum CollectStatus: string
{
    case PENDING = 'pending';
    case TO_COLLECT = 'to_collect';
    case COLLECTED = 'collected';
    case WITHDRAWN = 'withdrawn';

    /**
     * Get translated label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::TO_COLLECT => 'À collecter',
            self::COLLECTED => 'Collecté',
            self::WITHDRAWN => 'Retiré',
        };
    }

    /**
     * Get badge color
     */
    public function getBadgeColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::TO_COLLECT => 'info',
            self::COLLECTED => 'success',
            self::WITHDRAWN => 'secondary',
        };
    }

    /**
     * Check if status allows withdrawal
     */
    public function canWithdraw(): bool
    {
        return in_array($this, [self::TO_COLLECT, self::COLLECTED]);
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
