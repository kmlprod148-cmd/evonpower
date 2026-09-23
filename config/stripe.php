<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe payment gateway integration.
    | These settings are used to connect with Stripe's payment processing system.
    |
    */

    // Stripe API Keys - Utilise les clés actives depuis AdminSetting selon l'environnement
    // Les services de paiement doivent utiliser PaymentKeysService::getActiveStripeKeys()
    // pour récupérer les clés actives
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    
    // Stripe Settings
    'test_mode' => env('STRIPE_TEST_MODE', true),
    'api_version' => env('STRIPE_API_VERSION', '2023-10-16'),
    
    // Currency settings
    'default_currency' => env('STRIPE_DEFAULT_CURRENCY', 'eur'),
    'supported_currencies' => ['eur', 'usd'],
    
    // Payment method types
    'payment_method_types' => ['card'],
    'automatic_payment_methods' => [
        'enabled' => true,
        'allow_redirects' => 'never'
    ],
    
    // Webhook settings
    'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    'webhook_events' => [
        'checkout.session.completed',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'invoice.payment_succeeded',
        'invoice.payment_failed',
    ],
    
    // Timeout settings
    'timeout' => env('STRIPE_TIMEOUT', 30),
    'connection_timeout' => env('STRIPE_CONNECTION_TIMEOUT', 10),
    
    // Logging
    'log_requests' => env('STRIPE_LOG_REQUESTS', true),
    'log_responses' => env('STRIPE_LOG_RESPONSES', true),
    'log_webhooks' => env('STRIPE_LOG_WEBHOOKS', true),
    
    // Callback URLs (will be set dynamically in routes)
    'success_url' => env('APP_URL') . '/payment/stripe/success',
    'cancel_url' => env('APP_URL') . '/payment/stripe/cancel',
    
    // Additional settings
    'collect_billing_address' => env('STRIPE_COLLECT_BILLING_ADDRESS', false),
    'collect_shipping_address' => env('STRIPE_COLLECT_SHIPPING_ADDRESS', false),
    'allow_promotion_codes' => env('STRIPE_ALLOW_PROMOTION_CODES', false),
    
    // Customer settings
    'create_customer' => env('STRIPE_CREATE_CUSTOMER', true),
    'customer_metadata' => [
        'platform' => 'evonpower',
        'version' => '1.0.0'
    ],
    
    // Product settings
    'product_name' => env('STRIPE_PRODUCT_NAME', 'Réservation de borne de recharge'),
    'product_description' => env('STRIPE_PRODUCT_DESCRIPTION', 'Paiement pour réservation de borne de recharge EvonPower'),
    
    // Security settings
    'verify_signatures' => env('STRIPE_VERIFY_SIGNATURES', true),
    'allowed_origins' => [
        env('APP_URL'),
        'https://checkout.stripe.com',
    ],
];
