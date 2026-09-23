<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\Payment;
use App\Services\PaymentKeysService;
use App\Services\AdminConfigurationService;
use App\Services\ReservationPaymentApprovalService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;
use Throwable;

/**
 * Service unifié de paiement amélioré pour CMI et Stripe
 * 
 * Ce service fournit une interface unifiée pour gérer les paiements
 * avec une meilleure gestion d'erreurs, retry logic, et validation stricte
 */
class UnifiedPaymentService
{
    protected PaymentKeysService $paymentKeysService;
    protected AdminConfigurationService $adminSettings;
    protected array $cmiConfig;
    protected array $stripeConfig;
    
    // Configuration des retries
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // millisecondes
    
    public function __construct(
        PaymentKeysService $paymentKeysService,
        AdminConfigurationService $adminSettings
    ) {
        $this->paymentKeysService = $paymentKeysService;
        $this->adminSettings = $adminSettings;
        $this->loadConfigurations();
    }

    /**
     * Charge les configurations de paiement
     */
    protected function loadConfigurations(): void
    {
        try {
            // Charger les clés CMI actives
            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            $this->cmiConfig = [
                'store_key' => $cmiKeys['storekey'] ?? '',
                'client_id' => $cmiKeys['clientid'] ?? '',
                'api_url' => $cmiKeys['api_url'] ?? config('payments.cmi.base_url', 'https://testpayment.cmi.co.ma/fim/est3Dgate'),
                'callback_url' => $cmiKeys['callback_url'] ?? config('app.url'),
                'environment' => $cmiKeys['environment'] ?? 'test',
                'test_mode' => ($cmiKeys['environment'] ?? 'test') === 'test',
                'currency' => config('payments.cmi.currency', 'EUR'),
                'timeout' => config('payments.cmi.timeout', 30),
                'enabled' => $this->adminSettings->getSetting('payments', 'cmi_enabled', true),
            ];

            // Charger les clés Stripe actives
            $stripeKeys = $this->paymentKeysService->getActiveStripeKeys();
            $this->stripeConfig = [
                'publishable_key' => $stripeKeys['publishable_key'] ?? '',
                'secret_key' => $stripeKeys['secret_key'] ?? '',
                'webhook_secret' => $stripeKeys['webhook_secret'] ?? '',
                'environment' => $stripeKeys['environment'] ?? 'test',
                'test_mode' => ($stripeKeys['environment'] ?? 'test') === 'test',
                'currency' => strtolower($stripeKeys['currency'] ?? config('payments.stripe.currency', 'eur')),
                'timeout' => config('payments.stripe.timeout', 30),
                'enabled' => $this->adminSettings->getSetting('payments', 'stripe_enabled', true),
            ];
        } catch (Throwable $e) {
            Log::error('Erreur lors du chargement des configurations de paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Utiliser les configurations par défaut
            $this->cmiConfig = config('payments.cmi', []);
            $this->stripeConfig = config('payments.stripe', []);
        }
    }

    /**
     * Initie un paiement pour une réservation
     * 
     * @param Reservation $reservation
     * @param string $paymentMethod 'cmi' ou 'stripe'
     * @param array $options Options supplémentaires (peut inclure 'offline_mode' => true)
     * @return array
     */
    public function initiatePayment(Reservation $reservation, string $paymentMethod, array $options = []): array
    {
        try {
            // Validation préalable
            $validation = $this->validatePaymentRequest($reservation, $paymentMethod);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'message' => $validation['message']
                ];
            }

            DB::beginTransaction();

            // Créer l'enregistrement de transaction
            $transaction = $this->createPaymentTransaction($reservation, $paymentMethod);

            // Initier le paiement selon la méthode
            $paymentResult = match($paymentMethod) {
                'cmi' => $this->initiateCmiPayment($reservation, $transaction, $options),
                'stripe' => $this->initiateStripePayment($reservation, $transaction, $options),
                default => throw new Exception("Méthode de paiement non supportée: {$paymentMethod}")
            };

            if (!$paymentResult['success']) {
                DB::rollBack();
                $this->logPaymentError($reservation, $paymentMethod, $paymentResult['error'] ?? 'Erreur inconnue');
                
                return [
                    'success' => false,
                    'error' => $paymentResult['error'],
                    'message' => $paymentResult['message'] ?? 'Erreur lors de l\'initiation du paiement'
                ];
            }

            // Mettre à jour la transaction avec les données de paiement
            $transaction->update([
                'payment_reference' => $paymentResult['reference'] ?? null,
                'stripe_session_id' => $paymentMethod === 'stripe'
                    ? ($paymentResult['session_id'] ?? $transaction->stripe_session_id)
                    : $transaction->stripe_session_id,
                'gateway_response' => $paymentResult['data'] ?? null,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_initiated_at' => now()->toIso8601String(),
                    'payment_method' => $paymentMethod,
                    'options' => $options
                ])
            ]);

            // Mettre à jour la réservation
            $reservation->update([
                'payment_method' => $paymentMethod,
                'payment_status' => 'PENDING'
            ]);

            DB::commit();

            Log::info('Paiement initié avec succès', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'payment_method' => $paymentMethod,
                'amount' => $reservation->estimated_cost
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'payment_reference' => $paymentResult['reference'],
                'redirect_url' => $paymentResult['redirect_url'] ?? null,
                'payment_url' => $paymentResult['payment_url'] ?? null,
                'session_id' => $paymentResult['session_id'] ?? null,
                'data' => $paymentResult['data'] ?? null
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            $this->logPaymentError($reservation, $paymentMethod, $e->getMessage(), $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de l\'initiation du paiement'
            ];
        }
    }

    /**
     * Valide une demande de paiement
     */
    protected function validatePaymentRequest(Reservation $reservation, string $paymentMethod): array
    {
        // Vérifier que la réservation existe et est valide
        if (!$reservation) {
            return [
                'valid' => false,
                'error' => 'RESERVATION_NOT_FOUND',
                'message' => 'Réservation non trouvée'
            ];
        }

        // Vérifier le statut de la réservation (gérer enum ou string)
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;
        if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
            return [
                'valid' => false,
                'error' => 'INVALID_RESERVATION_STATUS',
                'message' => 'Cette réservation ne peut pas être payée'
            ];
        }

        // Vérifier le montant
        $amount = $reservation->estimated_cost ?? $reservation->amount ?? 0;
        if ($amount <= 0) {
            return [
                'valid' => false,
                'error' => 'INVALID_AMOUNT',
                'message' => 'Le montant de la réservation est invalide'
            ];
        }

        // Vérifier que la méthode de paiement est activée
        if ($paymentMethod === 'cmi' && (!$this->cmiConfig['enabled'] || empty($this->cmiConfig['client_id']))) {
            return [
                'valid' => false,
                'error' => 'PAYMENT_METHOD_DISABLED',
                'message' => 'Le paiement CMI n\'est pas disponible'
            ];
        }

        if ($paymentMethod === 'stripe' && (!$this->stripeConfig['enabled'] || empty($this->stripeConfig['secret_key']))) {
            return [
                'valid' => false,
                'error' => 'PAYMENT_METHOD_DISABLED',
                'message' => 'Le paiement Stripe n\'est pas disponible'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Initie un paiement CMI
     */
    protected function initiateCmiPayment(Reservation $reservation, Transaction $transaction, array $options = []): array
    {
        try {
            // Valider la configuration CMI en premier
            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            $storeKey = $cmiKeys['storekey'] ?? $this->cmiConfig['store_key'] ?? '';
            $clientId = $cmiKeys['clientid'] ?? $this->cmiConfig['client_id'] ?? '';

            if (empty($storeKey)) {
                throw new Exception('CMI Store Key manquante. Veuillez vérifier la configuration.');
            }

            if (empty($clientId)) {
                throw new Exception('CMI Client ID manquant. Veuillez vérifier la configuration.');
            }

            $amount = $reservation->estimated_cost ?? $reservation->amount ?? 0;
            
            // Valider le montant
            if ($amount <= 0) {
                throw new Exception('Le montant de la réservation doit être supérieur à 0');
            }

            $amountFormatted = number_format($amount, 2, '.', '');
            $rnd = time();
            $user = $reservation->user;

            if (!$user) {
                throw new Exception('Utilisateur non trouvé pour la réservation');
            }

            if (empty($user->email)) {
                throw new Exception('L\'adresse email est requise pour le paiement CMI');
            }

            // Valider l'email
            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('L\'adresse email n\'est pas valide');
            }

            // Préparer les données CMI
            $paymentData = [
                'clientid' => $clientId,
                'storetype' => '3D_PAY_HOSTING',
                'amount' => $amountFormatted,
                'oid' => (string) $reservation->id,
                'okUrl' => route('payment.cmi.reservation.success'),
                'failUrl' => route('payment.cmi.reservation.failure'),
                'rnd' => (string) $rnd,
                'currency' => $this->cmiConfig['currency'] ?? 'EUR',
                'lang' => $options['lang'] ?? app()->getLocale() ?? 'fr',
                'email' => $user->email,
                'tel' => $user->phone ?? '',
                'BillToName' => $user->name ?? '',
                'BillToStreet1' => $user->address ?? '',
                'BillToCity' => $user->city ?? '',
                'BillToCountry' => $user->country ?? 'MA',
                'BillToStateProvince' => $user->city ?? '',
                'BillToPostalCode' => $user->postal_code ?? '',
                'description' => "Réservation #{$reservation->id} - EVON"
            ];

            // Générer le hash de sécurité CMI selon la documentation CMI
            $hashString = $paymentData['clientid'] . 
                         $paymentData['oid'] . 
                         $paymentData['amount'] . 
                         $paymentData['okUrl'] . 
                         $paymentData['failUrl'] . 
                         $paymentData['rnd'] . 
                         $storeKey;
            
            $paymentData['hash'] = base64_encode(pack('H*', sha1($hashString)));

            // Construire l'URL de paiement CMI
            $baseUrl = rtrim($this->cmiConfig['api_url'], '/');
            if (strpos($baseUrl, 'est3Dgate') === false) {
                $baseUrl = str_replace('/fim/est3Dgate', '', $baseUrl);
                $baseUrl = rtrim($baseUrl, '/') . '/fim/est3Dgate';
            }

            Log::info('Paiement CMI initié', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'reference' => 'CMI-' . $reservation->id . '-' . $rnd,
                'payment_url' => $baseUrl,
                'data' => $paymentData
            ];

        } catch (Throwable $e) {
            Log::error('Erreur initiation paiement CMI', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de l\'initiation du paiement CMI'
            ];
        }
    }

    /**
     * Initie un paiement Stripe
     */
    protected function initiateStripePayment(Reservation $reservation, Transaction $transaction, array $options = []): array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeConfig['secret_key']);
            \Stripe\Stripe::setMaxNetworkRetries($this->maxRetries);

            $amount = $reservation->estimated_cost ?? $reservation->amount ?? 0;
            $amountInCents = $this->convertToStripeAmount($amount, $this->stripeConfig['currency']);
            $user = $reservation->user;

            // Créer une session de paiement Stripe avec retry logic
            $session = $this->retryStripeOperation(function() use ($reservation, $amountInCents, $user) {
                return \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => $this->stripeConfig['currency'],
                            'product_data' => [
                                'name' => "Réservation #{$reservation->id}",
                                'description' => "Réservation EVON - " . ($reservation->chargingPoint->name ?? 'Borne de charge')
                            ],
                            'unit_amount' => $amountInCents
                        ],
                        'quantity' => 1
                    ]],
                    'mode' => 'payment',
                    'success_url' => route('payment.reservation.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}&reservation_id=' . $reservation->id,
                    'cancel_url' => route('payment.reservation.stripe.cancel') . '?reservation_id=' . $reservation->id,
                    'customer_email' => $user->email ?? null,
                    'metadata' => [
                        'reservation_id' => (string) $reservation->id,
                        'transaction_id' => (string) $transaction->id,
                        'charging_point_id' => (string) $reservation->charging_point_id,
                        'user_id' => (string) $reservation->user_id
                    ],
                    'payment_intent_data' => [
                        'metadata' => [
                            'reservation_id' => (string) $reservation->id,
                            'transaction_id' => (string) $transaction->id
                        ]
                    ]
                ], [
                    'timeout' => $this->stripeConfig['timeout']
                ]);
            });

            Log::info('Paiement Stripe initié', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'session_id' => $session->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'reference' => $session->id,
                'session_id' => $session->id,
                'redirect_url' => $session->url,
                'data' => [
                    'session_id' => $session->id,
                    'payment_intent_id' => $session->payment_intent ?? null
                ]
            ];

        } catch (Throwable $e) {
            Log::error('Erreur initiation paiement Stripe', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de l\'initiation du paiement Stripe'
            ];
        }
    }

    /**
     * Convertit un montant en centimes pour Stripe
     */
    protected function convertToStripeAmount(float $amount, string $currency): int
    {
        // Devises sans décimales
        $zeroDecimalCurrencies = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
        
        if (in_array(strtolower($currency), $zeroDecimalCurrencies)) {
            return (int) round($amount);
        }
        
        return (int) round($amount * 100);
    }

    /**
     * Retry logic pour les opérations Stripe
     */
    protected function retryStripeOperation(callable $operation, int $maxRetries = null): mixed
    {
        $maxRetries = $maxRetries ?? $this->maxRetries;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                return $operation();
            } catch (\Stripe\Exception\RateLimitException $e) {
                $lastException = $e;
                $attempt++;
                if ($attempt < $maxRetries) {
                    usleep($this->retryDelay * 1000 * $attempt); // Délai exponentiel
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Ne pas retry pour les erreurs client (4xx)
                if ($e->getHttpStatus() < 500) {
                    throw $e;
                }
                $lastException = $e;
                $attempt++;
                if ($attempt < $maxRetries) {
                    usleep($this->retryDelay * 1000 * $attempt);
                }
            }
        }

        throw $lastException ?? new Exception('Erreur lors de l\'opération Stripe');
    }

    /**
     * Crée un enregistrement de transaction
     */
    protected function createPaymentTransaction(Reservation $reservation, string $paymentMethod): Transaction
    {
        // Vérifier si une transaction existe déjà
        $existingTransaction = Transaction::where('reservation_id', $reservation->id)
            ->where('status', 'pending')
            ->first();

        if ($existingTransaction) {
            return $existingTransaction;
        }

        return Transaction::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'amount' => $reservation->estimated_cost ?? $reservation->amount ?? 0,
            'currency' => 'EUR',
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'created_at' => now(),
        ]);
    }

    /**
     * Gère le succès d'un paiement
     */
    public function handlePaymentSuccess(string $paymentReference, array $webhookData = []): array
    {
        try {
            DB::beginTransaction();

            // Trouver la transaction
            $transaction = $this->findTransactionByReference($paymentReference, $webhookData);
            
            if (!$transaction) {
                throw new Exception("Transaction non trouvée pour la référence: {$paymentReference}");
            }

            // Vérifier que la transaction n'est pas déjà complétée
            if ($transaction->status === 'completed') {
                Log::warning('Tentative de traitement d\'une transaction déjà complétée', [
                    'transaction_id' => $transaction->id,
                    'payment_reference' => $paymentReference
                ]);
                
                DB::commit();
                return [
                    'success' => true,
                    'message' => 'Transaction déjà complétée',
                    'reservation' => $transaction->reservation
                ];
            }

            $reservation = $transaction->reservation;
            if (!$reservation) {
                throw new Exception("Réservation non trouvée pour la transaction: {$transaction->id}");
            }

            // Mettre à jour la transaction
            $transaction->update([
                'status' => 'completed',
                'completed_at' => now(),
                'webhook_data' => $webhookData,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_completed_at' => now()->toIso8601String(),
                    'webhook_data' => $webhookData
                ])
            ]);

            $approvalService = app(ReservationPaymentApprovalService::class);
            $approvalResult = $approvalService->applyPaymentSuccess(
                $reservation,
                $transaction->payment_method,
                'payment_gateway'
            );

            $reservation = $approvalResult['reservation'];
            $autoApproved = (bool) ($approvalResult['auto_approved'] ?? false);

            if ($autoApproved) {
                DB::afterCommit(function () use ($approvalService, $reservation) {
                    $approvalService->dispatchApprovalEvent($reservation, 'payment_gateway');
                });
            }

            DB::commit();

            Log::info('Paiement traité avec succès', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'payment_reference' => $paymentReference
            ]);

            return [
                'success' => true,
                'reservation' => $reservation,
                'transaction' => $transaction
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erreur traitement succès paiement', [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Gère l'échec d'un paiement
     */
    public function handlePaymentFailure(string $paymentReference, string $reason = ''): array
    {
        try {
            DB::beginTransaction();

            $transaction = $this->findTransactionByReference($paymentReference);
            
            if (!$transaction) {
                throw new Exception("Transaction non trouvée pour la référence: {$paymentReference}");
            }

            $reservation = $transaction->reservation;
            if (!$reservation) {
                throw new Exception("Réservation non trouvée pour la transaction: {$transaction->id}");
            }

            // Mettre à jour la transaction
            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $reason,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_failed_at' => now()->toIso8601String(),
                    'failure_reason' => $reason
                ])
            ]);

            // Mettre à jour la réservation
            $reservation->update([
                'payment_status' => 'FAILED'
            ]);

            DB::commit();

            Log::info('Échec de paiement traité', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction->id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'reservation' => $reservation,
                'transaction' => $transaction
            ];

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Erreur traitement échec paiement', [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Trouve une transaction par référence
     */
    protected function findTransactionByReference(string $paymentReference, array $webhookData = []): ?Transaction
    {
        // Essayer par payment_reference
        $transaction = Transaction::where('payment_reference', $paymentReference)->first();
        if ($transaction) {
            return $transaction;
        }

        // Essayer par reservation_id (pour CMI qui utilise oid = reservation_id)
        if (is_numeric($paymentReference)) {
            $reservation = Reservation::find((int) $paymentReference);
            if ($reservation) {
                $transaction = Transaction::where('reservation_id', $reservation->id)->first();
                if ($transaction) {
                    return $transaction;
                }
            }
        }

        // Essayer via webhook data (pour Stripe)
        if (isset($webhookData['data']['object']['metadata']['transaction_id'])) {
            $transactionId = $webhookData['data']['object']['metadata']['transaction_id'];
            return Transaction::find($transactionId);
        }

        if (isset($webhookData['data']['object']['metadata']['reservation_id'])) {
            $reservationId = $webhookData['data']['object']['metadata']['reservation_id'];
            $reservation = Reservation::find($reservationId);
            if ($reservation) {
                return Transaction::where('reservation_id', $reservation->id)->first();
            }
        }

        return null;
    }

    /**
     * Vérifie la signature CMI
     */
    public function verifyCmiSignature(array $callbackData): bool
    {
        try {
            if (!isset($callbackData['hash']) || !isset($callbackData['oid'])) {
                return false;
            }

            $cmiKeys = $this->paymentKeysService->getActiveCmiKeys();
            $storePassword = $cmiKeys['storekey'] ?? '';
            $hashString = $callbackData['clientid'] . 
                         $callbackData['oid'] . 
                         $callbackData['amount'] . 
                         $callbackData['okUrl'] . 
                         $callbackData['failUrl'] . 
                         $callbackData['rnd'] . 
                         $storePassword;
            
            $expectedHash = base64_encode(pack('H*', sha1($hashString)));
            
            return hash_equals($expectedHash, $callbackData['hash'] ?? '');
        } catch (Throwable $e) {
            Log::error('Erreur vérification signature CMI', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtient les méthodes de paiement disponibles
     */
    public function getAvailablePaymentMethods(): array
    {
        return [
            'cmi' => [
                'name' => 'CMI (Maroc)',
                'enabled' => $this->cmiConfig['enabled'] && !empty($this->cmiConfig['client_id']),
                'currency' => $this->cmiConfig['currency'] ?? 'EUR',
                'test_mode' => $this->cmiConfig['test_mode'] ?? true,
                'description' => 'Paiement sécurisé par carte bancaire via CMI',
                'icon' => 'fas fa-university',
                'color' => 'blue'
            ],
            'stripe' => [
                'name' => 'Stripe (International)',
                'enabled' => $this->stripeConfig['enabled'] && !empty($this->stripeConfig['secret_key']),
                'currency' => strtoupper($this->stripeConfig['currency'] ?? 'EUR'),
                'test_mode' => $this->stripeConfig['test_mode'] ?? true,
                'description' => 'Paiement sécurisé par carte bancaire via Stripe',
                'icon' => 'fab fa-stripe',
                'color' => 'purple'
            ]
        ];
    }

    /**
     * Log des erreurs de paiement
     */
    protected function logPaymentError(Reservation $reservation, string $paymentMethod, string $error, ?Throwable $exception = null): void
    {
        Log::error('Erreur paiement', [
            'reservation_id' => $reservation->id,
            'payment_method' => $paymentMethod,
            'error' => $error,
            'exception' => $exception ? [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ] : null
        ]);
    }
}

