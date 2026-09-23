<?php

namespace App\Services;

use App\Models\AdminSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Service pour la gestion de l'API CMI
 */
class CmiApiService
{
    /**
     * Obtient la configuration CMI
     */
    public function getConfig(?string $environment = null): array
    {
        $settings = AdminSetting::where('category', 'external_apis')
            ->whereIn('key', [
                // Test keys
                'cmi_test_api_key',
                'cmi_test_merchant_id',
                'cmi_test_api_url',
                // Prod keys
                'cmi_prod_api_key',
                'cmi_prod_merchant_id',
                'cmi_prod_api_url',
                // Common settings
                'cmi_environment',
                'cmi_currency',
                'cmi_language'
            ])
            ->get()
            ->keyBy('key');

        // Déterminer l'environnement à utiliser
        $activeEnvironment = $environment ?? $settings->get('cmi_environment')?->value ?? 'test';
        
        // Utiliser les clés selon l'environnement
        if ($activeEnvironment === 'prod') {
            $apiKey = $settings->get('cmi_prod_api_key')?->value ? 
                Crypt::decryptString($settings->get('cmi_prod_api_key')->value) : '';
            $merchantId = $settings->get('cmi_prod_merchant_id')?->value ?? '';
            $apiUrl = $settings->get('cmi_prod_api_url')?->value ?? 'https://payment.cmi.co.ma/fim/est3Dgate';
        } else {
            $apiKey = $settings->get('cmi_test_api_key')?->value ? 
                Crypt::decryptString($settings->get('cmi_test_api_key')->value) : '';
            $merchantId = $settings->get('cmi_test_merchant_id')?->value ?? '';
            $apiUrl = $settings->get('cmi_test_api_url')?->value ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate';
        }

        return [
            'api_key' => $apiKey,
            'merchant_id' => $merchantId,
            'api_url' => $apiUrl,
            'environment' => $activeEnvironment,
            'currency' => $settings->get('cmi_currency')?->value ?? 'EUR',
            'language' => $settings->get('cmi_language')?->value ?? 'fr'
        ];
    }

    /**
     * Vérifie si la configuration CMI est complète
     */
    public function isConfigured(): bool
    {
        $config = $this->getConfig();
        
        return !empty($config['api_key']) && 
               !empty($config['merchant_id']) && 
               !empty($config['api_url']);
    }

    /**
     * Génère une signature pour l'API CMI
     */
    public function generateSignature(array $data): string
    {
        $config = $this->getConfig();
        $apiKey = $config['api_key'];
        
        // Tri des données par clé
        ksort($data);
        
        // Concaténation des valeurs
        $concatenated = implode('', $data);
        
        // Ajout de la clé API
        $concatenated .= $apiKey;
        
        // Génération du hash SHA256
        return hash('sha256', $concatenated);
    }

    /**
     * Valide une signature CMI
     */
    public function validateSignature(array $data, string $signature): bool
    {
        $expectedSignature = $this->generateSignature($data);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Crée une requête de paiement CMI
     */
    public function createPaymentRequest(array $paymentData): array
    {
        $config = $this->getConfig();
        
        if (!$this->isConfigured()) {
            throw new \Exception('Configuration CMI incomplète');
        }

        $requestData = [
            'clientid' => $config['merchant_id'],
            'amount' => $paymentData['amount'],
            'oid' => $paymentData['order_id'],
            'okUrl' => $paymentData['success_url'],
            'failUrl' => $paymentData['fail_url'],
            'rnd' => time(),
            'currency' => $config['currency'],
            'lang' => $config['language'],
            'email' => $paymentData['email'] ?? '',
            'tel' => $paymentData['phone'] ?? '',
            'BillToName' => $paymentData['customer_name'] ?? '',
            'BillToStreet1' => $paymentData['address'] ?? '',
            'BillToCity' => $paymentData['city'] ?? '',
            'BillToStateProv' => $paymentData['state'] ?? '',
            'BillToPostalCode' => $paymentData['postal_code'] ?? '',
            'BillToCountry' => $paymentData['country'] ?? 'MA',
            'ShipToName' => $paymentData['customer_name'] ?? '',
            'ShipToStreet1' => $paymentData['address'] ?? '',
            'ShipToCity' => $paymentData['city'] ?? '',
            'ShipToStateProv' => $paymentData['state'] ?? '',
            'ShipToPostalCode' => $paymentData['postal_code'] ?? '',
            'ShipToCountry' => $paymentData['country'] ?? 'MA',
        ];

        // Génération de la signature
        $requestData['hash'] = $this->generateSignature($requestData);

        return [
            'url' => $config['api_url'],
            'data' => $requestData
        ];
    }

    /**
     * Traite la réponse de l'API CMI
     */
    public function processResponse(array $responseData): array
    {
        $config = $this->getConfig();
        
        // Vérification de la signature
        $receivedSignature = $responseData['HASH'] ?? '';
        unset($responseData['HASH']);
        
        if (!$this->validateSignature($responseData, $receivedSignature)) {
            throw new \Exception('Signature CMI invalide');
        }

        return [
            'success' => $responseData['ProcReturnCode'] === '00',
            'transaction_id' => $responseData['TransId'] ?? '',
            'order_id' => $responseData['oid'] ?? '',
            'amount' => $responseData['amount'] ?? '',
            'currency' => $responseData['currency'] ?? '',
            'status' => $responseData['Response'] ?? '',
            'error_message' => $responseData['ErrMsg'] ?? '',
            'raw_response' => $responseData
        ];
    }

    /**
     * Teste la connexion à l'API CMI
     */
    public function testConnection(): array
    {
        $config = $this->getConfig();
        
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Configuration CMI incomplète',
                'details' => 'Veuillez configurer tous les paramètres CMI requis'
            ];
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['api_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'message' => 'Impossible de se connecter à l\'API CMI',
                    'details' => $error
                ];
            }
            
            if ($httpCode >= 200 && $httpCode < 400) {
                return [
                    'success' => true,
                    'message' => 'Connexion à l\'API CMI réussie',
                    'details' => "Code HTTP: {$httpCode}"
                ];
            }
            
            return [
                'success' => false,
                'message' => 'L\'API CMI a retourné une erreur',
                'details' => "Code HTTP: {$httpCode}"
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du test de connexion CMI: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors du test de connexion',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtient les statistiques de paiement
     */
    public function getPaymentStats(): array
    {
        // Cette méthode pourrait être étendue pour récupérer des statistiques
        // depuis l'API CMI ou depuis la base de données locale
        return [
            'total_payments' => 0,
            'successful_payments' => 0,
            'failed_payments' => 0,
            'total_amount' => 0
        ];
    }
}
