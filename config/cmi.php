<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CMI Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for CMI (Credit Mutuel International) payment gateway
    | integration. These settings are used to connect with CMI's payment
    | processing system.
    |
    */

    // CMI Gateway Settings
    'storetype' => env('CMI_STORETYPE', '3d_pay_hosting'),
    'storekey' => env('CMI_STOREKEY'),
    'clientid' => env('CMI_CLIENTID'),
    'username' => env('CMI_USERNAME'),
    'password' => env('CMI_PASSWORD'),
    
    // CMI URLs
    'payment_url' => env('CMI_PAYMENT_URL', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
    'test_mode' => env('CMI_TEST_MODE', true),
    
    // Currency settings
    'default_currency' => env('CMI_DEFAULT_CURRENCY', 'MAD'),
    'supported_currencies' => ['MAD', 'EUR', 'USD'],
    
    // Language settings
    'default_language' => env('CMI_DEFAULT_LANGUAGE', 'fr'),
    'supported_languages' => ['fr', 'en', 'ar'],
    
    // Security settings
    'ip_whitelist' => [
        '195.8.31.0/24',
        '195.8.32.0/24',
        '195.8.33.0/24',
        '195.8.34.0/24',
        '195.8.35.0/24',
        '195.8.36.0/24',
        '195.8.37.0/24',
        '195.8.38.0/24',
        '195.8.39.0/24',
        '195.8.40.0/24',
    ],
    
    // Timeout settings
    'timeout' => env('CMI_TIMEOUT', 30),
    'connection_timeout' => env('CMI_CONNECTION_TIMEOUT', 10),
    
    // Logging
    'log_requests' => env('CMI_LOG_REQUESTS', true),
    'log_responses' => env('CMI_LOG_RESPONSES', true),
    
    // Callback URLs (will be set dynamically in routes)
    'success_url' => env('APP_URL') . '/payment/cmi/success',
    'failure_url' => env('APP_URL') . '/payment/cmi/failure',
    
    // Additional settings
    'auto_redirect' => env('CMI_AUTO_REDIRECT', true),
    'redirect_delay' => env('CMI_REDIRECT_DELAY', 5),
];