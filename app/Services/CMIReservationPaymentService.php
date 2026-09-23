<?php

namespace App\Services;

use App\Models\Reservation;
use App\Services\PaymentKeysService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service pour gérer les paiements de réservations via CMI
 * Utilise PaymentKeysService pour obtenir les configurations dynamiques
 */
class CMIReservationPaymentService
{
    protected PaymentKeysService $paymentKeysService;
    protected array $cmiConfig;
    
    /**
     * Mapping des codes de devises alphabétiques vers numériques (ISO 4217)
     */
    protected array $currencyMap = [
        'MAD' => '504',
        'EUR' => '978',
        'USD' => '840',
        'GBP' => '826',
    ];

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
                Log::warning('Les clés CMI ne sont pas configurées. Le service CMI sera désactivé.');
                $this->cmiConfig = [];
                return;
            }

            $this->cmiConfig = [
                'store_key' => $cmiKeys['storekey'],
                'client_id' => $cmiKeys['clientid'],
                'api_url' => $cmiKeys['api_url'] ?? 'https://testpayment.cmi.co.ma/fim/est3Dgate',
                'callback_url' => $cmiKeys['callback_url'] ?? config('app.url'),
                'environment' => $cmiKeys['environment'] ?? 'test',
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement de la configuration CMI pour réservation', [
                'error' => $e->getMessage()
            ]);
            $this->cmiConfig = [];
        }
    }

    /**
     * Prépare les données de paiement CMI pour une réservation
     * 
     * @param Reservation $reservation
     * @param array $options
     * @return array
     */
    public function preparePaymentData(Reservation $reservation, array $options = []): array
    {
        // Vérifier que la configuration est chargée
        if (empty($this->cmiConfig['store_key']) || empty($this->cmiConfig['client_id'])) {
            throw new \Exception('Configuration CMI incomplète. Veuillez configurer les clés API dans les paramètres admin.');
        }

        // Montant brut (CMI attend le montant en format décimal avec 2 décimales)
        $amountRaw = (float) ($reservation->estimated_cost ?? $reservation->amount ?? 0);
        
        // Générer un identifiant unique pour la transaction
        $rnd = time() . Str::random(6);
        
        // URLs de callback
        $baseUrl = $this->cmiConfig['callback_url'] ?? config('app.url');
        $okUrl = $baseUrl . '/payment/reservation/cmi/success';
        $failUrl = $baseUrl . '/payment/reservation/cmi/failure';
        $callbackUrl = $baseUrl . '/payment/reservation/cmi/callback';

        // Utiliser l'ID de la réservation comme order ID
        $oid = (string) $reservation->id;

        // Obtenir le code de devise (alphabétique -> numérique pour CMI)
        $currencyCode = $reservation->pricingPlan->currency ?? 'MAD';
        $currencyNumeric = $this->currencyMap[$currencyCode] ?? '504'; // Default to MAD
        
        // TranType pour les achats
        $tranType = 'PreAuth';
        
        // Préparer les données CMI selon la documentation officielle
        $paymentData = [
            'clientid' => $this->cmiConfig['client_id'],
            'amount' => number_format($amountRaw, 2, '.', ''), // Montant avec 2 décimales
            'oid' => $oid,
            'okUrl' => $okUrl,
            'failUrl' => $failUrl,
            'callbackUrl' => $callbackUrl,
            'TranType' => $tranType,
            'rnd' => $rnd,
            'storetype' => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'refreshtime' => '5',
            'lang' => 'fr',
            'encoding' => 'UTF-8',
            'email' => $reservation->user->email ?? '',
            'tel' => $reservation->user->phone ?? '',
            'BillToName' => $reservation->user->name ?? '',
            'currency' => $currencyNumeric,
        ];

        // Générer le hash selon la documentation CMI v3
        // L'ordre des champs pour le hash est crucial
        $hashFields = [
            $paymentData['clientid'],
            $paymentData['oid'],
            $paymentData['amount'],
            $paymentData['okUrl'],
            $paymentData['failUrl'],
            $paymentData['TranType'],
            $paymentData['rnd'],
            $paymentData['callbackUrl'],
            $paymentData['currency'],
            $paymentData['storetype'],
            $paymentData['hashAlgorithm'],
            $paymentData['lang'],
            $this->cmiConfig['store_key']
        ];
        
        $hashString = implode('|', $hashFields);
        $paymentData['HASH'] = base64_encode(hash('sha512', $hashString, true));

        Log::info('Données de paiement CMI préparées pour réservation', [
            'reservation_id' => $reservation->id,
            'oid' => $oid,
            'amount' => $paymentData['amount'],
            'currency_code' => $currencyCode,
            'currency_numeric' => $currencyNumeric,
            'okUrl' => $okUrl,
            'failUrl' => $failUrl,
            'callbackUrl' => $callbackUrl,
            'environment' => $this->cmiConfig['environment'],
            'client_id' => substr($this->cmiConfig['client_id'] ?? '', 0, 8) . '...'
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
            Log::error('Erreur lors de la vérification de la signature CMI pour réservation', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            return false;
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

