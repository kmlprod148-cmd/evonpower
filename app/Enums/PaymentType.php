<?php

namespace App\Enums;

/**
 * Types de paiement disponibles dans le système EVON
 */
enum PaymentType: string
{
    // Paiement par carte CMI
    case CMI = 'cmi';
    
    // Paiement hors ligne (espèces, virement)
    case OFFLINE = 'offline';
    
    // Paiement par QR Code (nouveau flux public)
    case QR_CODE = 'qr_code';
    
    // Paiement Postpayé (facturation différée)
    case POSTPAID = 'postpaid';
    
    // Paiement par wallet/crédits
    case WALLET = 'wallet';
    
    // Paiement Stripe
    case STRIPE = 'stripe';

    /**
     * Obtenir le label traduit du type de paiement
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CMI => 'Carte bancaire (CMI)',
            self::OFFLINE => 'Hors ligne',
            self::QR_CODE => 'QR Code',
            self::POSTPAID => 'Postpayé',
            self::WALLET => 'Portefeuille',
            self::STRIPE => 'Stripe',
        };
    }

    /**
     * Vérifie si le paiement nécessite une validation en temps réel
     */
    public function requiresRealTimeValidation(): bool
    {
        return in_array($this, [self::CMI, self::STRIPE]);
    }

    /**
     * Vérifie si le paiement permet un débit différé
     */
    public function supportsDeferredPayment(): bool
    {
        return in_array($this, [self::POSTPAID, self::WALLET]);
    }

    /**
     * Vérifie si le paiement est applicable pour le flux public
     */
    public function isPublicPaymentMethod(): bool
    {
        return in_array($this, [self::QR_CODE, self::WALLET, self::POSTPAID]);
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
