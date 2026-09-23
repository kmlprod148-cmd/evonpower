<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Exécuter le seeder.
     */
    public function run(): void
    {
        // Créer le client EVON par défaut
        Client::create([
            'name' => 'EVON',
            'display_name' => 'EVON Power',
            'slug' => 'evon',
            'domain' => config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'localhost',
            'subdomain' => null,
            
            // Logos et images
            'logo' => 'default-logo.png',
            'logo_dark' => 'default-logo-dark.png',
            'favicon' => 'favicon.ico',
            'hero_image' => 'hero-default.jpg',
            
            // Couleurs principales
            'primary_color' => '#10B981',
            'secondary_color' => '#047857',
            'accent_color' => '#F59E0B',
            'success_color' => '#10B981',
            'warning_color' => '#F59E0B',
            'error_color' => '#EF4444',
            'info_color' => '#3B82F6',
            
            // Couleurs de fond
            'background_primary' => '#FFFFFF',
            'background_secondary' => '#F9FAFB',
            'background_dark' => '#111827',
            
            // Couleurs de texte
            'text_primary' => '#111827',
            'text_secondary' => '#6B7280',
            'text_light' => '#9CA3AF',
            
            // Informations de contact
            'contact_email' => 'contact@evon.com',
            'contact_phone' => '+33 1 23 45 67 89',
            'contact_address' => '123 Rue de la Paix, 75001 Paris',
            'support_email' => 'support@evon.com',
            
            // Réseaux sociaux
            'social_facebook' => '',
            'social_twitter' => '',
            'social_linkedin' => '',
            'social_instagram' => '',
            
            // Informations légales
            'company_name' => 'EVON SAS',
            'company_siret' => '12345678901234',
            'company_vat' => 'FR12345678901',
            'legal_notice' => 'Mentions légales d\'EVON',
            'terms_of_service' => 'Conditions générales d\'utilisation d\'EVON',
            'privacy_policy' => 'Politique de confidentialité d\'EVON',
            
            // Configuration de l'interface
            'theme' => 'light',
            'language' => 'fr',
            'timezone' => 'Europe/Paris',
            'currency' => 'EUR',
            'currency_symbol' => '€',
            
            // Configuration des fonctionnalités
            'enable_qr_codes' => true,
            'enable_notifications' => true,
            'enable_analytics' => true,
            'enable_maintenance_mode' => false,
            
            // Configuration des paiements
            'payment_gateway' => 'stripe',
            'default_commission_rate' => 0.15,
            'activation_fee' => 0.00,
            
            // Configuration des bornes
            'default_charging_rate' => 0.25,
            'max_charging_power' => 22.00,
            'enable_smart_charging' => true,
            
            // Configuration de l'email
            'email_from_name' => 'EVON',
            'email_from_address' => 'noreply@evon.com',
            'email_signature' => 'L\'équipe EVON',
            
            // Configuration des notifications
            'notification_channels' => [
                'mail' => true,
                'sms' => false,
                'push' => false,
            ],
            
            // Configuration de la sécurité
            'session_lifetime' => 120,
            'max_login_attempts' => 5,
            'password_expiry_days' => 90,
            
            // Configuration des métadonnées SEO
            'meta_title' => 'EVON - Solutions de recharge intelligentes',
            'meta_description' => 'Découvrez nos solutions de recharge électrique innovantes',
            'meta_keywords' => 'recharge électrique, bornes, voiture électrique',
            'meta_author' => 'EVON',
            'meta_image' => 'meta-default.jpg',
            
            // Configuration des cookies
            'cookie_consent_required' => true,
            'cookie_policy_url' => '',
            'cookie_analytics' => true,
            'cookie_marketing' => false,
            
            // Configuration des intégrations
            'google_analytics_id' => '',
            'facebook_pixel_id' => '',
            'hotjar_id' => '',
            'intercom_id' => '',
            
            // Configuration des webhooks
            'webhook_url' => '',
            'webhook_secret' => Str::random(64),
            
            // Configuration des rapports
            'report_frequency' => 'weekly',
            'report_retention_days' => 365,
            'enable_auto_reports' => true,
            
            // Configuration des sauvegardes
            'backup_frequency' => 'daily',
            'backup_retention_days' => 30,
            'backup_storage' => 'local',
            
            // Statut et abonnement
            'is_active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
        ]);

        // Créer un client de démonstration
        Client::create([
            'name' => 'Demo',
            'display_name' => 'Demo Company',
            'slug' => 'demo',
            'domain' => 'demo.localhost',
            'subdomain' => 'demo',
            
            // Logos et images
            'logo' => 'demo-logo.png',
            'logo_dark' => 'demo-logo-dark.png',
            'favicon' => 'demo-favicon.ico',
            'hero_image' => 'demo-hero.jpg',
            
            // Couleurs principales
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1D4ED8',
            'accent_color' => '#F59E0B',
            'success_color' => '#10B981',
            'warning_color' => '#F59E0B',
            'error_color' => '#EF4444',
            'info_color' => '#06B6D4',
            
            // Couleurs de fond
            'background_primary' => '#FFFFFF',
            'background_secondary' => '#F8FAFC',
            'background_dark' => '#0F172A',
            
            // Couleurs de texte
            'text_primary' => '#0F172A',
            'text_secondary' => '#475569',
            'text_light' => '#94A3B8',
            
            // Informations de contact
            'contact_email' => 'contact@demo.com',
            'contact_phone' => '+33 1 98 76 54 32',
            'contact_address' => '456 Avenue des Champs, 75008 Paris',
            'support_email' => 'support@demo.com',
            
            // Réseaux sociaux
            'social_facebook' => 'https://facebook.com/demo',
            'social_twitter' => 'https://twitter.com/demo',
            'social_linkedin' => 'https://linkedin.com/company/demo',
            'social_instagram' => 'https://instagram.com/demo',
            
            // Informations légales
            'company_name' => 'Demo Company SAS',
            'company_siret' => '98765432109876',
            'company_vat' => 'FR98765432109',
            'legal_notice' => 'Mentions légales de Demo Company',
            'terms_of_service' => 'Conditions générales d\'utilisation de Demo Company',
            'privacy_policy' => 'Politique de confidentialité de Demo Company',
            
            // Configuration de l'interface
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'Europe/London',
            'currency' => 'USD',
            'currency_symbol' => '$',
            
            // Configuration des fonctionnalités
            'enable_qr_codes' => true,
            'enable_notifications' => true,
            'enable_analytics' => true,
            'enable_maintenance_mode' => false,
            
            // Configuration des paiements
            'payment_gateway' => 'paypal',
            'default_commission_rate' => 0.20,
            'activation_fee' => 10.00,
            
            // Configuration des bornes
            'default_charging_rate' => 0.30,
            'max_charging_power' => 50.00,
            'enable_smart_charging' => true,
            
            // Configuration de l'email
            'email_from_name' => 'Demo Company',
            'email_from_address' => 'noreply@demo.com',
            'email_signature' => 'L\'équipe Demo Company',
            
            // Configuration des notifications
            'notification_channels' => [
                'mail' => true,
                'sms' => true,
                'push' => true,
            ],
            
            // Configuration de la sécurité
            'session_lifetime' => 180,
            'max_login_attempts' => 3,
            'password_expiry_days' => 60,
            
            // Configuration des métadonnées SEO
            'meta_title' => 'Demo Company - Innovative Charging Solutions',
            'meta_description' => 'Discover our innovative electric charging solutions',
            'meta_keywords' => 'electric charging, stations, electric vehicle',
            'meta_author' => 'Demo Company',
            'meta_image' => 'demo-meta.jpg',
            
            // Configuration des cookies
            'cookie_consent_required' => true,
            'cookie_policy_url' => 'https://demo.com/cookies',
            'cookie_analytics' => true,
            'cookie_marketing' => true,
            
            // Configuration des intégrations
            'google_analytics_id' => 'GA-123456789',
            'facebook_pixel_id' => '123456789012345',
            'hotjar_id' => '1234567',
            'intercom_id' => 'demo123',
            
            // Configuration des webhooks
            'webhook_url' => 'https://demo.com/webhooks',
            'webhook_secret' => Str::random(64),
            
            // Configuration des rapports
            'report_frequency' => 'daily',
            'report_retention_days' => 180,
            'enable_auto_reports' => true,
            
            // Configuration des sauvegardes
            'backup_frequency' => 'hourly',
            'backup_retention_days' => 90,
            'backup_storage' => 's3',
            
            // Statut et abonnement
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
            'subscription_ends_at' => null,
        ]);

        $this->command->info('Clients créés avec succès !');
    }
}
