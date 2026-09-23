<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\AdminConfigurationService;
use App\Services\OfflinePaymentService;
use App\Services\PaymentKeysService;
use App\Models\Commission;

/**
 * Service d'intégration des paiements en ligne
 * 
 * Ce service gère l'intégration avec CMI et Stripe
 * pour les paiements des transactions et réservations
 */
class PaymentIntegrationService
{
    protected array $cmiConfig;
    protected array $stripeConfig;
    protected array $paymentMethods;
    protected OfflinePaymentService $offlinePaymentService;
    protected PaymentKeysService $paymentKeysService;

    public function __construct(
        OfflinePaymentService $offlinePaymentService,
        PaymentKeysService $paymentKeysService
    ) {
        $this->offlinePaymentService = $offlinePaymentService;
        $this->paymentKeysService = $paymentKeysService;

        // Charger les configurations depuis PaymentKeysService (qui utilise AdminSetting)
        $this->loadPaymentConfigurations();

        $this->paymentMethods = [
            'cmi' => 'CMI (Maroc)',
            'stripe' => 'Stripe (International)',
            'bank_transfer' => 'Virement bancaire',
            'cash' => 'Espèces'
        ];
    }

    /**
     * Charge les configurations de paiement depuis PaymentKeysService
     */
    protected function loadPaymentConfigurations(): void
    {
        try {
            // Charger les clés CMI actives selon l'environnement
            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            $this->cmiConfig = [
                'store_key' => $cmiKeys['storekey'] ?? '',
                'client_id' => $cmiKeys['clientid'] ?? '',
                'api_url' => $cmiKeys['api_url'] ?? config('payments.cmi.base_url', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
                'callback_url' => $cmiKeys['callback_url'] ?? config('app.url'),
                'environment' => $cmiKeys['environment'] ?? 'test',
                'test_mode' => ($cmiKeys['environment'] ?? 'test') === 'test',
                'currency' => config('payments.cmi.currency', 'EUR'),
            ];

            // Charger les clés Stripe actives selon l'environnement
            $stripeKeys = $this->paymentKeysService->getActiveStripeKeys();
            $this->stripeConfig = [
                'publishable_key' => $stripeKeys['publishable_key'] ?? '',
                'secret_key' => $stripeKeys['secret_key'] ?? '',
                'webhook_secret' => $stripeKeys['webhook_secret'] ?? '',
                'environment' => $stripeKeys['environment'] ?? 'test',
                'test_mode' => ($stripeKeys['environment'] ?? 'test') === 'test',
                'currency' => config('payments.stripe.currency', 'eur'),
            ];
        } catch (\Exception $e) {
            Log::warning('Erreur lors du chargement des configurations de paiement', [
                'error' => $e->getMessage()
            ]);
            // Utiliser les configurations par défaut en cas d'erreur
            $this->cmiConfig = config('payments.cmi', []);
            $this->stripeConfig = config('payments.stripe', []);
        }
    }

    /**
     * Initialise un paiement CMI
     * 
     * @param Transaction $transaction
     * @param array $options
     * @return array
     */
    public function initiateCmiPayment(Transaction $transaction, array $options = []): array
    {
        try {
            $paymentData = [
                'clientid' => $this->cmiConfig['client_id'],
                'storetype' => '3D_PAY_HOSTING',
                'amount' => number_format($transaction->price_total, 2, '.', ''),
                'oid' => $transaction->id,
                'okUrl' => route('payment.cmi.success'),
                'failUrl' => route('payment.cmi.failure'),
                'rnd' => time(),
                'currency' => $this->cmiConfig['currency'],
                'lang' => $options['lang'] ?? 'fr',
                'email' => $transaction->user->email ?? '',
                'tel' => $transaction->user->phone ?? '',
                'BillToName' => $transaction->user->name ?? '',
                'BillToStreet1' => $transaction->user->address ?? '',
                'BillToCity' => $transaction->user->city ?? '',
                'BillToCountry' => $transaction->user->country ?? 'MA',
                'BillToStateProvince' => $transaction->user->city ?? '',
                'BillToPostalCode' => $transaction->user->postal_code ?? '',
                'description' => "Paiement transaction #{$transaction->id} - EVON"
            ];

            // Générer le hash de sécurité
            $hashString = $paymentData['clientid'] . $paymentData['oid'] . $paymentData['amount'] . 
                         $paymentData['okUrl'] . $paymentData['failUrl'] . $paymentData['rnd'] . 
                         $this->cmiConfig['store_password'];
            $paymentData['hash'] = base64_encode(pack('H*', sha1($hashString)));

            // Enregistrer la tentative de paiement
            $this->logPaymentAttempt($transaction, 'cmi', $paymentData);

            // Construire l'URL de paiement CMI de manière sécurisée
            $baseUrl = $this->cmiConfig['base_url'] ?? 'https://testpayment.cmi.co.ma';
            $baseUrl = rtrim($baseUrl, '/');
            
            // S'assurer que l'URL contient /fim/est3Dgate (requis par CMI)
            if (strpos($baseUrl, 'est3Dgate') === false) {
                if (strpos($baseUrl, '/fim/') === false) {
                    $baseUrl .= '/fim/est3Dgate';
                } else {
                    $baseUrl .= '/est3Dgate';
                }
            }
            
            // Vérifier qu'il n'y a pas de duplication
            $est3DgateCount = substr_count($baseUrl, 'est3Dgate');
            if ($est3DgateCount > 1) {
                $parts = explode('est3Dgate', $baseUrl, 2);
                $baseUrl = $parts[0] . 'est3Dgate';
            }
            
            return [
                'success' => true,
                'payment_method' => 'cmi',
                'payment_url' => $baseUrl,
                'payment_data' => $paymentData,
                'transaction_id' => $transaction->id
            ];

        } catch (\Exception $e) {
            Log::error('Erreur initialisation paiement CMI', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur initialisation paiement CMI: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initialise un paiement Stripe
     * 
     * @param Transaction $transaction
     * @param array $options
     * @return array
     */
    public function initiateStripePayment(Transaction $transaction, array $options = []): array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeConfig['secret_key']);

            // Créer une session de paiement Stripe
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $this->stripeConfig['currency'],
                        'product_data' => [
                            'name' => "Transaction #{$transaction->id}",
                            'description' => "Paiement transaction EVON - " . ($transaction->chargingPoint->name ?? 'Borne de charge')
                        ],
                        'unit_amount' => (int) ($transaction->price_total * 100) // Stripe utilise les centimes
                    ],
                    'quantity' => 1
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.stripe.cancel'),
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'user_id' => $transaction->user_id
                ]
            ]);

            // Enregistrer la tentative de paiement
            $this->logPaymentAttempt($transaction, 'stripe', [
                'session_id' => $session->id,
                'amount' => $transaction->price_total
            ]);

            return [
                'success' => true,
                'payment_method' => 'stripe',
                'session_id' => $session->id,
                'payment_url' => $session->url,
                'transaction_id' => $transaction->id
            ];

        } catch (\Exception $e) {
            Log::error('Erreur initialisation paiement Stripe', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur initialisation paiement Stripe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traite le callback de succès CMI
     * 
     * @param array $callbackData
     * @return array
     */
    public function handleCmiSuccessCallback(array $callbackData): array
    {
        try {
            // Vérifier la signature CMI
            if (!$this->verifyCmiSignature($callbackData)) {
                throw new \Exception('Signature CMI invalide');
            }

            $transactionId = $callbackData['oid'] ?? null;
            if (!$transactionId) {
                throw new \Exception('ID de transaction manquant');
            }

            $transaction = Transaction::find($transactionId);
            if (!$transaction) {
                throw new \Exception('Transaction non trouvée');
            }

            // Mettre à jour le statut de la transaction
            $transaction->update([
                'payment_status' => 'paid',
                'payment_method' => 'cmi',
                'payment_id' => $callbackData['TransId'] ?? null,
                'paid_at' => now()
            ]);

            // Traiter les commissions
            $this->processCommissions($transaction);

            Log::info('Paiement CMI traité avec succès', [
                'transaction_id' => $transactionId,
                'amount' => $callbackData['amount'] ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Paiement traité avec succès',
                'transaction_id' => $transactionId
            ];

        } catch (\Exception $e) {
            Log::error('Erreur traitement callback CMI', [
                'callback_data' => $callbackData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur traitement paiement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traite le webhook Stripe
     * 
     * @param array $webhookData
     * @return array
     */
    public function handleStripeWebhook(array $webhookData): array
    {
        try {
            $event = $webhookData['type'] ?? null;
            $sessionId = $webhookData['data']['object']['id'] ?? null;

            if ($event === 'checkout.session.completed' && $sessionId) {
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                $transactionId = $session->metadata['transaction_id'] ?? null;

                if ($transactionId) {
                    $transaction = Transaction::find($transactionId);
                    if ($transaction) {
                        $transaction->update([
                            'payment_status' => 'paid',
                            'payment_method' => 'stripe',
                            'payment_id' => $session->payment_intent,
                            'paid_at' => now()
                        ]);

                        // Traiter les commissions
                        $this->processCommissions($transaction);

                        Log::info('Paiement Stripe traité avec succès', [
                            'transaction_id' => $transactionId,
                            'session_id' => $sessionId
                        ]);
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Webhook Stripe traité'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur traitement webhook Stripe', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur traitement webhook: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifie la signature CMI
     * 
     * @param array $data
     * @return bool
     */
    private function verifyCmiSignature(array $data): bool
    {
        $hashString = $data['clientid'] . $data['oid'] . $data['amount'] . 
                     $data['okUrl'] . $data['failUrl'] . $data['rnd'] . 
                     $this->cmiConfig['store_password'];
        $expectedHash = base64_encode(pack('H*', sha1($hashString)));

        return hash_equals($expectedHash, $data['hash'] ?? '');
    }

    /**
     * Traite les commissions après paiement
     * 
     * @param Transaction $transaction
     */
    private function processCommissions(Transaction $transaction): void
    {
        try {
            // Utiliser le service de commission consolidé
            $commissionService = app(CommissionCalculationService::class);
            
            if ($transaction->businessProfile) {
                $commissions = $commissionService->calculateCommissions(
                    $transaction->businessProfile,
                    $transaction->price_total,
                    'corrected'
                );

                // Mettre à jour les commissions dans la transaction
                $transaction->update([
                    'admin_commission' => $commissions['admin_commission'],
                    'integrator_commission' => $commissions['integrator_commission'],
                    'partner_commission' => $commissions['operator_commission'] ?? 0
                ]);

                Log::info('Commissions traitées après paiement', [
                    'transaction_id' => $transaction->id,
                    'commissions' => $commissions
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur traitement commissions', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enregistre une tentative de paiement
     * 
     * @param Transaction $transaction
     * @param string $method
     * @param array $data
     */
    private function logPaymentAttempt(Transaction $transaction, string $method, array $data): void
    {
        Log::info('Tentative de paiement enregistrée', [
            'transaction_id' => $transaction->id,
            'payment_method' => $method,
            'amount' => $transaction->price_total,
            'data' => $data
        ]);
    }

    /**
     * Traite un paiement hors ligne
     * 
     * @param Transaction $transaction
     * @param string $paymentMethod
     * @param array $paymentData
     * @return array
     */
    public function processOfflinePayment(Transaction $transaction, string $paymentMethod, array $paymentData = []): array
    {
        return $this->offlinePaymentService->processOfflinePayment($transaction, $paymentMethod, $paymentData);
    }

    /**
     * Confirme un paiement hors ligne (admin seulement)
     * 
     * @param Order $order
     * @param array $confirmationData
     * @return array
     */
    public function confirmOfflinePayment(Order $order, array $confirmationData = []): array
    {
        return $this->offlinePaymentService->confirmOfflinePayment($order, $confirmationData);
    }

    /**
     * Annule un paiement hors ligne
     * 
     * @param Order $order
     * @param string $reason
     * @return array
     */
    public function cancelOfflinePayment(Order $order, string $reason = ''): array
    {
        return $this->offlinePaymentService->cancelOfflinePayment($order, $reason);
    }

    /**
     * Vérifie si une méthode de paiement hors ligne est activée
     * 
     * @param string $paymentMethod
     * @return bool
     */
    public function isOfflinePaymentEnabled(string $paymentMethod): bool
    {
        return $this->offlinePaymentService->isOfflinePaymentEnabled($paymentMethod);
    }

    /**
     * Obtient les méthodes de paiement disponibles
     * 
{{ ... }}
     */
    /**
     * Obtient les méthodes de paiement disponibles
     * 
     * IMPORTANT: Cette méthode retourne CMI et Stripe pour TOUS les utilisateurs authentifiés,
     * y compris les clients avec le rôle 'user', s'ils sont configurés et activés.
     * Aucune restriction basée sur les rôles n'est appliquée ici.
     * 
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        $adminSettings = app(AdminConfigurationService::class);
        
        // Recharger les configurations pour s'assurer d'avoir les dernières valeurs
        $this->loadPaymentConfigurations();

        // Vérifier si CMI est configuré et activé
        // Disponible pour TOUS les utilisateurs authentifiés (y compris les clients)
        $cmiEnabled = $adminSettings->getSetting('payments', 'cmi_enabled', true);
        $cmiConfigured = !empty($this->cmiConfig['store_key']) && !empty($this->cmiConfig['client_id']);
        
        // Vérifier si Stripe est configuré et activé
        // Disponible pour TOUS les utilisateurs authentifiés (y compris les clients)
        $stripeEnabled = $adminSettings->getSetting('payments', 'stripe_enabled', true);
        $stripeConfigured = !empty($this->stripeConfig['secret_key']) && !empty($this->stripeConfig['publishable_key']);

        // Vérifier si le mode hors ligne est activé pour CMI et Stripe
        $cmiOfflineMode = $adminSettings->getSetting('payments', 'cmi_offline_mode_enabled', false);
        $stripeOfflineMode = $adminSettings->getSetting('payments', 'stripe_offline_mode_enabled', false);

        return [
            'offline' => [
                'name' => 'Paiement hors ligne',
                'enabled' => $adminSettings->getSetting('payments', 'offline_enabled', true),
                'currency' => 'EUR',
                'description' => 'En tant que client public, votre demande de recharge sera soumise à confirmation par un administrateur. Une fois confirmée, le montant sera ajouté à votre solde.'
            ],
            'cmi' => [
                'name' => $cmiOfflineMode ? 'CMI (Hors Ligne)' : 'Paiement par carte bancaire - CMI',
                'enabled' => $cmiEnabled && $cmiConfigured,
                'currency' => $this->cmiConfig['currency'] ?? 'EUR',
                'test_mode' => $this->cmiConfig['test_mode'] ?? true,
                'offline_mode' => $cmiOfflineMode,
                'description' => $cmiConfigured 
                    ? ($cmiOfflineMode 
                        ? 'Paiement par carte bancaire via CMI. Votre demande sera soumise à confirmation automatique après validation du paiement.'
                        : 'Paiement sécurisé via CMI (Maroc). Approbation automatique selon la réponse de la banque. Montant crédité instantanément.')
                    : 'CMI non configuré. Veuillez configurer les clés API dans les paramètres admin.'
            ],
            'stripe' => [
                'name' => $stripeOfflineMode ? 'Stripe (Hors Ligne)' : 'Paiement par carte bancaire - Stripe',
                'enabled' => $stripeEnabled && $stripeConfigured,
                'currency' => $this->stripeConfig['currency'] ?? 'EUR',
                'test_mode' => $this->stripeConfig['test_mode'] ?? true,
                'offline_mode' => $stripeOfflineMode,
                'description' => $stripeConfigured 
                    ? ($stripeOfflineMode 
                        ? 'Paiement par carte bancaire via Stripe. Votre demande sera soumise à confirmation automatique après validation du paiement.'
                        : 'Paiement sécurisé via Stripe (International). Approbation automatique selon la réponse de la banque. Montant crédité instantanément.')
                    : 'Stripe non configuré. Veuillez configurer les clés API dans les paramètres admin.'
            ],
            'bank_transfer' => [
                'name' => 'Virement bancaire',
                'enabled' => $adminSettings->getSetting('payments', 'bank_transfer_enabled', true),
                'currency' => 'EUR',
                'instructions' => $adminSettings->getSetting('payments', 'offline_payment_instructions', ''),
                'bank_details' => $adminSettings->getSetting('payments', 'bank_details', '')
            ],
            'cash' => [
                'name' => 'Espèces',
                'enabled' => $adminSettings->getSetting('payments', 'cash_enabled', true),
                'currency' => 'EUR',
                'instructions' => $adminSettings->getSetting('payments', 'offline_payment_instructions', '')
            ]
        ];
    }

    /**
     * Obtient les statistiques de paiement
     * 
     * @return array
     */
    public function getPaymentStatistics(): array
    {
        $cacheKey = 'payment_statistics';
        
        return Cache::remember($cacheKey, 300, function () {
            $stats = [
                'total_transactions' => Transaction::count(),
                'paid_transactions' => Transaction::where('payment_status', 'paid')->count(),
                'pending_transactions' => Transaction::where('payment_status', 'pending')->count(),
                'failed_transactions' => Transaction::where('payment_status', 'failed')->count(),
                'total_revenue' => Transaction::where('payment_status', 'paid')->sum('price_total'),
                'cmi_payments' => Transaction::where('payment_method', 'cmi')->where('payment_status', 'paid')->count(),
                'stripe_payments' => Transaction::where('payment_method', 'stripe')->where('payment_status', 'paid')->count(),
                'bank_transfer_payments' => Transaction::where('payment_method', 'bank_transfer')->where('payment_status', 'paid')->count(),
                'calculated_at' => now()->toISOString()
            ];

            return $stats;
        });
    }
}
