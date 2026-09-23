<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration des Paiements
    |--------------------------------------------------------------------------
    |
    | Configuration pour les différents fournisseurs de paiement
    | CMI (Maroc) et Stripe (International)
    |
    */

    'cmi' => [
        'base_url' => env('CMI_BASE_URL', 'https://testpayment.cmi.co.ma'),
        'payment_url' => env('CMI_PAYMENT_URL', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
        'store_key' => env('CMI_STOREKEY', env('CMI_STORE_KEY')),
        'store_password' => env('CMI_STORE_PASSWORD'),
        'client_id' => env('CMI_CLIENTID', env('CMI_CLIENT_ID', '600002823')),
        'username' => env('CMI_USERNAME'),
        'password' => env('CMI_PASSWORD'),
        'currency' => env('CMI_DEFAULT_CURRENCY', env('CMI_CURRENCY', 'MAD')),
        'test_mode' => env('CMI_TEST_MODE', true),
        'timeout' => env('CMI_TIMEOUT', 30),
        'storetype' => env('CMI_STORETYPE', '3D_PAY_HOSTING'),
        'hash_algorithm' => 'ver3',
    ],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'eur'),
        'test_mode' => env('STRIPE_TEST_MODE', true),
        'timeout' => env('STRIPE_TIMEOUT', 30),
    ],

    'default_method' => env('DEFAULT_PAYMENT_METHOD', 'cmi'),
    'supported_methods' => ['cmi', 'stripe', 'bank_transfer', 'cash'],
    
    'webhook_urls' => [
        'cmi_success' => env('APP_URL') . '/api/payments/cmi/success',
        'cmi_failure' => env('APP_URL') . '/api/payments/cmi/failure',
        'stripe_webhook' => env('APP_URL') . '/api/payments/stripe/webhook',
    ],

    'security' => [
        'verify_signatures' => env('PAYMENT_VERIFY_SIGNATURES', true),
        'allowed_ips' => env('PAYMENT_ALLOWED_IPS', ''),
        'timeout' => env('PAYMENT_TIMEOUT', 30),
        'max_retries' => env('PAYMENT_MAX_RETRIES', 3),
        'retry_delay' => env('PAYMENT_RETRY_DELAY', 1000), // millisecondes
    ],

    'general' => [
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 0.50),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 10000),
        'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'EUR'),
    ],
];