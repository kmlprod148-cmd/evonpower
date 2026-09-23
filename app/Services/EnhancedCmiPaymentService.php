<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service CMI amélioré pour le Maroc
 * 
 * Gestion sécurisée des paiements CMI avec callbacks sécurisés
 */
class EnhancedCmiPaymentService
{
    protected array $config;
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->config = config('payments.cmi', []);
        $this->currencyService = $currencyService;
    }

    /**
     * Initier un paiement CMI sécurisé
     */
    public function initiatePayment(array $paymentData): array
    {
        try {
            // Validation des données
            $this->validatePaymentData($paymentData);

            // Préparer les données CMI
            $cmiData = $this->prepareCmiData($paymentData);

            // Générer la signature de sécurité
            $cmiData['HASH'] = $this->generateSecureHash($cmiData);

            // Enregistrer la transaction en base
            $transaction = $this->createPaymentTransaction($cmiData);

            // Log de sécurité
            Log::info('CMI Payment initiated', [
                'transaction_id' => $transaction['id'],
                'amount' => $cmiData['amount'],
                'currency' => $cmiData['currency'],
                'order_id' => $cmiData['oid']
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction['id'],
                'payment_url' => $this->config['payment_url'],
                'form_data' => $cmiData,
                'redirect_url' => $this->buildRedirectUrl($cmiData)
            ];

        } catch (\Exception $e) {
            Log::error('CMI Payment initiation failed', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valider les données de paiement
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['amount', 'currency', 'order_id', 'customer_email'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ {$field} est requis");
            }
        }

        // Vérifier le montant minimum
        $minAmount = config('payments.general.min_amount', 1.0);
        if ($data['amount'] < $minAmount) {
            throw new \InvalidArgumentException("Le montant minimum est de {$minAmount} EUR");
        }

        // Vérifier le montant maximum
        $maxAmount = config('payments.general.max_amount', 10000.0);
        if ($data['amount'] > $maxAmount) {
            throw new \InvalidArgumentException("Le montant maximum est de {$maxAmount} EUR");
        }

        // Vérifier la devise supportée
        if (!$this->currencyService->isCurrencySupportedByPaymentMethod($data['currency'], 'cmi')) {
            throw new \InvalidArgumentException("La devise {$data['currency']} n'est pas supportée par CMI");
        }
    }

    /**
     * Préparer les données CMI
     */
    protected function prepareCmiData(array $paymentData): array
    {
        $rnd = microtime(true);
        $currency = strtoupper($paymentData['currency']);
        
        // Conversion de devise si nécessaire
        $amount = $paymentData['amount'];
        if ($currency !== 'EUR') {
            $conversion = $this->currencyService->getTotalWithConversionFees(
                $amount, 
                $currency, 
                'EUR'
            );
            $amount = $conversion['total_amount'];
        }

        return [
            'clientid' => $this->config['clientid'],
            'storetype' => $this->config['storetype'],
            'amount' => number_format($amount, 2, '.', ''),
            'oid' => $paymentData['order_id'],
            'okUrl' => $this->config['success_url'],
            'failUrl' => $this->config['failure_url'],
            'rnd' => $rnd,
            'currency' => $currency,
            'lang' => $this->config['default_language'],
            'email' => $paymentData['customer_email'],
            'tel' => $paymentData['customer_phone'] ?? '',
            'BillToName' => $paymentData['customer_name'] ?? '',
            'BillToCompany' => $paymentData['company_name'] ?? '',
            'BillToStreet1' => $paymentData['billing_address'] ?? '',
            'BillToCity' => $paymentData['billing_city'] ?? '',
            'BillToStateProv' => $paymentData['billing_state'] ?? '',
            'BillToPostalCode' => $paymentData['billing_postal_code'] ?? '',
            'BillToCountry' => $paymentData['billing_country'] ?? 'MA',
            'storekey' => $this->config['storekey']
        ];
    }

    /**
     * Générer une signature sécurisée
     */
    protected function generateSecureHash(array $data): string
    {
        $hashString = $data['storekey'] . 
                     $data['oid'] . 
                     $data['amount'] . 
                     $data['okUrl'] . 
                     $data['failUrl'] . 
                     $data['rnd'] . 
                     $data['currency'];
        
        return base64_encode(pack('H*', sha1($hashString)));
    }

    /**
     * Créer une transaction de paiement
     */
    protected function createPaymentTransaction(array $cmiData): array
    {
        $transactionId = 'CMI_' . $cmiData['oid'] . '_' . time();
        
        // Stocker temporairement les données
        Cache::put("cmi_payment_{$transactionId}", $cmiData, 3600); // 1 heure
        
        return [
            'id' => $transactionId,
            'order_id' => $cmiData['oid'],
            'amount' => $cmiData['amount'],
            'currency' => $cmiData['currency'],
            'status' => 'pending',
            'created_at' => now()
        ];
    }

    /**
     * Construire l'URL de redirection
     */
    protected function buildRedirectUrl(array $cmiData): string
    {
        $params = http_build_query($cmiData);
        return $this->config['payment_url'] . '?' . $params;
    }

    /**
     * Traiter le callback CMI (sécurisé)
     */
    public function handleCallback(array $callbackData): array
    {
        try {
            // Vérifier l'IP source
            if (!$this->isValidCallbackIp()) {
                throw new \Exception('IP non autorisée pour le callback CMI');
            }

            // Vérifier la signature
            if (!$this->verifyCallbackSignature($callbackData)) {
                throw new \Exception('Signature CMI invalide');
            }

            // Récupérer la transaction
            $transaction = $this->getTransactionByOrderId($callbackData['oid'] ?? '');
            if (!$transaction) {
                throw new \Exception('Transaction non trouvée');
            }

            // Traiter le statut
            $status = $this->processCallbackStatus($callbackData, $transaction);

            // Log de sécurité
            Log::info('CMI Callback processed', [
                'transaction_id' => $transaction['id'],
                'order_id' => $callbackData['oid'],
                'status' => $status,
                'amount' => $callbackData['amount'] ?? null
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction['id'],
                'status' => $status,
                'message' => $this->getStatusMessage($status)
            ];

        } catch (\Exception $e) {
            Log::error('CMI Callback processing failed', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier l'IP du callback
     */
    protected function isValidCallbackIp(): bool
    {
        $clientIp = request()->ip();
        $allowedIps = $this->config['ip_whitelist'] ?? [];

        foreach ($allowedIps as $ipRange) {
            if ($this->ipInRange($clientIp, $ipRange)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si une IP est dans une plage
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Vérifier la signature du callback
     */
    protected function verifyCallbackSignature(array $data): bool
    {
        if (!isset($data['HASH'])) {
            return false;
        }

        $receivedHash = $data['HASH'];
        unset($data['HASH']);

        $calculatedHash = $this->generateSecureHash($data);
        return hash_equals($calculatedHash, $receivedHash);
    }

    /**
     * Récupérer une transaction par order_id
     */
    protected function getTransactionByOrderId(string $orderId): ?array
    {
        // Récupérer depuis le cache ou la base de données
        $cacheKey = "cmi_payment_order_{$orderId}";
        return Cache::get($cacheKey);
    }

    /**
     * Traiter le statut du callback
     */
    protected function processCallbackStatus(array $callbackData, array $transaction): string
    {
        $status = $callbackData['Response'] ?? 'FAILURE';
        
        switch ($status) {
            case 'Approved':
                return 'success';
            case 'Declined':
                return 'declined';
            case 'Error':
                return 'error';
            default:
                return 'failed';
        }
    }

    /**
     * Obtenir le message de statut
     */
    protected function getStatusMessage(string $status): string
    {
        $messages = [
            'success' => 'Paiement effectué avec succès',
            'declined' => 'Paiement refusé par la banque',
            'error' => 'Erreur lors du traitement du paiement',
            'failed' => 'Échec du paiement'
        ];

        return $messages[$status] ?? 'Statut inconnu';
    }

    /**
     * Vérifier le statut d'une transaction
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        try {
            $transaction = Cache::get("cmi_payment_{$transactionId}");
            
            if (!$transaction) {
                throw new \Exception('Transaction non trouvée');
            }

            return [
                'success' => true,
                'transaction' => $transaction
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Annuler une transaction
     */
    public function cancelTransaction(string $transactionId): array
    {
        try {
            $transaction = Cache::get("cmi_payment_{$transactionId}");
            
            if (!$transaction) {
                throw new \Exception('Transaction non trouvée');
            }

            // Marquer comme annulée
            $transaction['status'] = 'cancelled';
            Cache::put("cmi_payment_{$transactionId}", $transaction, 3600);

            Log::info('CMI Transaction cancelled', [
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => true,
                'message' => 'Transaction annulée avec succès'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
