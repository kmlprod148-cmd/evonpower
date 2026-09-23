<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration de la marque
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient tous les paramètres de personnalisation de la marque
    | qui peuvent être surchargés via les variables d'environnement.
    |
    */

    // Informations de base de la marque
    'name' => env('BRAND_NAME', 'EVON'),
    'display_name' => env('BRAND_DISPLAY_NAME', 'EVON Power'),
    'tagline' => env('BRAND_TAGLINE', 'Solutions de recharge intelligentes'),
    'description' => env('BRAND_DESCRIPTION', 'Plateforme de gestion de bornes de recharge électrique'),

    // Logos et images
    'logo' => env('BRAND_LOGO', 'default-logo.png'),
    'logo_dark' => env('BRAND_LOGO_DARK', 'default-logo-dark.png'),
    'favicon' => env('BRAND_FAVICON', 'favicon.ico'),
    'hero_image' => env('BRAND_HERO_IMAGE', 'hero-default.jpg'),

    // Couleurs principales
    'primary_color' => env('BRAND_PRIMARY_COLOR', '#10B981'),
    'secondary_color' => env('BRAND_SECONDARY_COLOR', '#047857'),
    'accent_color' => env('BRAND_ACCENT_COLOR', '#F59E0B'),
    'success_color' => env('BRAND_SUCCESS_COLOR', '#10B981'),
    'warning_color' => env('BRAND_WARNING_COLOR', '#F59E0B'),
    'error_color' => env('BRAND_ERROR_COLOR', '#EF4444'),
    'info_color' => env('BRAND_INFO_COLOR', '#3B82F6'),

    // Couleurs de fond
    'background_primary' => env('BRAND_BACKGROUND_PRIMARY', '#FFFFFF'),
    'background_secondary' => env('BRAND_BACKGROUND_SECONDARY', '#F9FAFB'),
    'background_dark' => env('BRAND_BACKGROUND_DARK', '#111827'),

    // Couleurs de texte
    'text_primary' => env('BRAND_TEXT_PRIMARY', '#111827'),
    'text_secondary' => env('BRAND_TEXT_SECONDARY', '#6B7280'),
    'text_light' => env('BRAND_TEXT_LIGHT', '#9CA3AF'),

    // Informations de contact
    'contact_email' => env('BRAND_CONTACT_EMAIL', 'contact@evon.com'),
    'contact_phone' => env('BRAND_CONTACT_PHONE', '+33 1 23 45 67 89'),
    'contact_address' => env('BRAND_CONTACT_ADDRESS', '123 Rue de la Paix, 75001 Paris'),
    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@evon.com'),

    // Réseaux sociaux
    'social_facebook' => env('BRAND_SOCIAL_FACEBOOK', ''),
    'social_twitter' => env('BRAND_SOCIAL_TWITTER', ''),
    'social_linkedin' => env('BRAND_SOCIAL_LINKEDIN', ''),
    'social_instagram' => env('BRAND_SOCIAL_INSTAGRAM', ''),

    // Informations légales
    'company_name' => env('BRAND_COMPANY_NAME', 'EVON SAS'),
    'company_siret' => env('BRAND_COMPANY_SIRET', '12345678901234'),
    'company_vat' => env('BRAND_COMPANY_VAT', 'FR12345678901'),
    'legal_notice' => env('BRAND_LEGAL_NOTICE', ''),
    'terms_of_service' => env('BRAND_TERMS_OF_SERVICE', ''),
    'privacy_policy' => env('BRAND_PRIVACY_POLICY', ''),

    // Configuration de l'interface
    'theme' => env('BRAND_THEME', 'light'), // light, dark, auto
    'language' => env('BRAND_LANGUAGE', 'fr'), // fr, en, ar
    'timezone' => env('BRAND_TIMEZONE', 'Europe/Paris'),
    'currency' => env('BRAND_CURRENCY', 'EUR'),
    'currency_symbol' => env('BRAND_CURRENCY_SYMBOL', '€'),

    // Configuration des fonctionnalités
    'enable_qr_codes' => env('BRAND_ENABLE_QR_CODES', true),
    'enable_notifications' => env('BRAND_ENABLE_NOTIFICATIONS', true),
    'enable_analytics' => env('BRAND_ENABLE_ANALYTICS', true),
    'enable_maintenance_mode' => env('BRAND_ENABLE_MAINTENANCE_MODE', false),

    // Configuration des paiements
    'payment_gateway' => env('BRAND_PAYMENT_GATEWAY', 'stripe'), // stripe, paypal, cmi
    'default_commission_rate' => env('BRAND_DEFAULT_COMMISSION_RATE', 0.15), // 15%
    'activation_fee' => env('BRAND_ACTIVATION_FEE', 0),

    // Configuration des bornes
    'default_charging_rate' => env('BRAND_DEFAULT_CHARGING_RATE', 0.25), // €/kWh
    'max_charging_power' => env('BRAND_MAX_CHARGING_POWER', 22), // kW
    'enable_smart_charging' => env('BRAND_ENABLE_SMART_CHARGING', true),

    // Configuration de l'email
    'email_from_name' => env('BRAND_EMAIL_FROM_NAME', 'EVON'),
    'email_from_address' => env('BRAND_EMAIL_FROM_ADDRESS', 'noreply@evon.com'),
    'email_signature' => env('BRAND_EMAIL_SIGNATURE', 'L\'équipe EVON'),

    // Configuration des notifications
    'notification_channels' => [
        'mail' => env('BRAND_NOTIFICATION_MAIL', true),
        'sms' => env('BRAND_NOTIFICATION_SMS', false),
        'push' => env('BRAND_NOTIFICATION_PUSH', false),
    ],

    // Configuration de la sécurité
    'session_lifetime' => env('BRAND_SESSION_LIFETIME', 120), // minutes
    'max_login_attempts' => env('BRAND_MAX_LOGIN_ATTEMPTS', 5),
    'password_expiry_days' => env('BRAND_PASSWORD_EXPIRY_DAYS', 90),

    // Configuration des métadonnées SEO
    'meta_title' => env('BRAND_META_TITLE', 'EVON - Solutions de recharge intelligentes'),
    'meta_description' => env('BRAND_META_DESCRIPTION', 'Découvrez nos solutions de recharge électrique innovantes'),
    'meta_keywords' => env('BRAND_META_KEYWORDS', 'recharge électrique, bornes, voiture électrique'),
    'meta_author' => env('BRAND_META_AUTHOR', 'EVON'),
    'meta_image' => env('BRAND_META_IMAGE', 'meta-default.jpg'),

    // Configuration des cookies
    'cookie_consent_required' => env('BRAND_COOKIE_CONSENT_REQUIRED', true),
    'cookie_policy_url' => env('BRAND_COOKIE_POLICY_URL', ''),
    'cookie_analytics' => env('BRAND_COOKIE_ANALYTICS', true),
    'cookie_marketing' => env('BRAND_COOKIE_MARKETING', false),

    // Configuration des intégrations
    'google_analytics_id' => env('BRAND_GOOGLE_ANALYTICS_ID', ''),
    'facebook_pixel_id' => env('BRAND_FACEBOOK_PIXEL_ID', ''),
    'hotjar_id' => env('BRAND_HOTJAR_ID', ''),
    'intercom_id' => env('BRAND_INTERCOM_ID', ''),

    // Configuration des webhooks
    'webhook_url' => env('BRAND_WEBHOOK_URL', ''),
    'webhook_secret' => env('BRAND_WEBHOOK_SECRET', ''),

    // Configuration des rapports
    'report_frequency' => env('BRAND_REPORT_FREQUENCY', 'weekly'), // daily, weekly, monthly
    'report_retention_days' => env('BRAND_REPORT_RETENTION_DAYS', 365),
    'enable_auto_reports' => env('BRAND_ENABLE_AUTO_REPORTS', true),

    // Configuration des sauvegardes
    'backup_frequency' => env('BRAND_BACKUP_FREQUENCY', 'daily'), // hourly, daily, weekly
    'backup_retention_days' => env('BRAND_BACKUP_RETENTION_DAYS', 30),
    'backup_storage' => env('BRAND_BACKUP_STORAGE', 'local'), // local, s3, ftp
];
