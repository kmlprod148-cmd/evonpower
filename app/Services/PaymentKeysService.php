<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

/**
 * Service pour récupérer les clés API de paiement actives selon l'environnement
 * Utilise les clés test ou prod selon l'environnement sélectionné dans AdminSetting
 */
class PaymentKeysService
{
    /**
     * Récupère les clés Stripe actives selon l'environnement
     * 
     * @return array
     */
    public function getActiveStripeKeys(): array
    {
        return Cache::remember('active_stripe_keys', 3600, function () {
            $settings = AdminSetting::where('category', 'external_apis')
                ->whereIn('key', [
                    'stripe_test_publishable_key',
                    'stripe_test_secret_key',
                    'stripe_test_webhook_secret',
                    'stripe_prod_publishable_key',
                    'stripe_prod_secret_key',
                    'stripe_prod_webhook_secret',
                    'stripe_environment',
                ])
                ->get()
                ->keyBy('key');

            $environment = $settings->get('stripe_environment')?->value ?? 'test';

            if ($environment === 'prod') {
                return [
                    'publishable_key' => $settings->get('stripe_prod_publishable_key')?->value
                        ?? env('STRIPE_PUBLISHABLE_KEY') ?? env('STRIPE_KEY') ?? env('STRIPE_PUBLIC_KEY', ''),
                    'secret_key' => $this->getDecryptedValue($settings->get('stripe_prod_secret_key')?->value)
                        ?? env('STRIPE_SECRET_KEY') ?? env('STRIPE_SECRET', ''),
                    'webhook_secret' => $this->getDecryptedValue($settings->get('stripe_prod_webhook_secret')?->value)
                        ?? env('STRIPE_WEBHOOK_SECRET', ''),
                    'environment' => 'prod',
                    'currency' => env('STRIPE_CURRENCY', env('STRIPE_DEFAULT_CURRENCY', 'eur')),
                ];
            } else {
                return [
                    'publishable_key' => $settings->get('stripe_test_publishable_key')?->value
                        ?? env('STRIPE_PUBLISHABLE_KEY') ?? env('STRIPE_KEY') ?? env('STRIPE_PUBLIC_KEY', ''),
                    'secret_key' => $this->getDecryptedValue($settings->get('stripe_test_secret_key')?->value)
                        ?? env('STRIPE_SECRET_KEY') ?? env('STRIPE_SECRET', ''),
                    'webhook_secret' => $this->getDecryptedValue($settings->get('stripe_test_webhook_secret')?->value)
                        ?? env('STRIPE_WEBHOOK_SECRET', ''),
                    'environment' => 'test',
                    'currency' => env('STRIPE_CURRENCY', env('STRIPE_DEFAULT_CURRENCY', 'eur')),
                ];
            }
        });
    }

    /**
     * Récupère les clés CMI actives selon l'environnement
     * 
     * @return array
     */
    public function getActiveCmiKeys(): array
    {
        return Cache::remember('active_cmi_keys', 3600, function () {
            $settings = AdminSetting::where('category', 'external_apis')
                ->whereIn('key', [
                    'cmi_test_api_key',
                    'cmi_test_merchant_id',
                    'cmi_test_api_url',
                    'cmi_test_callback_url',
                    'cmi_prod_api_key',
                    'cmi_prod_merchant_id',
                    'cmi_prod_api_url',
                    'cmi_prod_callback_url',
                    'cmi_environment',
                ])
                ->get()
                ->keyBy('key');

            $environment = $settings->get('cmi_environment')?->value ?? 'test';

            if ($environment === 'prod') {
                return [
                    'storekey' => $this->getDecryptedValue($settings->get('cmi_prod_api_key')?->value) ?? env('CMI_STOREKEY', ''),
                    'clientid' => $settings->get('cmi_prod_merchant_id')?->value ?? env('CMI_CLIENTID', ''),
                    'api_url' => $settings->get('cmi_prod_api_url')?->value ?? env('CMI_PAYMENT_URL', 'https://payment.cmi.co.ma/fim/est3Dgate'),
                    'callback_url' => $settings->get('cmi_prod_callback_url')?->value ?? 'https://devcharge.evonpower.com', // URL de base pour les callbacks - utilise https://devcharge.evonpower.com par défaut
                    'environment' => 'prod',
                ];
            } else {
                return [
                    'storekey' => $this->getDecryptedValue($settings->get('cmi_test_api_key')?->value) ?? env('CMI_STOREKEY', ''),
                    'clientid' => $settings->get('cmi_test_merchant_id')?->value ?? env('CMI_CLIENTID', ''),
                    'api_url' => $settings->get('cmi_test_api_url')?->value ?? env('CMI_PAYMENT_URL', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
                    'callback_url' => $settings->get('cmi_test_callback_url')?->value ?? 'https://devcharge.evonpower.com', // URL de base pour les callbacks - utilise https://devcharge.evonpower.com par défaut
                    'environment' => 'test',
                ];
            }
        });
    }

    /**
     * Récupère une valeur décryptée si nécessaire
     * 
     * @param string|null $value
     * @return string|null
     */
    private function getDecryptedValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Si le décryptage échoue, retourner la valeur telle quelle (peut-être déjà en clair)
            return $value;
        }
    }

    /**
     * Vide le cache des clés
     */
    public function clearCache(): void
    {
        Cache::forget('active_stripe_keys');
        Cache::forget('active_cmi_keys');
    }
}

