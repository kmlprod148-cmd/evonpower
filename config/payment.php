<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment gateways and methods
    |
    */

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'EUR'),

    'cmi' => [
        'base_url' => env('CMI_BASE_URL', 'https://test.cmi.co.ma/fim/est3Dgate'),
        'store_id' => env('CMI_STORE_ID'),
        'store_key' => env('CMI_STORE_KEY'),
        'is_active' => env('CMI_ACTIVE', true),
    ],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'is_active' => env('STRIPE_ACTIVE', true),
    ],

    'webhooks' => [
        'cmi' => [
            'url' => '/api/webhooks/cmi',
            'verify_signature' => true,
        ],
        'stripe' => [
            'url' => '/api/webhooks/stripe',
            'verify_signature' => true,
        ],
    ],

    'statuses' => [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'completed' => 'Terminé',
        'failed' => 'Échec',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
    ],

    'currencies' => [
        'EUR' => 'Euro',
        'USD' => 'US Dollar',
        'MAD' => 'Moroccan Dirham',
    ],
];
