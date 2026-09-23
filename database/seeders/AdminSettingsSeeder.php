<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSystemSettings();
        $this->seedBusinessSettings();
        $this->seedFinancialSettings();
        $this->seedChargingPointSettings();
        $this->seedNotificationSettings();
        $this->seedSecuritySettings();
        $this->seedApiSettings();
        $this->seedExternalApisSettings();
        $this->seedBackupSettings();
    }

    /**
     * Initialise les paramètres système
     */
    private function seedSystemSettings(): void
    {
        $settings = [
            'app_name' => 'EVON - Système de Gestion des Bornes de Charge',
            'app_url' => 'http://localhost',
            'timezone' => 'Africa/Casablanca',
            'maintenance_mode' => false,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'system', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'maintenance_mode' ? 'boolean' : 'text',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres business
     */
    private function seedBusinessSettings(): void
    {
        $settings = [
            'company_name' => 'EVON',
            'company_email' => 'contact@evon.ma',
            'company_phone' => '+212 5XX XXX XXX',
            'company_address' => '',
            'vat_number' => '',
            'registration_number' => '',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'business', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'company_email' ? 'email' : 'text',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres financiers
     */
    private function seedFinancialSettings(): void
    {
        $settings = [
            'default_currency' => 'EUR',
            'vat_rate' => 20.0,
            'commission_rate' => 5.0,
            'minimum_transaction_amount' => 1.0,
            'maximum_transaction_amount' => 10000.0,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'financial', 'key' => $key],
                [
                    'value' => $value,
                    'type' => 'number',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres des bornes de charge
     */
    private function seedChargingPointSettings(): void
    {
        $settings = [
            'default_power_output' => 22.0,
            'reservation_duration' => 30,
            'session_timeout' => 15,
            'auto_start_charging' => true,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'charging_points', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'auto_start_charging' ? 'boolean' : 'number',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres de notification
     */
    private function seedNotificationSettings(): void
    {
        $settings = [
            'email_notifications' => true,
            'sms_notifications' => false,
            'push_notifications' => true,
            'notification_frequency' => 'immediate',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'notifications', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'notification_frequency' ? 'select' : 'boolean',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres de sécurité
     */
    private function seedSecuritySettings(): void
    {
        $settings = [
            'session_lifetime' => 120,
            'password_min_length' => 8,
            'two_factor_auth' => false,
            'ip_whitelist' => '',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'security', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'two_factor_auth' ? 'boolean' : 'number',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres API
     */
    private function seedApiSettings(): void
    {
        $settings = [
            'rate_limit' => 60,
            'api_timeout' => 30,
            'api_version' => 'v1',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'api', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'api_version' ? 'text' : 'number',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres des APIs externes
     */
    private function seedExternalApisSettings(): void
    {
        $settings = [
            'currency_api_key' => '',
            'currency_api_provider' => 'fixer',
            'currency_api_base_url' => 'https://api.fixer.io',
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'cmi_store_key' => '',
            'cmi_client_id' => '',
            'cmi_username' => '',
            'cmi_password' => '',
            'paypal_client_id' => '',
            'paypal_secret' => '',
            'aws_access_key_id' => '',
            'aws_secret_access_key' => '',
            'aws_region' => 'eu-west-1',
            'sentry_dsn' => '',
            'google_maps_api_key' => '',
            'twilio_account_sid' => '',
            'twilio_auth_token' => '',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'external_apis', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'currency_api_provider' ? 'select' : ($key === 'aws_region' ? 'text' : 'password'),
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Initialise les paramètres de sauvegarde
     */
    private function seedBackupSettings(): void
    {
        $settings = [
            'auto_backup' => true,
            'backup_frequency' => 'daily',
            'backup_retention' => 30,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\AdminSetting::updateOrCreate(
                ['category' => 'backup', 'key' => $key],
                [
                    'value' => $value,
                    'type' => $key === 'backup_frequency' ? 'select' : ($key === 'auto_backup' ? 'boolean' : 'number'),
                    'is_active' => true,
                ]
            );
        }
    }
}
