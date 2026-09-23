<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Steve API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Steve API integration
    |
    */

    // IMPORTANT: pas de valeurs par défaut "dangereuses" en production.
    // Configurez ces variables dans .env.
    // Bug #9: .env.example documents STEVE_API_USER / STEVE_API_PASSWORD while this
    // file used to read STEVE_USERNAME / STEVE_PASSWORD only — a silent mismatch.
    // Read both, preferring the documented .env.example names so a fresh install works.
    'api_url'  => env('STEVE_API_URL', env('STEVE_BASE_URL', '')),
    'username' => env('STEVE_API_USER', env('STEVE_USERNAME', '')),
    'password' => env('STEVE_API_PASSWORD', env('STEVE_PASSWORD', '')),
    'timeout' => env('STEVE_TIMEOUT', 30),
    'retry_attempts' => env('STEVE_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('STEVE_RETRY_DELAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Auto-provision on create
    |--------------------------------------------------------------------------
    |
    | When TRUE (default), creating a charging point through ChargingPointService
    | also POSTs it to SteVe in the same DB transaction. If SteVe fails the
    | local row is rolled back — the canonical atomicity contract.
    |
    | Set FALSE in environments where SteVe is intentionally unreachable
    | (local dev, staging without SteVe, or a deployment that wants to import
    | charging points and provision them later). The local row is persisted
    | and stamped `steve_connection_status.awaiting_sync = true`; an admin
    | can re-trigger provisioning via the existing `sync-steve` route.
    |
    */
    'auto_provision_on_create' => env('STEVE_AUTO_PROVISION_ON_CREATE', true),
    
    /*
    |--------------------------------------------------------------------------
    | Default OCPP Tag Configuration
    |--------------------------------------------------------------------------
    |
    | Le tag OCPP par défaut utilisé pour les opérations RemoteStart/Stop
    | Ce tag doit être configuré dans SteVe avec les autorisations appropriées
    |
    */
    
    'default_id_tag' => env('STEVE_DEFAULT_ID_TAG', 'Open10Tag'),
    
    /*
    |--------------------------------------------------------------------------
    | OCPP Endpoints Configuration
    |--------------------------------------------------------------------------
    |
    | Ces endpoints sont utilisés par les bornes de recharge pour communiquer
    | avec Steve via le protocole OCPP (Open Charge Point Protocol)
    |
    */
    
    // Endpoint SOAP pour OCPP (OCPP 1.5, OCPP 1.6)
    'ocpp_soap_endpoint' => env('STEVE_OCPP_SOAP_ENDPOINT', 'http://158.69.27.239:8180/steve/services/CentralSystemService'),
    
    // Endpoint WebSocket pour OCPP (OCPP 1.6 JSON, OCPP 2.0)
    // Format: ws://host:port/steve/websocket/CentralSystemService/{chargeBoxId}
    'ocpp_websocket_endpoint' => env('STEVE_OCPP_WEBSOCKET_ENDPOINT', 'ws://158.69.27.239:8180/steve/websocket/CentralSystemService/'),
    
    // Base URL pour la génération des endpoints OCPP
    'ocpp_base_url' => env('STEVE_OCPP_BASE_URL', 'http://158.69.27.239:8180'),
    
    /*
    |--------------------------------------------------------------------------
    | WebSocket Configuration (pour notre application)
    |--------------------------------------------------------------------------
    */
    
    'websocket_url' => env('STEVE_WEBSOCKET_URL', env('STEVE_OCPP_WEBSOCKET_ENDPOINT', 'ws://158.69.27.239:8180/steve/websocket/CentralSystemService/')),
    'websocket_timeout' => env('STEVE_WEBSOCKET_TIMEOUT', 30),
    
    /*
    |--------------------------------------------------------------------------
    | Auto Stop Configuration
    |--------------------------------------------------------------------------
    */
    
    'auto_stop_enabled' => env('STEVE_AUTO_STOP_ENABLED', true),
    'auto_stop_check_interval' => env('STEVE_AUTO_STOP_CHECK_INTERVAL', 60), // seconds
    'auto_stop_grace_period' => env('STEVE_AUTO_STOP_GRACE_PERIOD', 300), // seconds
    
    /*
    |--------------------------------------------------------------------------
    | Postpaid Payment (Steve as source of truth)
    |--------------------------------------------------------------------------
    */
    'postpaid_fetch_delay' => env('STEVE_POSTPAID_FETCH_DELAY', 3), // seconds after RemoteStop before fetching from Steve
    'postpaid_use_steve_data' => env('STEVE_POSTPAID_USE_STEVE_DATA', true), // use getTransaction/getMeterValues for consumption
    'postpaid_fetch_retries' => env('STEVE_POSTPAID_FETCH_RETRIES', 3), // retries for getTransaction/getMeterValues
    'postpaid_fetch_retry_delay' => env('STEVE_POSTPAID_FETCH_RETRY_DELAY', 500), // ms between retries
    'postpaid_sync_enabled' => env('STEVE_POSTPAID_SYNC_ENABLED', true), // SyncSteVePostpaidTransactionsJob
    'postpaid_sync_interval' => env('STEVE_POSTPAID_SYNC_INTERVAL', 2), // minutes between sync runs

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    
    'monitoring_enabled' => env('STEVE_MONITORING_ENABLED', true),
    'monitoring_check_interval' => env('STEVE_MONITORING_CHECK_INTERVAL', 300), // seconds
    'monitoring_alert_threshold' => env('STEVE_MONITORING_ALERT_THRESHOLD', 5), // failed attempts

    // Slice 3: the legacy `endpoints` array enumerated fictional SteVe paths
    // (e.g. `/api/v1/charging-sessions`) that don't exist in the 3.9.0
    // management REST spec and were never read by any code. Removed to avoid
    // misleading future contributors — the canonical surface is built at
    // request time by `SteVeHttpClientService::apiPath()` /
    // `SteveTransactionService::apiPath()`.
];
