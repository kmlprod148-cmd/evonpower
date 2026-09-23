<?php

return [
    'financial_transactions' => 'Financial Transactions',
    'id' => 'ID',
    'transaction' => 'Transaction',
    'revenue_share' => 'Revenue Share',
    'payer' => 'Payer',
    'payee' => 'Payee',
    'amount' => 'Amount',
    'type' => 'Type',
    'status' => 'Status',
    'description' => 'Description',
    'date' => 'Date',
    'not_applicable' => 'N/A',
    
    // Transaction Categories
    'categories' => [
        'recharge' => 'Recharge',
        'recharge_wallet' => 'E-Wallet',
        'abonnement' => 'Subscription',
        'commission' => 'Commission',
    ],
    
    // Transaction Types
    'types' => [
        // Recharges
        'recharge_credit' => 'Credit Recharge',
        'recharge_bank_transfer' => 'Bank Transfer',
        'recharge_cash' => 'Cash Payment',
        'recharge_card' => 'Card Payment',
        'recharge_cmi' => 'CMI Payment',
        
        // E-Wallet
        'wallet_deposit' => 'Wallet Deposit',
        'wallet_withdrawal' => 'Wallet Withdrawal',
        'wallet_transfer' => 'Wallet Transfer',
        'wallet_refund' => 'Wallet Refund',
        
        // Subscriptions
        'subscription_monthly' => 'Monthly Subscription',
        'subscription_yearly' => 'Yearly Subscription',
        'subscription_pack' => 'Recharge Pack',
        'subscription_renewal' => 'Subscription Renewal',
        
        // Charging Sessions
        'charging_session' => 'Charging Session',
        'charging_session_prepaid' => 'Prepaid Session',
        'charging_session_postpaid' => 'Postpaid Session',
        
        // Commissions
        'commission_admin' => 'Admin Commission',
        'commission_integrator' => 'Integrator Commission',
        'commission_operator' => 'Operator Commission',
        'commission_partner' => 'Partner Commission',
        
        // Hierarchical Transactions
        'hierarchical_admin_integrator' => 'Admin → Integrator',
        'hierarchical_integrator_operator' => 'Integrator → Operator',
        'hierarchical_operator_partner' => 'Operator → Partner',
        
        // Fees
        'fee_activation' => 'Activation Fee',
        'fee_admin' => 'Admin Fee',
        'fee_integrator' => 'Integrator Fee',
        'fee_late_payment' => 'Late Payment Fee',
        
        // Reservations
        'reservation_payment' => 'Reservation Payment',
        'reservation_refund' => 'Reservation Refund',
        'reservation_cancel' => 'Reservation Cancellation',
    ],
    
    // Collection Status
    'collect_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'collected' => 'Collected',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],
    
    // Transaction Status
    'transaction_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'canceled' => 'Canceled',
        'refunded' => 'Refunded',
    ],
];
