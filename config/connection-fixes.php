<?php

/**
 * Configuration des corrections de connexion serveur
 */

return [
    "auth" => [
        "test_credentials" => [
            "email" => "admin",
            "password" => "1234"
        ],
        "token_storage" => "localStorage", // localStorage, sessionStorage, ou cookie
        "auto_login" => true,
        "retry_count" => 3,
        "retry_delay" => 5000
    ],
    
    "notifications" => [
        "enabled" => true,
        "polling_interval" => 30000, // 30 secondes
        "max_retry_count" => 3,
        "timeout" => 10000
    ],
    
    "dataset" => [
        "safe_access" => true,
        "null_handling" => true,
        "error_logging" => true
    ],
    
    "query_selector" => [
        "safe_mode" => true,
        "error_handling" => true,
        "fallback_to_null" => true
    ]
];