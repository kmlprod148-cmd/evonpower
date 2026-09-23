<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reservation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le système de réservation
    |
    */

    // Timeouts pour les requêtes de réservation
    'timeouts' => [
        'database' => env('RESERVATION_DB_TIMEOUT', 120), // 2 minutes
        'api_request' => env('RESERVATION_API_TIMEOUT', 30), // 30 secondes
        'payment_gateway' => env('RESERVATION_PAYMENT_TIMEOUT', 60), // 1 minute
    ],

    // Configuration des retry
    'retry' => [
        'max_attempts' => env('RESERVATION_MAX_RETRY', 3),
        'delay_between_attempts' => env('RESERVATION_RETRY_DELAY', 2), // secondes
        'exponential_backoff' => env('RESERVATION_EXPONENTIAL_BACKOFF', true),
    ],

    // Configuration des erreurs
    'error_handling' => [
        'log_database_errors' => env('RESERVATION_LOG_DB_ERRORS', true),
        'log_connection_errors' => env('RESERVATION_LOG_CONNECTION_ERRORS', true),
        'show_detailed_errors' => env('RESERVATION_SHOW_DETAILED_ERRORS', false),
    ],

    // Configuration des connexions
    'connections' => [
        'database_pool_size' => env('RESERVATION_DB_POOL_SIZE', 10),
        'enable_connection_pooling' => env('RESERVATION_ENABLE_DB_POOLING', false),
        'connection_keep_alive' => env('RESERVATION_DB_KEEP_ALIVE', true),
    ],

    // Configuration des notifications
    'notifications' => [
        'send_error_notifications' => env('RESERVATION_SEND_ERROR_NOTIFICATIONS', true),
        'admin_email' => env('RESERVATION_ADMIN_EMAIL', 'admin@evonpower.com'),
        'error_threshold' => env('RESERVATION_ERROR_THRESHOLD', 5), // Nombre d'erreurs avant notification
    ],
];
