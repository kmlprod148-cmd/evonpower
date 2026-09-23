<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

/**
 * Service pour récupérer les configurations de paiement depuis AdminSetting
 * et les synchroniser avec config/payments.php
 */
class PaymentConfigService
{
    /**
     * Récupère la configuration CMI depuis AdminSetting
     */
    public function getCmiConfig(): array
    {
        return Cache::remember('payment_config_cmi', 3600, function () {
            $defaultConfig = config('payments.cmi', []);
            
            return [
                'base_url' => $this->getSetting('cmi_base_url', $defaultConfig['base_url'] ?? 'https://testpayment.cmi.co.ma'),
                'store_key' => $this->getEncryptedSetting('cmi_store_key', $defaultConfig['store_key'] ?? ''),
                'store_password' => $this->getEncryptedSetting('cmi_store_password', $defaultConfig['store_password'] ?? ''),
                'client_id' => $this->getEncryptedSetting('cmi_client_id', $defaultConfig['client_id'] ?? ''),
                'username' => $this->getEncryptedSetting('cmi_username', $defaultConfig['username'] ?? ''),
                'password' => $this->getEncryptedSetting('cmi_password', $defaultConfig['password'] ?? ''),
                'currency' => $this->getSetting('cmi_currency', $defaultConfig['currency'] ?? 'EUR'),
                'test_mode' => $this->getBooleanSetting('cmi_test_mode', $defaultConfig['test_mode'] ?? true),
                'timeout' => (int) $this->getSetting('cmi_timeout', $defaultConfig['timeout'] ?? 30),
            ];
        });
    }

    /**
     * Récupère la configuration Stripe depuis AdminSetting
     */
    public function getStripeConfig(): array
    {
        return Cache::remember('payment_config_stripe', 3600, function () {
            $defaultConfig = config('payments.stripe', []);
            
            return [
                'public_key' => $this->getEncryptedSetting('stripe_public_key', $defaultConfig['public_key'] ?? ''),
                'secret_key' => $this->getEncryptedSetting('stripe_secret_key', $defaultConfig['secret_key'] ?? ''),
                'webhook_secret' => $this->getEncryptedSetting('stripe_webhook_secret', $defaultConfig['webhook_secret'] ?? ''),
                'currency' => $this->getSetting('stripe_currency', $defaultConfig['currency'] ?? 'eur'),
                'test_mode' => $this->getBooleanSetting('stripe_test_mode', $defaultConfig['test_mode'] ?? true),
                'timeout' => (int) $this->getSetting('stripe_timeout', $defaultConfig['timeout'] ?? 30),
            ];
        });
    }

    /**
     * Récupère les URLs de webhooks
     */
    public function getWebhookUrls(): array
    {
        return Cache::remember('payment_config_webhooks', 3600, function () {
            $defaultConfig = config('payments.webhook_urls', []);
            
            return [
                'cmi_success' => $this->getSetting('cmi_webhook_success_url', $defaultConfig['cmi_success'] ?? route('payment.cmi.success')),
                'cmi_failure' => $this->getSetting('cmi_webhook_failure_url', $defaultConfig['cmi_failure'] ?? route('payment.cmi.failure')),
                'stripe_webhook' => $this->getSetting('stripe_webhook_url', $defaultConfig['stripe_webhook'] ?? route('payment.stripe.webhook')),
            ];
        });
    }

    /**
     * Récupère les paramètres de sécurité
     */
    public function getSecurityConfig(): array
    {
        return Cache::remember('payment_config_security', 3600, function () {
            $defaultConfig = config('payments.security', []);
            
            return [
                'verify_signatures' => $this->getBooleanSetting('verify_signatures', $defaultConfig['verify_signatures'] ?? true),
                'allowed_ips' => $this->getSetting('allowed_ips', $defaultConfig['allowed_ips'] ?? ''),
                'timeout' => (int) $this->getSetting('payment_timeout', $defaultConfig['timeout'] ?? 30),
            ];
        });
    }

    /**
     * Récupère toutes les configurations de paiement
     */
    public function getAllConfig(): array
    {
        return [
            'cmi' => $this->getCmiConfig(),
            'stripe' => $this->getStripeConfig(),
            'webhook_urls' => $this->getWebhookUrls(),
            'security' => $this->getSecurityConfig(),
        ];
    }

    /**
     * Récupère un paramètre depuis AdminSetting
     */
    private function getSetting(string $key, $default = null)
    {
        $setting = AdminSetting::where('category', 'payment_processors')
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Récupère un paramètre crypté depuis AdminSetting
     */
    private function getEncryptedSetting(string $key, $default = null)
    {
        $setting = AdminSetting::where('category', 'payment_processors')
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if (!$setting || !$setting->value) {
            return $default;
        }

        try {
            return Crypt::decryptString($setting->value);
        } catch (\Exception $e) {
            // Si le décryptage échoue, retourner la valeur telle quelle (peut-être déjà en clair)
            return $setting->value;
        }
    }

    /**
     * Récupère un paramètre booléen depuis AdminSetting
     */
    private function getBooleanSetting(string $key, bool $default = false): bool
    {
        $value = $this->getSetting($key, $default ? '1' : '0');
        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Vide le cache des configurations
     */
    public function clearCache(): void
    {
        Cache::forget('payment_config_cmi');
        Cache::forget('payment_config_stripe');
        Cache::forget('payment_config_webhooks');
        Cache::forget('payment_config_security');
    }
}

