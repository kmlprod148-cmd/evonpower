<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Service pour la gestion de l'API Stripe
 */
class StripeApiService
{
    /**
     * Obtient la configuration Stripe
     */
    public function getConfig(?string $environment = null): array
    {
        $settings = AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                // Test keys
                'stripe_test_publishable_key',
                'stripe_test_secret_key',
                'stripe_test_webhook_secret',
                'stripe_test_webhook_url',
                // Prod keys
                'stripe_prod_publishable_key',
                'stripe_prod_secret_key',
                'stripe_prod_webhook_secret',
                'stripe_prod_webhook_url',
                // Common settings
                'stripe_environment',
                'stripe_currency'
            ])
            ->get()
            ->keyBy('key');

        // Déterminer l'environnement à utiliser
        $activeEnvironment = $environment ?? $settings->get('stripe_environment')?->value ?? 'test';
        
        // Utiliser les clés selon l'environnement
        if ($activeEnvironment === 'prod') {
            $publishableKey = $settings->get('stripe_prod_publishable_key')?->value ?? '';
            $secretKey = $settings->get('stripe_prod_secret_key')?->value ? 
                Crypt::decryptString($settings->get('stripe_prod_secret_key')->value) : '';
            $webhookSecret = $settings->get('stripe_prod_webhook_secret')?->value ? 
                Crypt::decryptString($settings->get('stripe_prod_webhook_secret')->value) : '';
            $webhookUrl = $settings->get('stripe_prod_webhook_url')?->value ?? '';
        } else {
            $publishableKey = $settings->get('stripe_test_publishable_key')?->value ?? '';
            $secretKey = $settings->get('stripe_test_secret_key')?->value ? 
                Crypt::decryptString($settings->get('stripe_test_secret_key')->value) : '';
            $webhookSecret = $settings->get('stripe_test_webhook_secret')?->value ? 
                Crypt::decryptString($settings->get('stripe_test_webhook_secret')->value) : '';
            $webhookUrl = $settings->get('stripe_test_webhook_url')?->value ?? '';
        }

        return [
            'publishable_key' => $publishableKey,
            'secret_key' => $secretKey,
            'webhook_secret' => $webhookSecret,
            'environment' => $activeEnvironment,
            'currency' => $settings->get('stripe_currency')?->value ?? 'EUR',
            'webhook_url' => $webhookUrl
        ];
    }

    /**
     * Vérifie si la configuration Stripe est complète
     */
    public function isConfigured(): bool
    {
        $config = $this->getConfig();
        
        return !empty($config['publishable_key']) && 
               !empty($config['secret_key']);
    }

    /**
     * Obtient la devise de l'application
     */
    public function getAppCurrency(): string
    {
        $setting = AdminSetting::where('category', 'system')
            ->where('key', 'app_default_currency')
            ->first();
            
        return $setting?->value ?? 'EUR';
    }

    /**
     * Obtient le symbole de la devise de l'application
     */
    public function getAppCurrencySymbol(): string
    {
        $setting = AdminSetting::where('category', 'system')
            ->where('key', 'app_currency_symbol')
            ->first();
            
        return $setting?->value ?? 'EUR';
    }

    /**
     * Crée un paiement Stripe
     */
    public function createPayment(array $paymentData): array
    {
        $config = $this->getConfig();
        
        if (!$this->isConfigured()) {
            throw new \Exception('Configuration Stripe incomplète');
        }

        $stripeData = [
            'amount' => $this->convertToStripeAmount($paymentData['amount'], $config['currency']),
            'currency' => strtolower($config['currency']),
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_id' => $paymentData['order_id'] ?? '',
                'customer_email' => $paymentData['email'] ?? '',
                'description' => $paymentData['description'] ?? ''
            ]
        ];

        if (isset($paymentData['customer_id'])) {
            $stripeData['customer'] = $paymentData['customer_id'];
        }

        if (isset($paymentData['success_url'])) {
            $stripeData['success_url'] = $paymentData['success_url'];
        }

        if (isset($paymentData['cancel_url'])) {
            $stripeData['cancel_url'] = $paymentData['cancel_url'];
        }

        return $this->makeStripeRequest('payment_intents', $stripeData);
    }

    /**
     * Récupère un paiement Stripe
     */
    public function retrievePayment(string $paymentIntentId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Configuration Stripe incomplète');
        }

        return $this->makeStripeRequest("payment_intents/{$paymentIntentId}", [], 'GET');
    }

    /**
     * Confirme un paiement Stripe
     */
    public function confirmPayment(string $paymentIntentId, array $data = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Configuration Stripe incomplète');
        }

        return $this->makeStripeRequest("payment_intents/{$paymentIntentId}/confirm", $data, 'POST');
    }

    /**
     * Annule un paiement Stripe
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Configuration Stripe incomplète');
        }

        return $this->makeStripeRequest("payment_intents/{$paymentIntentId}/cancel", [], 'POST');
    }

    /**
     * Crée un remboursement
     */
    public function createRefund(string $paymentIntentId, array $refundData = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Configuration Stripe incomplète');
        }

        $stripeData = array_merge([
            'payment_intent' => $paymentIntentId
        ], $refundData);

        return $this->makeStripeRequest('refunds', $stripeData);
    }

    /**
     * Valide un webhook Stripe
     */
    public function validateWebhook(string $payload, string $signature): bool
    {
        $config = $this->getConfig();
        
        if (empty($config['webhook_secret'])) {
            return false;
        }

        try {
            $expectedSignature = hash_hmac('sha256', $payload, $config['webhook_secret']);
            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation du webhook Stripe: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Traite un webhook Stripe
     */
    public function processWebhook(array $eventData): array
    {
        $eventType = $eventData['type'] ?? '';
        
        switch ($eventType) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($eventData);
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($eventData);
            case 'payment_intent.canceled':
                return $this->handlePaymentCanceled($eventData);
            default:
                return [
                    'success' => true,
                    'message' => 'Webhook traité avec succès',
                    'event_type' => $eventType
                ];
        }
    }

    /**
     * Teste la connexion à l'API Stripe
     */
    public function testConnection(): array
    {
        $config = $this->getConfig();
        
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Configuration Stripe incomplète',
                'details' => 'Veuillez configurer tous les paramètres Stripe requis'
            ];
        }

        try {
            $result = $this->makeStripeRequest('account', [], 'GET');
            
            if (isset($result['id'])) {
                return [
                    'success' => true,
                    'message' => 'Connexion à l\'API Stripe réussie',
                    'details' => "Compte: " . ($result['display_name'] ?? 'N/A') . " (ID: " . $result['id'] . ")"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Réponse inattendue de l\'API Stripe',
                    'details' => json_encode($result)
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion Stripe: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors du test de connexion',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Convertit un montant en centimes pour Stripe
     */
    private function convertToStripeAmount(float $amount, string $currency): int
    {
        // Stripe utilise des centimes pour la plupart des devises
        $zeroDecimalCurrencies = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
        
        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }
        
        return (int) ($amount * 100);
    }

    /**
     * Effectue une requête vers l'API Stripe
     */
    private function makeStripeRequest(string $endpoint, array $data = [], string $method = 'POST'): array
    {
        $config = $this->getConfig();
        $url = "https://api.stripe.com/v1/{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['secret_key'],
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("Erreur cURL: {$error}");
        }
        
        $response = json_decode($result, true);
        
        if ($httpCode >= 400) {
            $errorMessage = $response['error']['message'] ?? 'Erreur inconnue';
            throw new \Exception("Erreur Stripe ({$httpCode}): {$errorMessage}");
        }
        
        return $response;
    }

    /**
     * Gère un paiement réussi
     */
    private function handlePaymentSucceeded(array $eventData): array
    {
        $paymentIntent = $eventData['data']['object'] ?? [];
        
        Log::info('Paiement Stripe réussi', [
            'payment_intent_id' => $paymentIntent['id'] ?? '',
            'amount' => $paymentIntent['amount'] ?? 0,
            'currency' => $paymentIntent['currency'] ?? ''
        ]);
        
        return [
            'success' => true,
            'message' => 'Paiement traité avec succès',
            'payment_intent_id' => $paymentIntent['id'] ?? '',
            'amount' => $paymentIntent['amount'] ?? 0
        ];
    }

    /**
     * Gère un paiement échoué
     */
    private function handlePaymentFailed(array $eventData): array
    {
        $paymentIntent = $eventData['data']['object'] ?? [];
        
        Log::warning('Paiement Stripe échoué', [
            'payment_intent_id' => $paymentIntent['id'] ?? '',
            'last_payment_error' => $paymentIntent['last_payment_error'] ?? []
        ]);
        
        return [
            'success' => false,
            'message' => 'Paiement échoué',
            'payment_intent_id' => $paymentIntent['id'] ?? '',
            'error' => $paymentIntent['last_payment_error'] ?? []
        ];
    }

    /**
     * Gère un paiement annulé
     */
    private function handlePaymentCanceled(array $eventData): array
    {
        $paymentIntent = $eventData['data']['object'] ?? [];
        
        Log::info('Paiement Stripe annulé', [
            'payment_intent_id' => $paymentIntent['id'] ?? ''
        ]);
        
        return [
            'success' => true,
            'message' => 'Paiement annulé',
            'payment_intent_id' => $paymentIntent['id'] ?? ''
        ];
    }

    /**
     * Obtient les statistiques de paiement
     */
    public function getPaymentStats(): array
    {
        // Cette méthode pourrait être étendue pour récupérer des statistiques
        // depuis l'API Stripe ou depuis la base de données locale
        return [
            'total_payments' => 0,
            'successful_payments' => 0,
            'failed_payments' => 0,
            'total_amount' => 0
        ];
    }
}
