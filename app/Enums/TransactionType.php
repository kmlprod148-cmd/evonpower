<?php

namespace App\Enums;

/**
 * Constants normalisées pour les types et catégories de transactions
 * 
 * Catégories principales:
 * - recharge: Recharges de crédit utilisateur
 * - recharge_wallet: Opérations de portefeuille électronique
 * - abonnement: Abonnements périodiques ou forfaits
 * - commission: Rémunérations de partenaires et intégrateurs
 */
class TransactionType
{
    // ========================
    // CATÉGORIES PRINCIPAALES
    // ========================
    public const CATEGORY_RECHARGE = 'recharge';
    public const CATEGORY_RECHARGE_WALLET = 'recharge_wallet';
    public const CATEGORY_ABONNEMENT = 'abonnement';
    public const CATEGORY_COMMISSION = 'commission';
    
    // ========================
    // TYPES DE TRANSACTIONS
    // ========================
    
    // Recharges (crédit utilisateur)
    public const TYPE_RECHARGE_CREDIT = 'recharge_credit';
    public const TYPE_RECHARGE_BANK_TRANSFER = 'recharge_bank_transfer';
    public const TYPE_RECHARGE_CASH = 'recharge_cash';
    public const TYPE_RECHARGE_CARD = 'recharge_card';
    public const TYPE_RECHARGE_CMI = 'recharge_cmi';
    
    // Opérations portefeuille électronique
    public const TYPE_WALLET_DEPOSIT = 'wallet_deposit';
    public const TYPE_WALLET_WITHDRAWAL = 'wallet_withdrawal';
    public const TYPE_WALLET_TRANSFER = 'wallet_transfer';
    public const TYPE_WALLET_REFUND = 'wallet_refund';
    
    // Abonnements / Forfaits
    public const TYPE_SUBSCRIPTION_MONTHLY = 'subscription_monthly';
    public const TYPE_SUBSCRIPTION_YEARLY = 'subscription_yearly';
    public const TYPE_SUBSCRIPTION_PACK = 'subscription_pack';
    public const TYPE_SUBSCRIPTION_RENEWAL = 'subscription_renewal';
    
    // Sessions de charge (client)
    public const TYPE_CHARGING_SESSION = 'charging_session';
    public const TYPE_CHARGING_SESSION_PREPAID = 'charging_session_prepaid';
    public const TYPE_CHARGING_SESSION_POSTPAID = 'charging_session_postpaid';
    
    // Commissions et rémunérations
    public const TYPE_COMMISSION_ADMIN = 'commission_admin';
    public const TYPE_COMMISSION_INTEGRATOR = 'commission_integrator';
    public const TYPE_COMMISSION_OPERATOR = 'commission_operator';
    public const TYPE_COMMISSION_PARTNER = 'commission_partner';
    
    // Transactions hiérarchiques
    public const TYPE_HIERARCHICAL_ADMIN_INTEGRATOR = 'hierarchical_admin_integrator';
    public const TYPE_HIERARCHICAL_INTEGRATOR_OPERATOR = 'hierarchical_integrator_operator';
    public const TYPE_HIERARCHICAL_OPERATOR_PARTNER = 'hierarchical_operator_partner';
    
    // Frais et pénalités
    public const TYPE_FEE_ACTIVATION = 'fee_activation';
    public const TYPE_FEE_ADMIN = 'fee_admin';
    public const TYPE_FEE_INTEGRATOR = 'fee_integrator';
    public const TYPE_FEE_LATE_PAYMENT = 'fee_late_payment';
    
    // Réservation
    public const TYPE_RESERVATION_PAYMENT = 'reservation_payment';
    public const TYPE_RESERVATION_REFUND = 'reservation_refund';
    public const TYPE_RESERVATION_CANCEL = 'reservation_cancel';
    
    // ========================
    // SOUS-CATÉGORIES PAR TYPE
    // ========================
    
    public const SUB_CATEGORIES = [
        // Recharges
        self::CATEGORY_RECHARGE => [
            self::TYPE_RECHARGE_CREDIT => 'Recharge de crédit',
            self::TYPE_RECHARGE_BANK_TRANSFER => 'Virement bancaire',
            self::TYPE_RECHARGE_CASH => 'Paiement espèce',
            self::TYPE_RECHARGE_CARD => 'Carte bancaire',
            self::TYPE_RECHARGE_CMI => 'Paiement CMI',
        ],
        // Portefeuille électronique
        self::CATEGORY_RECHARGE_WALLET => [
            self::TYPE_WALLET_DEPOSIT => 'Dépôt portefeuille',
            self::TYPE_WALLET_WITHDRAWAL => 'Retrait portefeuille',
            self::TYPE_WALLET_TRANSFER => 'Transfert portefeuille',
            self::TYPE_WALLET_REFUND => 'Remboursement portefeuille',
        ],
        // Abonnements
        self::CATEGORY_ABONNEMENT => [
            self::TYPE_SUBSCRIPTION_MONTHLY => 'Abonnement mensuel',
            self::TYPE_SUBSCRIPTION_YEARLY => 'Abonnement annuel',
            self::TYPE_SUBSCRIPTION_PACK => 'Pack de recharge',
            self::TYPE_SUBSCRIPTION_RENEWAL => 'Renouvellement abonnement',
        ],
        // Commissions
        self::CATEGORY_COMMISSION => [
            self::TYPE_COMMISSION_ADMIN => 'Commission admin',
            self::TYPE_COMMISSION_INTEGRATOR => "Commission intégrateur",
            self::TYPE_COMMISSION_OPERATOR => 'Commission opérateur',
            self::TYPE_COMMISSION_PARTNER => 'Commission partenaire',
        ],
    ];

    // ========================
    // MAPPING TYPES VERS CATÉGORIES
    // ========================
    
    public const TYPE_TO_CATEGORY = [
        // Recharges
        self::TYPE_RECHARGE_CREDIT => self::CATEGORY_RECHARGE,
        self::TYPE_RECHARGE_BANK_TRANSFER => self::CATEGORY_RECHARGE,
        self::TYPE_RECHARGE_CASH => self::CATEGORY_RECHARGE,
        self::TYPE_RECHARGE_CARD => self::CATEGORY_RECHARGE,
        self::TYPE_RECHARGE_CMI => self::CATEGORY_RECHARGE,
        
        // Portefeuille électronique
        self::TYPE_WALLET_DEPOSIT => self::CATEGORY_RECHARGE_WALLET,
        self::TYPE_WALLET_WITHDRAWAL => self::CATEGORY_RECHARGE_WALLET,
        self::TYPE_WALLET_TRANSFER => self::CATEGORY_RECHARGE_WALLET,
        self::TYPE_WALLET_REFUND => self::CATEGORY_RECHARGE_WALLET,
        
        // Abonnements
        self::TYPE_SUBSCRIPTION_MONTHLY => self::CATEGORY_ABONNEMENT,
        self::TYPE_SUBSCRIPTION_YEARLY => self::CATEGORY_ABONNEMENT,
        self::TYPE_SUBSCRIPTION_PACK => self::CATEGORY_ABONNEMENT,
        self::TYPE_SUBSCRIPTION_RENEWAL => self::CATEGORY_ABONNEMENT,
        
        // Sessions de charge
        self::TYPE_CHARGING_SESSION => self::CATEGORY_RECHARGE,
        self::TYPE_CHARGING_SESSION_PREPAID => self::CATEGORY_RECHARGE,
        self::TYPE_CHARGING_SESSION_POSTPAID => self::CATEGORY_RECHARGE_WALLET,
        
        // Commissions
        self::TYPE_COMMISSION_ADMIN => self::CATEGORY_COMMISSION,
        self::TYPE_COMMISSION_INTEGRATOR => self::CATEGORY_COMMISSION,
        self::TYPE_COMMISSION_OPERATOR => self::CATEGORY_COMMISSION,
        self::TYPE_COMMISSION_PARTNER => self::CATEGORY_COMMISSION,
        
        // Transactions hiérarchiques
        self::TYPE_HIERARCHICAL_ADMIN_INTEGRATOR => self::CATEGORY_COMMISSION,
        self::TYPE_HIERARCHICAL_INTEGRATOR_OPERATOR => self::CATEGORY_COMMISSION,
        self::TYPE_HIERARCHICAL_OPERATOR_PARTNER => self::CATEGORY_COMMISSION,
        
        // Frais
        self::TYPE_FEE_ACTIVATION => self::CATEGORY_COMMISSION,
        self::TYPE_FEE_ADMIN => self::CATEGORY_COMMISSION,
        self::TYPE_FEE_INTEGRATOR => self::CATEGORY_COMMISSION,
        self::TYPE_FEE_LATE_PAYMENT => self::CATEGORY_COMMISSION,
        
        // Réservation
        self::TYPE_RESERVATION_PAYMENT => self::CATEGORY_RECHARGE,
        self::TYPE_RESERVATION_REFUND => self::CATEGORY_RECHARGE,
        self::TYPE_RESERVATION_CANCEL => self::CATEGORY_RECHARGE,
    ];

    // ========================
    // LABELS POUR CATÉGORIES
    // ========================
    
    public const CATEGORY_LABELS = [
        self::CATEGORY_RECHARGE => 'Recharge',
        self::CATEGORY_RECHARGE_WALLET => 'Portefeuille électronique',
        self::CATEGORY_ABONNEMENT => 'Abonnement',
        self::CATEGORY_COMMISSION => 'Commission',
    ];

    // ========================
    // LABELS POUR TYPES
    // ========================
    
    public const TYPE_LABELS = [
        // Recharges
        self::TYPE_RECHARGE_CREDIT => 'Recharge de crédit',
        self::TYPE_RECHARGE_BANK_TRANSFER => 'Virement bancaire',
        self::TYPE_RECHARGE_CASH => 'Paiement espèce',
        self::TYPE_RECHARGE_CARD => 'Carte bancaire',
        self::TYPE_RECHARGE_CMI => 'Paiement CMI',
        
        // Portefeuille électronique
        self::TYPE_WALLET_DEPOSIT => 'Dépôt portefeuille',
        self::TYPE_WALLET_WITHDRAWAL => 'Retrait portefeuille',
        self::TYPE_WALLET_TRANSFER => 'Transfert portefeuille',
        self::TYPE_WALLET_REFUND => 'Remboursement portefeuille',
        
        // Abonnements
        self::TYPE_SUBSCRIPTION_MONTHLY => 'Abonnement mensuel',
        self::TYPE_SUBSCRIPTION_YEARLY => 'Abonnement annuel',
        self::TYPE_SUBSCRIPTION_PACK => 'Pack de recharge',
        self::TYPE_SUBSCRIPTION_RENEWAL => 'Renouvellement abonnement',
        
        // Sessions de charge
        self::TYPE_CHARGING_SESSION => 'Session de charge',
        self::TYPE_CHARGING_SESSION_PREPAID => 'Session prépayée',
        self::TYPE_CHARGING_SESSION_POSTPAID => 'Session postpayée',
        
        // Commissions
        self::TYPE_COMMISSION_ADMIN => 'Commission admin',
        self::TYPE_COMMISSION_INTEGRATOR => "Commission intégrateur",
        self::TYPE_COMMISSION_OPERATOR => 'Commission opérateur',
        self::TYPE_COMMISSION_PARTNER => 'Commission partenaire',
        
        // Transactions hiérarchiques
        self::TYPE_HIERARCHICAL_ADMIN_INTEGRATOR => 'Admin → Intégrateur',
        self::TYPE_HIERARCHICAL_INTEGRATOR_OPERATOR => 'Intégrateur → Opérateur',
        self::TYPE_HIERARCHICAL_OPERATOR_PARTNER => 'Opérateur → Partenaire',
        
        // Frais
        self::TYPE_FEE_ACTIVATION => "Frais d'activation",
        self::TYPE_FEE_ADMIN => 'Frais admin',
        self::TYPE_FEE_INTEGRATOR => 'Frais intégrateur',
        self::TYPE_FEE_LATE_PAYMENT => 'Frais de retard',
        
        // Réservation
        self::TYPE_RESERVATION_PAYMENT => 'Paiement réservation',
        self::TYPE_RESERVATION_REFUND => 'Remboursement réservation',
        self::TYPE_RESERVATION_CANCEL => 'Annulation réservation',
    ];

    /**
     * Obtenir la catégorie d'un type de transaction
     */
    public static function getCategory(string $type): string
    {
        return self::TYPE_TO_CATEGORY[$type] ?? self::CATEGORY_RECHARGE;
    }

    /**
     * Obtenir le label d'un type de transaction
     */
    public static function getTypeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? $type;
    }

    /**
     * Obtenir le label d'une catégorie
     */
    public static function getCategoryLabel(string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? $category;
    }

    /**
     * Obtenir les sous-catégories d'une catégorie
     */
    public static function getSubCategories(string $category): array
    {
        return self::SUB_CATEGORIES[$category] ?? [];
    }

    /**
     * Vérifier si un type est valide
     */
    public static function isValidType(string $type): bool
    {
        return isset(self::TYPE_TO_CATEGORY[$type]);
    }

    /**
     * Vérifier si une catégorie est valide
     */
    public static function isValidCategory(string $category): bool
    {
        return isset(self::CATEGORY_LABELS[$category]);
    }

    /**
     * Obtenir tous les types disponibles
     */
    public static function getAllTypes(): array
    {
        return array_keys(self::TYPE_TO_CATEGORY);
    }

    /**
     * Obtenir toutes les catégories disponibles
     */
    public static function getAllCategories(): array
    {
        return array_keys(self::CATEGORY_LABELS);
    }

    /**
     * Obtenir les types pour une catégorie spécifique
     */
    public static function getTypesByCategory(string $category): array
    {
        return array_keys(self::TYPE_TO_CATEGORY, $category);
    }

    /**
     * Formater pour API (avec traductions)
     */
    public static function formatForApi(string $locale = 'fr'): array
    {
        $categories = [];
        
        foreach (self::CATEGORY_LABELS as $categoryKey => $categoryLabel) {
            $types = [];
            
            foreach (self::TYPE_TO_CATEGORY as $typeKey => $typeCategory) {
                if ($typeCategory === $categoryKey) {
                    $types[] = [
                        'code' => $typeKey,
                        'label' => self::TYPE_LABELS[$typeKey] ?? $typeKey,
                    ];
                }
            }
            
            $categories[] = [
                'code' => $categoryKey,
                'label' => $categoryLabel,
                'types' => $types,
            ];
        }
        
        return $categories;
    }
}
