<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OCPP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le protocole OCPP (Open Charge Point Protocol)
    |
    */

    // URL de base pour les communications OCPP
    'base_url' => env('OCPP_BASE_URL', 'http://localhost:8080'),

    // Timeout pour les requêtes OCPP (en secondes)
    'timeout' => env('OCPP_TIMEOUT', 30),

    // Endpoints OCPP par défaut
    'endpoints' => [
        'default' => env('OCPP_DEFAULT_ENDPOINT', 'http://localhost:8080/ocpp'),
        // Vous pouvez ajouter des endpoints spécifiques par ID de borne
        // 1 => 'http://192.168.1.100:8080/ocpp',
        // 2 => 'http://192.168.1.101:8080/ocpp',
    ],

    // Authentification OCPP
    'auth' => [
        'username' => env('OCPP_USERNAME', 'admin'),
        'password' => env('OCPP_PASSWORD', 'password'),
    ],

    // Configuration des protocoles OCPP
    'protocols' => [
        'ocpp16' => [
            'port' => 8080,
            'version' => '1.6',
            'endpoint_suffix' => '/ocpp',
        ],
        'ocpp20' => [
            'port' => 8081,
            'version' => '2.0.1',
            'endpoint_suffix' => '/ocpp',
        ],
    ],

    // Configuration des retry en cas d'échec
    'retry' => [
        'max_attempts' => env('OCPP_MAX_RETRY_ATTEMPTS', 3),
        'delay_between_attempts' => env('OCPP_RETRY_DELAY', 2), // secondes
    ],

    // Configuration du monitoring
    'monitoring' => [
        'heartbeat_interval' => env('OCPP_HEARTBEAT_INTERVAL', 60), // secondes
        'connection_timeout' => env('OCPP_CONNECTION_TIMEOUT', 30), // secondes
    ],

    // Configuration des logs
    'logging' => [
        'enabled' => env('OCPP_LOGGING_ENABLED', true),
        'level' => env('OCPP_LOG_LEVEL', 'info'),
        'channel' => env('OCPP_LOG_CHANNEL', 'ocpp'),
    ],

    // Configuration des transactions
    'transactions' => [
        'max_duration' => env('OCPP_MAX_TRANSACTION_DURATION', 1440), // minutes (24h)
        'meter_value_interval' => env('OCPP_METER_VALUE_INTERVAL', 60), // secondes
    ],

    // Configuration des connecteurs
    'connectors' => [
        'default_power' => env('OCPP_DEFAULT_CONNECTOR_POWER', 22), // kW
        'max_power' => env('OCPP_MAX_CONNECTOR_POWER', 350), // kW
    ],
]; 