<?php

namespace App\Services;

use App\Models\CreditRecharge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service pour gérer les recharges de crédit via CMI
 * Utilise PaymentKeysService pour obtenir les configurations dynamiques
 */
class CMICreditRechargeService
{
    protected PaymentKeysService $paymentKeysService;
    protected array $cmiConfig;

    public function __construct(PaymentKeysService $paymentKeysService)
    {
        $this->paymentKeysService = $paymentKeysService;
        $this->loadCmiConfig();
    }

    /**
     * Charge la configuration CMI depuis PaymentKeysService
     */
    protected function loadCmiConfig(): void
    {
        try {
            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            
            if (empty($cmiKeys['storekey']) || empty($cmiKeys['clientid'])) {
                throw new \Exception('Les clés CMI ne sont pas configurées. Veuillez configurer les clés API dans les paramètres admin.');
            }

            $this->cmiConfig = [
                'store_key' => $cmiKeys['storekey'],
                'client_id' => $cmiKeys['clientid'],
                'api_url' => $cmiKeys['api_url'] ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'callback_url' => $cmiKeys['callback_url'] ?? config('app.url'),
                'environment' => $cmiKeys['environment'] ?? 'test',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement de la configuration CMI', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Prépare les données de paiement CMI pour une recharge
     * 
     * @param CreditRecharge $recharge
     * @param array $options
     * @return array
     */
    public function preparePaymentData(CreditRecharge $recharge, array $options = []): array
    {
        // Vérifier que la configuration est chargée
        if (empty($this->cmiConfig['store_key']) || empty($this->cmiConfig['client_id'])) {
            throw new \Exception('Configuration CMI incomplète. Veuillez configurer les clés API dans les paramètres admin.');
        }

        // Montant en centimes (CMI attend le montant en centimes)
        $amount = (int) round($recharge->amount * 100);
        
        // Générer un identifiant unique pour la transaction
        $rnd = time() . Str::random(6);
        
        // URLs de callback
        $baseUrl = $this->cmiConfig['callback_url'] ?? config('app.url');
        $okUrl = $baseUrl . '/credit-recharge/cmi/callback';
        $failUrl = $baseUrl . '/credit-recharge/cmi/callback';
        $callbackUrl = $baseUrl . '/credit-recharge/cmi/callback';

        // Préparer les données CMI
        $paymentData = [
            'clientid' => $this->cmiConfig['client_id'],
            'amount' => (string) $amount,
            'oid' => (string) $recharge->id, // Utiliser l'ID de la recharge comme order ID
            'okUrl' => $okUrl,
            'failUrl' => $failUrl,
            'callbackUrl' => $callbackUrl,
            'rnd' => $rnd,
            'storetype' => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'refreshtime' => '0',
            'lang' => 'fr',
            'email' => $recharge->user->email ?? '',
            'tel' => $recharge->user->phone ?? '',
            'BillToName' => $recharge->user->name ?? '',
            'currency' => $recharge->currency ?? 'EUR',
        ];

        // Générer le hash selon la documentation CMI
        $hashString = $paymentData['clientid'] . 
                     $paymentData['oid'] . 
                     $paymentData['amount'] . 
                     $paymentData['okUrl'] . 
                     $paymentData['failUrl'] . 
                     $paymentData['rnd'] . 
                     $this->cmiConfig['store_key'];
        
        $paymentData['HASH'] = base64_encode(pack('H*', sha1($hashString)));

        Log::info('Données de paiement CMI préparées', [
            'recharge_id' => $recharge->id,
            'amount' => $recharge->amount,
            'currency' => $recharge->currency,
            'environment' => $this->cmiConfig['environment']
        ]);

        return $paymentData;
    }

    /**
     * Retourne l'URL de paiement CMI
     * 
     * @return string
     */
    public function getPaymentUrl(): string
    {
        return $this->cmiConfig['api_url'] ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate';
    }

    /**
     * Vérifie la signature CMI d'un callback
     * 
     * @param array $callbackData
     * @return bool
     */
    public function verifyCallbackSignature(array $callbackData): bool
    {
        try {
            if (empty($callbackData['HASH']) || empty($callbackData['oid']) || empty($callbackData['amount'])) {
                return false;
            }

            // Reconstruire le hash selon la documentation CMI pour les callbacks
            $hashString = $callbackData['amount'] . 
                         $callbackData['oid'] . 
                         ($callbackData['Response'] ?? '') . 
                         $this->cmiConfig['store_key'];
            
            $expectedHash = base64_encode(pack('H*', sha1($hashString)));
            
            return hash_equals($expectedHash, $callbackData['HASH']);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de la signature CMI', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            return false;
        }
    }

    /**
     * Gère le callback serveur-à-serveur de CMI
     * 
     * @param array $callbackData
     * @return string
     */
    public function handleCallback(array $callbackData): string
    {
        try {
            // Vérifier la signature
            if (!$this->verifyCallbackSignature($callbackData)) {
                Log::warning('Signature CMI invalide dans le callback', [
                    'callback_data' => $callbackData
                ]);
                return 'FAILURE';
            }

            // Vérifier le code de retour
            $procReturnCode = $callbackData['ProcReturnCode'] ?? '';
            if ($procReturnCode === '00') {
                Log::info('CMI Callback: Paiement approuvé', [
                    'oid' => $callbackData['oid'] ?? null,
                    'trans_id' => $callbackData['TransId'] ?? null
                ]);
                return 'ACTION=POSTAUTH';
            } else {
                Log::warning('CMI Callback: Paiement refusé', [
                    'oid' => $callbackData['oid'] ?? null,
                    'proc_return_code' => $procReturnCode,
                    'error_message' => $callbackData['ErrMsg'] ?? 'Unknown error'
                ]);
                return 'FAILURE';
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du callback CMI', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            return 'FAILURE';
        }
    }

    /**
     * Gère la page de retour (Ok-Fail) de CMI
     * 
     * @param array $returnData
     * @return array
     */
    public function handleReturn(array $returnData): array
    {
        try {
            // Vérifier la signature
            $hashValid = $this->verifyCallbackSignature($returnData);
            
            // Vérifier si le paiement est approuvé
            $procReturnCode = $returnData['ProcReturnCode'] ?? '';
            $paymentApproved = $hashValid && ($procReturnCode === '00');
            
            return [
                'hash_valid' => $hashValid,
                'payment_approved' => $paymentApproved,
                'error_message' => $paymentApproved ? null : ($returnData['ErrMsg'] ?? 'Paiement échoué'),
                'trans_id' => $returnData['TransId'] ?? null,
                'amount' => $returnData['amount'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du retour CMI', [
                'error' => $e->getMessage(),
                'return_data' => $returnData
            ]);
            return [
                'hash_valid' => false,
                'payment_approved' => false,
                'error_message' => 'Erreur lors du traitement du retour',
            ];
        }
    }

    /**
     * Obtient la configuration CMI actuelle
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->cmiConfig;
    }
}

