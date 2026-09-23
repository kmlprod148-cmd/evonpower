<?php

return [
    'financial_transactions' => 'Transactions Financières',
    'id' => 'ID',
    'transaction' => 'Transaction',
    'revenue_share' => 'Partage de Revenus',
    'payer' => 'Payeur',
    'payee' => 'Bénéficiaire',
    'amount' => 'Montant',
    'type' => 'Type',
    'status' => 'Statut',
    'description' => 'Description',
    'date' => 'Date',
    'not_applicable' => 'N/A',
    
    // Catégories de transactions
    'categories' => [
        'recharge' => 'Recharge',
        'recharge_wallet' => 'Portefeuille électronique',
        'abonnement' => 'Abonnement',
        'commission' => 'Commission',
    ],
    
    // Types de transactions
    'types' => [
        // Recharges
        'recharge_credit' => 'Recharge de crédit',
        'recharge_bank_transfer' => 'Virement bancaire',
        'recharge_cash' => 'Paiement espèce',
        'recharge_card' => 'Carte bancaire',
        'recharge_cmi' => 'Paiement CMI',
        
        // Portefeuille électronique
        'wallet_deposit' => 'Dépôt portefeuille',
        'wallet_withdrawal' => 'Retrait portefeuille',
        'wallet_transfer' => 'Transfert portefeuille',
        'wallet_refund' => 'Remboursement portefeuille',
        
        // Abonnements
        'subscription_monthly' => 'Abonnement mensuel',
        'subscription_yearly' => 'Abonnement annuel',
        'subscription_pack' => 'Pack de recharge',
        'subscription_renewal' => 'Renouvellement abonnement',
        
        // Sessions de charge
        'charging_session' => 'Session de charge',
        'charging_session_prepaid' => 'Session prépayée',
        'charging_session_postpaid' => 'Session postpayée',
        
        // Commissions
        'commission_admin' => 'Commission admin',
        'commission_integrator' => 'Commission intégrateur',
        'commission_operator' => 'Commission opérateur',
        'commission_partner' => 'Commission partenaire',
        
        // Transactions hiérarchiques
        'hierarchical_admin_integrator' => 'Admin → Intégrateur',
        'hierarchical_integrator_operator' => 'Intégrateur → Opérateur',
        'hierarchical_operator_partner' => 'Opérateur → Partenaire',
        
        // Frais
        'fee_activation' => "Frais d'activation",
        'fee_admin' => 'Frais admin',
        'fee_integrator' => 'Frais intégrateur',
        'fee_late_payment' => 'Frais de retard',
        
        // Réservation
        'reservation_payment' => 'Paiement réservation',
        'reservation_refund' => 'Remboursement réservation',
        'reservation_cancel' => 'Annulation réservation',
    ],
    
    // Statuts de collecte
    'collect_status' => [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'collected' => 'Collecté',
        'failed' => 'Échoué',
        'refunded' => 'Remboursé',
    ],
    
    // Statuts de transaction
    'transaction_status' => [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'completed' => 'Terminé',
        'failed' => 'Échoué',
        'canceled' => 'Annulé',
        'refunded' => 'Remboursé',
    ],
];