<?php

namespace App\Exceptions;

use Exception;

class FeeCalculationException extends Exception
{
    /**
     * Code d'erreur pour les frais invalides
     */
    public const INVALID_FEE_CONFIGURATION = 1001;
    
    /**
     * Code d'erreur pour les business profiles manquants
     */
    public const MISSING_BUSINESS_PROFILE = 1002;
    
    /**
     * Code d'erreur pour les montants invalides
     */
    public const INVALID_AMOUNT = 1003;
    
    /**
     * Code d'erreur pour les utilisateurs invalides
     */
    public const INVALID_USERS = 1004;
    
    /**
     * Code d'erreur pour les types de transaction non supportés
     */
    public const UNSUPPORTED_TRANSACTION_TYPE = 1005;
    
    /**
     * Code d'erreur pour les limites dépassées
     */
    public const LIMIT_EXCEEDED = 1006;

    /**
     * Créer une exception pour une configuration de frais invalide
     */
    public static function invalidFeeConfiguration(string $message = 'Configuration de frais invalide'): self
    {
        return new self($message, self::INVALID_FEE_CONFIGURATION);
    }

    /**
     * Créer une exception pour un business profile manquant
     */
    public static function missingBusinessProfile(string $transactionType = null): self
    {
        $message = 'Aucun business profile actif trouvé';
        if ($transactionType) {
            $message .= " pour le type de transaction: {$transactionType}";
        }
        
        return new self($message, self::MISSING_BUSINESS_PROFILE);
    }

    /**
     * Créer une exception pour un montant invalide
     */
    public static function invalidAmount(string $message = 'Montant invalide'): self
    {
        return new self($message, self::INVALID_AMOUNT);
    }

    /**
     * Créer une exception pour des utilisateurs invalides
     */
    public static function invalidUsers(string $message = 'Utilisateurs invalides'): self
    {
        return new self($message, self::INVALID_USERS);
    }

    /**
     * Créer une exception pour un type de transaction non supporté
     */
    public static function unsupportedTransactionType(string $type): self
    {
        return new self("Type de transaction non supporté: {$type}", self::UNSUPPORTED_TRANSACTION_TYPE);
    }

    /**
     * Créer une exception pour des limites dépassées
     */
    public static function limitExceeded(string $message): self
    {
        return new self($message, self::LIMIT_EXCEEDED);
    }

    /**
     * Obtenir le type d'erreur basé sur le code
     */
    public function getErrorType(): string
    {
        return match($this->getCode()) {
            self::INVALID_FEE_CONFIGURATION => 'invalid_fee_configuration',
            self::MISSING_BUSINESS_PROFILE => 'missing_business_profile',
            self::INVALID_AMOUNT => 'invalid_amount',
            self::INVALID_USERS => 'invalid_users',
            self::UNSUPPORTED_TRANSACTION_TYPE => 'unsupported_transaction_type',
            self::LIMIT_EXCEEDED => 'limit_exceeded',
            default => 'unknown_error'
        };
    }

    /**
     * Obtenir un message d'erreur convivial
     */
    public function getFriendlyMessage(): string
    {
        return match($this->getCode()) {
            self::INVALID_FEE_CONFIGURATION => 'La configuration des frais n\'est pas valide.',
            self::MISSING_BUSINESS_PROFILE => 'Aucun profil d\'affaires actif n\'a été trouvé pour cette transaction.',
            self::INVALID_AMOUNT => 'Le montant de la transaction n\'est pas valide.',
            self::INVALID_USERS => 'Les utilisateurs spécifiés ne sont pas valides.',
            self::UNSUPPORTED_TRANSACTION_TYPE => 'Ce type de transaction n\'est pas supporté.',
            self::LIMIT_EXCEEDED => 'Les limites de transaction ont été dépassées.',
            default => 'Une erreur inattendue s\'est produite lors du calcul des frais.'
        };
    }

    /**
     * Obtenir les détails de l'erreur pour le logging
     */
    public function getErrorDetails(): array
    {
        return [
            'error_type' => $this->getErrorType(),
            'error_code' => $this->getCode(),
            'message' => $this->getMessage(),
            'friendly_message' => $this->getFriendlyMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }
}
