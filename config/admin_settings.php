<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Settings Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the admin settings for the application.
    | These settings can be managed through the admin panel.
    |
    */
    
    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    */
    'system' => [
        'app_name' => [
            'type' => 'text',
            'label' => 'Nom de l\'application',
            'value' => env('APP_NAME', 'EVON'),
            'description' => 'Le nom affiché de l\'application',
            'required' => true,
        ],
        'app_url' => [
            'type' => 'url',
            'label' => 'URL de l\'application',
            'value' => env('APP_URL', 'http://localhost'),
            'description' => 'L\'URL principale de l\'application',
            'required' => true,
        ],
        'timezone' => [
            'type' => 'select',
            'label' => 'Fuseau horaire',
            'value' => env('APP_TIMEZONE', 'Africa/Casablanca'),
            'options' => [
                'Africa/Casablanca' => 'Casablanca (UTC+1)',
                'Europe/Paris' => 'Paris (UTC+1)',
                'UTC' => 'UTC (UTC+0)',
                'America/New_York' => 'New York (UTC-5)',
            ],
            'description' => 'Fuseau horaire par défaut de l\'application',
            'required' => true,
        ],
        'maintenance_mode' => [
            'type' => 'boolean',
            'label' => 'Mode maintenance',
            'value' => false,
            'description' => 'Activer le mode maintenance',
            'required' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Processors Settings
    |--------------------------------------------------------------------------
    */
    'payment_processors' => [
        // CMI API Settings
        'cmi_api_key' => [
            'type' => 'password',
            'label' => 'Clé API CMI',
            'value' => '',
            'description' => 'Clé secrète fournie par CMI pour les paiements',
            'required' => true,
            'encrypted' => true,
        ],
        'cmi_merchant_id' => [
            'type' => 'text',
            'label' => 'ID du Marchand CMI',
            'value' => '',
            'description' => 'Identifiant unique de votre compte marchand CMI',
            'required' => true,
        ],
        'cmi_api_url' => [
            'type' => 'url',
            'label' => 'URL de l\'API CMI',
            'value' => 'https://testpayment.cmi.co.ma/fim/est3Dgate',
            'description' => 'URL de l\'endpoint de l\'API CMI',
            'required' => true,
        ],
        'cmi_environment' => [
            'type' => 'select',
            'label' => 'Environnement CMI',
            'value' => 'test',
            'options' => [
                'test' => 'Test',
                'production' => 'Production'
            ],
            'description' => 'Choisissez l\'environnement CMI',
            'required' => true,
        ],
        'cmi_currency' => [
            'type' => 'select',
            'label' => 'Devise CMI',
            'value' => 'EUR',
            'options' => [
                'EUR' => 'Euro',
                'USD' => 'Dollar américain'
            ],
            'description' => 'Devise utilisée pour les transactions CMI',
            'required' => true,
        ],
        'cmi_language' => [
            'type' => 'select',
            'label' => 'Langue CMI',
            'value' => 'fr',
            'options' => [
                'fr' => 'Français',
                'en' => 'English',
                'ar' => 'العربية'
            ],
            'description' => 'Langue par défaut pour l\'interface de paiement CMI',
            'required' => true,
        ],
        
        // Stripe API Settings
        'stripe_secret_key' => [
            'type' => 'password',
            'label' => 'Clé Secrète Stripe',
            'value' => '',
            'description' => 'Votre clé secrète Stripe (commence par sk_live_ ou sk_test_)',
            'required' => true,
            'encrypted' => true,
        ],
        'stripe_public_key' => [
            'type' => 'text',
            'label' => 'Clé Publique Stripe',
            'value' => '',
            'description' => 'Votre clé publique Stripe (commence par pk_live_ ou pk_test_)',
            'required' => true,
        ],
        'stripe_webhook_secret' => [
            'type' => 'password',
            'label' => 'Clé Secrète Webhook Stripe',
            'value' => '',
            'description' => 'La clé secrète de signature de votre webhook Stripe',
            'required' => false,
            'encrypted' => true,
        ],
        'stripe_environment' => [
            'type' => 'select',
            'label' => 'Environnement Stripe',
            'value' => 'test',
            'options' => [
                'test' => 'Test',
                'production' => 'Production'
            ],
            'description' => 'Choisissez l\'environnement Stripe',
            'required' => true,
        ],
        'stripe_currency' => [
            'type' => 'select',
            'label' => 'Devise Stripe',
            'value' => 'EUR',
            'options' => [
                'EUR' => 'Euro',
                'USD' => 'Dollar américain'
            ],
            'description' => 'Devise utilisée pour les transactions Stripe',
            'required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    */
    'currency' => [
        'app_default_currency' => [
            'type' => 'select',
            'label' => 'Devise par défaut de l\'application',
            'value' => 'EUR',
            'options' => [
                'EUR' => 'Euro',
                'USD' => 'Dollar américain'
            ],
            'description' => 'Devise par défaut pour toutes les transactions et affichages',
            'required' => true,
        ],
        'app_currency_symbol' => [
            'type' => 'text',
            'label' => 'Symbole de devise',
            'value' => '€',
            'description' => 'Symbole affiché pour la devise par défaut',
            'required' => true,
        ],
        'app_currency_format' => [
            'type' => 'select',
            'label' => 'Format de devise',
            'value' => '2',
            'options' => [
                '0' => 'Pas de décimales (ex: 100)',
                '1' => '1 décimale (ex: 100.0)',
                '2' => '2 décimales (ex: 100.00)'
            ],
            'description' => 'Nombre de décimales à afficher pour les montants',
            'required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Settings
    |--------------------------------------------------------------------------
    */
    'business' => [
        'company_name' => [
            'type' => 'text',
            'label' => 'Nom de l\'entreprise',
            'value' => 'EVON',
            'description' => 'Nom officiel de l\'entreprise',
            'required' => true,
        ],
        'company_email' => [
            'type' => 'email',
            'label' => 'Email de l\'entreprise',
            'value' => 'contact@evon.ma',
            'description' => 'Email de contact principal',
            'required' => true,
        ],
        'company_phone' => [
            'type' => 'tel',
            'label' => 'Téléphone de l\'entreprise',
            'value' => '+212 5XX XXX XXX',
            'description' => 'Numéro de téléphone principal',
            'required' => false,
        ],
        'company_address' => [
            'type' => 'textarea',
            'label' => 'Adresse de l\'entreprise',
            'value' => '',
            'description' => 'Adresse complète de l\'entreprise',
            'required' => false,
        ],
        'vat_number' => [
            'type' => 'text',
            'label' => 'Numéro de TVA',
            'value' => '',
            'description' => 'Numéro de TVA de l\'entreprise',
            'required' => false,
        ],
        'registration_number' => [
            'type' => 'text',
            'label' => 'Numéro d\'enregistrement',
            'value' => '',
            'description' => 'Numéro d\'enregistrement commercial',
            'required' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial Settings
    |--------------------------------------------------------------------------
    */
    'financial' => [
        'default_currency' => [
            'type' => 'select',
            'label' => 'Devise par défaut',
            'value' => 'EUR',
            'options' => [
                'EUR' => 'Euro (€)',
                'USD' => 'Dollar américain ($)',
                'GBP' => 'Livre sterling (£)',
            ],
            'description' => 'Devise par défaut pour les transactions',
            'required' => true,
        ],
        'vat_rate' => [
            'type' => 'number',
            'label' => 'Taux de TVA (%)',
            'value' => 20.0,
            'min' => 0,
            'max' => 100,
            'step' => 0.1,
            'description' => 'Taux de TVA en pourcentage',
            'required' => true,
        ],
        'commission_rate' => [
            'type' => 'number',
            'label' => 'Taux de commission (%)',
            'value' => 5.0,
            'min' => 0,
            'max' => 50,
            'step' => 0.1,
            'description' => 'Taux de commission par défaut',
            'required' => true,
        ],
        'minimum_transaction_amount' => [
            'type' => 'number',
            'label' => 'Montant minimum de transaction',
            'value' => 1.0,
            'min' => 0,
            'step' => 0.01,
            'description' => 'Montant minimum pour une transaction',
            'required' => true,
        ],
        'maximum_transaction_amount' => [
            'type' => 'number',
            'label' => 'Montant maximum de transaction',
            'value' => 10000.0,
            'min' => 0,
            'step' => 0.01,
            'description' => 'Montant maximum pour une transaction',
            'required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Charging Point Settings
    |--------------------------------------------------------------------------
    */
    'charging_points' => [
        'default_power_output' => [
            'type' => 'number',
            'label' => 'Puissance de sortie par défaut (kW)',
            'value' => 22.0,
            'min' => 1,
            'max' => 350,
            'step' => 0.1,
            'description' => 'Puissance de sortie par défaut des bornes',
            'required' => true,
        ],
        'reservation_duration' => [
            'type' => 'number',
            'label' => 'Durée de réservation (minutes)',
            'value' => 30,
            'min' => 5,
            'max' => 120,
            'step' => 5,
            'description' => 'Durée par défaut d\'une réservation',
            'required' => true,
        ],
        'session_timeout' => [
            'type' => 'number',
            'label' => 'Timeout de session (minutes)',
            'value' => 15,
            'min' => 5,
            'max' => 60,
            'step' => 1,
            'description' => 'Timeout pour les sessions de charge',
            'required' => true,
        ],
        'auto_start_charging' => [
            'type' => 'boolean',
            'label' => 'Démarrage automatique de la charge',
            'value' => true,
            'description' => 'Démarrer automatiquement la charge après connexion',
            'required' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'email_notifications' => [
            'type' => 'boolean',
            'label' => 'Notifications par email',
            'value' => true,
            'description' => 'Activer les notifications par email',
            'required' => false,
        ],
        'sms_notifications' => [
            'type' => 'boolean',
            'label' => 'Notifications par SMS',
            'value' => false,
            'description' => 'Activer les notifications par SMS',
            'required' => false,
        ],
        'push_notifications' => [
            'type' => 'boolean',
            'label' => 'Notifications push',
            'value' => true,
            'description' => 'Activer les notifications push',
            'required' => false,
        ],
        'notification_frequency' => [
            'type' => 'select',
            'label' => 'Fréquence des notifications',
            'value' => 'immediate',
            'options' => [
                'immediate' => 'Immédiate',
                'hourly' => 'Horaire',
                'daily' => 'Quotidienne',
                'weekly' => 'Hebdomadaire',
            ],
            'description' => 'Fréquence d\'envoi des notifications',
            'required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'session_lifetime' => [
            'type' => 'number',
            'label' => 'Durée de vie des sessions (minutes)',
            'value' => 120,
            'min' => 15,
            'max' => 1440,
            'step' => 15,
            'description' => 'Durée de vie des sessions utilisateur',
            'required' => true,
        ],
        'password_min_length' => [
            'type' => 'number',
            'label' => 'Longueur minimale des mots de passe',
            'value' => 8,
            'min' => 6,
            'max' => 32,
            'step' => 1,
            'description' => 'Longueur minimale requise pour les mots de passe',
            'required' => true,
        ],
        'two_factor_auth' => [
            'type' => 'boolean',
            'label' => 'Authentification à deux facteurs',
            'value' => false,
            'description' => 'Activer l\'authentification à deux facteurs',
            'required' => false,
        ],
        'ip_whitelist' => [
            'type' => 'textarea',
            'label' => 'Liste blanche IP',
            'value' => '',
            'description' => 'Adresses IP autorisées (une par ligne)',
            'required' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limit' => [
            'type' => 'number',
            'label' => 'Limite de taux API (requêtes/minute)',
            'value' => 60,
            'min' => 10,
            'max' => 1000,
            'step' => 10,
            'description' => 'Nombre maximum de requêtes API par minute',
            'required' => true,
        ],
        'api_timeout' => [
            'type' => 'number',
            'label' => 'Timeout API (secondes)',
            'value' => 30,
            'min' => 5,
            'max' => 300,
            'step' => 5,
            'description' => 'Timeout pour les requêtes API',
            'required' => true,
        ],
        'api_version' => [
            'type' => 'text',
            'label' => 'Version de l\'API',
            'value' => 'v1',
            'description' => 'Version actuelle de l\'API',
            'required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Keys Settings
    |--------------------------------------------------------------------------
    */
    'external_apis' => [
        'currency_api_key' => [
            'type' => 'password',
            'label' => 'Clé API des taux de change',
            'value' => '',
            'description' => 'Clé API pour récupérer les taux de change (ex: Fixer.io, ExchangeRate-API)',
            'required' => false,
            'placeholder' => 'Entrez votre clé API pour les taux de change',
        ],
        'currency_api_provider' => [
            'type' => 'select',
            'label' => 'Fournisseur de taux de change',
            'value' => 'fixer',
            'options' => [
                'fixer' => 'Fixer.io',
                'exchangerate' => 'ExchangeRate-API',
                'currencylayer' => 'CurrencyLayer',
                'exchangerate_api' => 'ExchangeRate-API',
            ],
            'description' => 'Service utilisé pour récupérer les taux de change',
            'required' => true,
        ],
        'currency_api_base_url' => [
            'type' => 'url',
            'label' => 'URL de base de l\'API des devises',
            'value' => 'https://api.fixer.io',
            'description' => 'URL de base pour l\'API des taux de change',
            'required' => false,
        ],
        'stripe_public_key' => [
            'type' => 'password',
            'label' => 'Clé publique Stripe',
            'value' => '',
            'description' => 'Clé publique Stripe pour les paiements',
            'required' => false,
            'placeholder' => 'pk_live_... ou pk_test_...',
        ],
        'stripe_secret_key' => [
            'type' => 'password',
            'label' => 'Clé secrète Stripe',
            'value' => '',
            'description' => 'Clé secrète Stripe pour les paiements',
            'required' => false,
            'placeholder' => 'sk_live_... ou sk_test_...',
        ],
        'stripe_webhook_secret' => [
            'type' => 'password',
            'label' => 'Secret webhook Stripe',
            'value' => '',
            'description' => 'Secret pour valider les webhooks Stripe',
            'required' => false,
            'placeholder' => 'whsec_...',
        ],
        'cmi_store_key' => [
            'type' => 'password',
            'label' => 'Clé de magasin CMI',
            'value' => '',
            'description' => 'Clé de magasin pour CMI (Credit Mutuel International)',
            'required' => false,
            'placeholder' => 'Votre clé de magasin CMI',
        ],
        'cmi_client_id' => [
            'type' => 'password',
            'label' => 'ID client CMI',
            'value' => '',
            'description' => 'Identifiant client pour CMI',
            'required' => false,
            'placeholder' => 'Votre ID client CMI',
        ],
        'cmi_username' => [
            'type' => 'text',
            'label' => 'Nom d\'utilisateur CMI',
            'value' => '',
            'description' => 'Nom d\'utilisateur pour l\'API CMI',
            'required' => false,
            'placeholder' => 'Votre nom d\'utilisateur CMI',
        ],
        'cmi_password' => [
            'type' => 'password',
            'label' => 'Mot de passe CMI',
            'value' => '',
            'description' => 'Mot de passe pour l\'API CMI',
            'required' => false,
            'placeholder' => 'Votre mot de passe CMI',
        ],
        'paypal_client_id' => [
            'type' => 'password',
            'label' => 'ID client PayPal',
            'value' => '',
            'description' => 'Identifiant client PayPal',
            'required' => false,
            'placeholder' => 'Votre ID client PayPal',
        ],
        'paypal_secret' => [
            'type' => 'password',
            'label' => 'Secret PayPal',
            'value' => '',
            'description' => 'Secret client PayPal',
            'required' => false,
            'placeholder' => 'Votre secret PayPal',
        ],
        'aws_access_key_id' => [
            'type' => 'password',
            'label' => 'Clé d\'accès AWS',
            'value' => '',
            'description' => 'Clé d\'accès AWS pour les services cloud',
            'required' => false,
            'placeholder' => 'AKIA...',
        ],
        'aws_secret_access_key' => [
            'type' => 'password',
            'label' => 'Clé secrète AWS',
            'value' => '',
            'description' => 'Clé secrète AWS pour les services cloud',
            'required' => false,
            'placeholder' => 'Votre clé secrète AWS',
        ],
        'aws_region' => [
            'type' => 'text',
            'label' => 'Région AWS',
            'value' => 'eu-west-1',
            'description' => 'Région AWS par défaut',
            'required' => false,
            'placeholder' => 'eu-west-1',
        ],
        'sentry_dsn' => [
            'type' => 'password',
            'label' => 'DSN Sentry',
            'value' => '',
            'description' => 'DSN Sentry pour le monitoring des erreurs',
            'required' => false,
            'placeholder' => 'https://...@sentry.io/...',
        ],
        'google_maps_api_key' => [
            'type' => 'password',
            'label' => 'Clé API Google Maps',
            'value' => '',
            'description' => 'Clé API Google Maps pour la géolocalisation',
            'required' => false,
            'placeholder' => 'AIza...',
        ],
        'twilio_account_sid' => [
            'type' => 'password',
            'label' => 'SID compte Twilio',
            'value' => '',
            'description' => 'SID du compte Twilio pour les SMS',
            'required' => false,
            'placeholder' => 'AC...',
        ],
        'twilio_auth_token' => [
            'type' => 'password',
            'label' => 'Token d\'authentification Twilio',
            'value' => '',
            'description' => 'Token d\'authentification Twilio',
            'required' => false,
            'placeholder' => 'Votre token Twilio',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'cmi_enabled' => [
            'type' => 'boolean',
            'label' => 'Activer les paiements CMI',
            'value' => true,
            'description' => 'Activer/Désactiver les paiements via CMI (Maroc)',
            'required' => false,
        ],
        'stripe_enabled' => [
            'type' => 'boolean',
            'label' => 'Activer les paiements Stripe',
            'value' => true,
            'description' => 'Activer/Désactiver les paiements via Stripe (International)',
            'required' => false,
        ],
        'offline_enabled' => [
            'type' => 'boolean',
            'label' => 'Activer les paiements hors ligne',
            'value' => true,
            'description' => 'Activer/Désactiver les paiements hors ligne (virement bancaire, espèces)',
            'required' => false,
        ],
        'bank_transfer_enabled' => [
            'type' => 'boolean',
            'label' => 'Activer les virements bancaires',
            'value' => true,
            'description' => 'Permettre les paiements par virement bancaire',
            'required' => false,
        ],
        'cash_enabled' => [
            'type' => 'boolean',
            'label' => 'Activer les paiements en espèces',
            'value' => true,
            'description' => 'Permettre les paiements en espèces sur place',
            'required' => false,
        ],
        'default_payment_method' => [
            'type' => 'select',
            'label' => 'Méthode de paiement par défaut',
            'value' => 'cmi',
            'options' => [
                'cmi' => 'CMI (Maroc)',
                'stripe' => 'Stripe (International)',
                'offline' => 'Hors ligne',
                'bank_transfer' => 'Virement bancaire',
                'cash' => 'Espèces'
            ],
            'description' => 'Méthode de paiement proposée par défaut',
            'required' => true,
        ],
        'offline_payment_instructions' => [
            'type' => 'textarea',
            'label' => 'Instructions pour paiements hors ligne',
            'value' => 'Veuillez contacter notre équipe pour finaliser le paiement hors ligne. Nos coordonnées bancaires vous seront communiquées.',
            'description' => 'Instructions affichées pour les paiements hors ligne',
            'required' => false,
        ],
        'bank_details' => [
            'type' => 'textarea',
            'label' => 'Coordonnées bancaires',
            'value' => '',
            'description' => 'Informations bancaires pour les virements (RIB, IBAN, etc.)',
            'required' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'auto_backup' => [
            'type' => 'boolean',
            'label' => 'Sauvegarde automatique',
            'value' => true,
            'description' => 'Activer les sauvegardes automatiques',
            'required' => false,
        ],
        'backup_frequency' => [
            'type' => 'select',
            'label' => 'Fréquence de sauvegarde',
            'value' => 'daily',
            'options' => [
                'hourly' => 'Horaire',
                'daily' => 'Quotidienne',
                'weekly' => 'Hebdomadaire',
                'monthly' => 'Mensuelle',
            ],
            'description' => 'Fréquence des sauvegardes automatiques',
            'required' => true,
        ],
        'backup_retention' => [
            'type' => 'number',
            'label' => 'Rétention des sauvegardes (jours)',
            'value' => 30,
            'min' => 1,
            'max' => 365,
            'step' => 1,
            'description' => 'Nombre de jours de rétention des sauvegardes',
            'required' => true,
        ],
    ],
];
