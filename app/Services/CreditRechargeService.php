<?php

namespace App\Services;

use App\Models\User;
use App\Models\ClientUser;
use App\Models\Wallet;
use App\Models\CreditRecharge;
use App\Models\CreditPack;
use App\Models\WalletTransaction;
use App\Services\PaymentIntegrationService;
use App\Services\OfflinePaymentService;
use App\Services\CMICreditRechargeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de gestion des recharges de crédit client
 * 
 * Gère les recharges via trois méthodes :
 * - Offline : Recharge manuelle par un administrateur
 * - CMI : Paiement en ligne via CMI (Maroc)
 * - Stripe : Paiement en ligne via Stripe (International)
 */
class CreditRechargeService
{
    protected PaymentIntegrationService $paymentIntegrationService;
    protected OfflinePaymentService $offlinePaymentService;

    public function __construct(
        PaymentIntegrationService $paymentIntegrationService,
        OfflinePaymentService $offlinePaymentService
    ) {
        $this->paymentIntegrationService = $paymentIntegrationService;
        $this->offlinePaymentService = $offlinePaymentService;
    }

    /**
     * Initie une recharge de crédit avec un pack
     * 
     * @param User|ClientUser $user
     * @param int $creditPackId
     * @param string $paymentMethod
     * @param array $options
     * @return array
     */
    public function initiateRechargeWithPack(
        User|ClientUser $user,
        int $creditPackId,
        string $paymentMethod,
        array $options = []
    ): array {
        try {
            $creditPack = CreditPack::find($creditPackId);
            
            if (!$creditPack) {
                return [
                    'success' => false,
                    'message' => 'Pack de crédit non trouvé'
                ];
            }

            if (!$creditPack->is_active) {
                return [
                    'success' => false,
                    'message' => 'Ce pack de crédit n\'est plus disponible'
                ];
            }

            // Calculer le montant avec bonus
            $amount = $creditPack->total_amount_with_bonus;
            $currency = $creditPack->currency;

            return $this->initiateRecharge(
                $user,
                $amount,
                $paymentMethod,
                $currency,
                array_merge($options, [
                    'credit_pack_id' => $creditPackId,
                    'is_custom' => false,
                    'description' => $options['description'] ?? "Recharge via {$creditPack->name}",
                ])
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initiation de la recharge avec pack', [
                'user_id' => $user->id,
                'credit_pack_id' => $creditPackId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initiation de la recharge: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initie une recharge de crédit
     * 
     * @param User|ClientUser $user
     * @param float $amount
     * @param string $paymentMethod
     * @param string $currency
     * @param array $options
     * @return array
     */
    public function initiateRecharge(
        User|ClientUser $user,
        float $amount,
        string $paymentMethod,
        string $currency = 'EUR',
        array $options = []
    ): array {
        try {
            // Validation
            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Le montant doit être supérieur à zéro'
                ];
            }

            if (!in_array($paymentMethod, ['offline', 'cmi', 'stripe'])) {
                return [
                    'success' => false,
                    'message' => 'Méthode de paiement invalide'
                ];
            }

            // Obtenir ou créer le wallet de l'utilisateur
            $wallet = $user->getOrCreateWallet([
                'currency' => $currency,
                'is_active' => true
            ]);

            // Créer l'enregistrement de recharge
            $recharge = CreditRecharge::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'credit_pack_id' => $options['credit_pack_id'] ?? null,
                'is_custom' => $options['is_custom'] ?? ($options['credit_pack_id'] === null),
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'description' => $options['description'] ?? "Recharge de crédit de {$amount} {$currency}",
                'metadata' => array_merge($options['metadata'] ?? [], [
                    'credit_pack_id' => $options['credit_pack_id'] ?? null,
                    'is_custom' => $options['is_custom'] ?? ($options['credit_pack_id'] === null),
                ]),
            ]);

            // Traiter selon la méthode de paiement
            switch ($paymentMethod) {
                case 'offline':
                    return $this->initiateOfflineRecharge($recharge, $options);
                
                case 'cmi':
                    return $this->initiateCmiRecharge($recharge, $options);
                
                case 'stripe':
                    return $this->initiateStripeRecharge($recharge, $options);
                
                default:
                    return [
                        'success' => false,
                        'message' => 'Méthode de paiement non supportée'
                    ];
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initiation de la recharge', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initiation de la recharge: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initie une recharge hors ligne
     * 
     * @param CreditRecharge $recharge
     * @param array $options
     * @return array
     */
    protected function initiateOfflineRecharge(CreditRecharge $recharge, array $options = []): array
    {
        // Pour les recharges offline, on attend la confirmation de l'admin
        // La recharge reste en statut "pending" jusqu'à confirmation
        
        return [
            'success' => true,
            'recharge_id' => $recharge->id,
            'reference' => $recharge->reference,
            'status' => 'pending',
            'message' => 'Recharge hors ligne créée. En attente de confirmation par un administrateur.',
            'requires_admin_confirmation' => true
        ];
    }

    /**
     * Initie une recharge via CMI
     * 
     * @param CreditRecharge $recharge
     * @param array $options
     * @return array
     */
    protected function initiateCmiRecharge(CreditRecharge $recharge, array $options = []): array
    {
        try {
            // Utiliser le nouveau service CMI pour les recharges
            $cmiService = app(CMICreditRechargeService::class);
            
            // Préparer les données de paiement selon l'API CMI
            $paymentData = $cmiService->preparePaymentData($recharge, $options);
            $paymentUrl = $cmiService->getPaymentUrl();

            // Sauvegarder les données de paiement
            $recharge->update([
                'payment_data' => $paymentData,
                'status' => 'processing'
            ]);

            return [
                'success' => true,
                'recharge_id' => $recharge->id,
                'reference' => $recharge->reference,
                'payment_method' => 'cmi',
                'payment_url' => route('credit-recharge.cmi.send', $recharge->id),
                'payment_data' => $paymentData,
                'status' => 'processing'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur initiation recharge CMI', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $recharge->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement CMI: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initie une recharge via Stripe
     * 
     * @param CreditRecharge $recharge
     * @param array $options
     * @return array
     */
    protected function initiateStripeRecharge(CreditRecharge $recharge, array $options = []): array
    {
        try {
            $stripeConfig = config('payments.stripe', []);
            
            if (empty($stripeConfig['secret_key'])) {
                return [
                    'success' => false,
                    'message' => 'Configuration Stripe incomplète'
                ];
            }

            \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);

            // Créer une session de paiement Stripe
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($stripeConfig['currency'] ?? 'eur'),
                        'product_data' => [
                            'name' => "Recharge crédit #{$recharge->reference}",
                            'description' => "Recharge de crédit EVON - {$recharge->amount} {$recharge->currency}"
                        ],
                        'unit_amount' => (int) ($recharge->amount * 100) // Stripe utilise les centimes
                    ],
                    'quantity' => 1
                ]],
                'mode' => 'payment',
                'success_url' => route('credit-recharge.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('credit-recharge.stripe.cancel'),
                'metadata' => [
                    'recharge_id' => $recharge->id,
                    'reference' => $recharge->reference,
                    'user_id' => $recharge->user_id
                ]
            ]);

            // Mettre à jour la recharge avec les données de paiement
            $recharge->update([
                'payment_data' => [
                    'session_id' => $session->id,
                    'amount' => $recharge->amount
                ],
                'external_id' => $session->id,
                'status' => 'processing'
            ]);

            return [
                'success' => true,
                'recharge_id' => $recharge->id,
                'reference' => $recharge->reference,
                'payment_method' => 'stripe',
                'session_id' => $session->id,
                'payment_url' => $session->url,
                'status' => 'processing'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur initiation recharge Stripe', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage()
            ]);

            $recharge->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement Stripe: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Confirme une recharge hors ligne (admin seulement)
     * 
     * @param CreditRecharge $recharge
     * @param User $processor
     * @param array $options
     * @return array
     */
    public function confirmOfflineRecharge(
        CreditRecharge $recharge,
        User $processor,
        array $options = []
    ): array {
        try {
            if ($recharge->payment_method !== 'offline') {
                return [
                    'success' => false,
                    'message' => 'Cette recharge n\'est pas une recharge hors ligne'
                ];
            }

            if ($recharge->isCompleted()) {
                return [
                    'success' => false,
                    'message' => 'Cette recharge est déjà complétée'
                ];
            }

            return DB::transaction(function () use ($recharge, $processor, $options) {
                // Obtenir le wallet
                $wallet = $recharge->wallet ?? $recharge->user->getOrCreateWallet();
                
                // Créditer le wallet
                $walletTransaction = $wallet->credit(
                    $recharge->amount,
                    $recharge->description ?? "Recharge de crédit hors ligne #{$recharge->reference}",
                    array_merge($recharge->metadata ?? [], [
                        'recharge_id' => $recharge->id,
                        'recharge_reference' => $recharge->reference,
                        'processed_by' => $processor->id,
                        'payment_method' => 'offline'
                    ]),
                    $recharge->currency
                );

                // Mettre à jour la recharge
                $recharge->update([
                    'status' => 'completed',
                    'processed_by' => $processor->id,
                    'processed_at' => now(),
                    'wallet_id' => $wallet->id,
                    'metadata' => array_merge($recharge->metadata ?? [], [
                        'wallet_transaction_id' => $walletTransaction->id
                    ])
                ]);

                Log::info('Recharge hors ligne confirmée', [
                    'recharge_id' => $recharge->id,
                    'user_id' => $recharge->user_id,
                    'amount' => $recharge->amount,
                    'processed_by' => $processor->id
                ]);

                $recharge->user?->unsetRelation('wallet');
                
                // Invalider le cache du solde client après l'ajout de crédits
                app(\App\Services\ClientBalanceService::class)->invalidate($recharge->user);

                return [
                    'success' => true,
                    'message' => 'Recharge confirmée avec succès',
                    'recharge' => $recharge->fresh(),
                    'wallet_balance' => $wallet->fresh()->balance
                ];
            });

        } catch (\Exception $e) {
            Log::error('Erreur confirmation recharge hors ligne', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la confirmation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Rejette une recharge offline
     * 
     * @param CreditRecharge $recharge
     * @param User $processor
     * @param string $reason
     * @return array
     */
    public function rejectOfflineRecharge(
        CreditRecharge $recharge,
        User $processor,
        string $reason
    ): array {
        try {
            if ($recharge->payment_method !== 'offline') {
                return [
                    'success' => false,
                    'message' => 'Cette recharge n\'est pas une recharge hors ligne'
                ];
            }

            if ($recharge->isCompleted()) {
                return [
                    'success' => false,
                    'message' => 'Cette recharge est déjà complétée'
                ];
            }

            if ($recharge->status === 'failed' || $recharge->status === 'cancelled') {
                return [
                    'success' => false,
                    'message' => 'Cette recharge est déjà rejetée ou annulée'
                ];
            }

            // Mettre à jour la recharge
            $recharge->update([
                'status' => 'failed',
                'processed_by' => $processor->id,
                'processed_at' => now(),
                'failed_at' => now(),
                'failure_reason' => $reason,
                'metadata' => array_merge($recharge->metadata ?? [], [
                    'rejected_by' => $processor->id,
                    'rejected_at' => now()->toISOString(),
                    'rejection_reason' => $reason
                ])
            ]);

            Log::info('Recharge hors ligne rejetée', [
                'recharge_id' => $recharge->id,
                'user_id' => $recharge->user_id,
                'amount' => $recharge->amount,
                'rejected_by' => $processor->id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Recharge rejetée avec succès',
                'recharge' => $recharge->fresh()
            ];

        } catch (\Exception $e) {
            Log::error('Erreur rejet recharge hors ligne', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du rejet: ' . $e->getMessage()
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
            Log::info('Callback CMI reçu', [
                'callback_data' => array_keys($callbackData),
                'oid' => $callbackData['oid'] ?? null,
                'Response' => $callbackData['Response'] ?? null
            ]);

            // Vérifier que le paiement a été approuvé
            if (isset($callbackData['Response']) && $callbackData['Response'] !== 'Approved') {
                $rechargeId = $callbackData['oid'] ?? null;
                if ($rechargeId) {
                    $recharge = CreditRecharge::find($rechargeId);
                    if ($recharge && !$recharge->isCompleted()) {
                        $recharge->markAsFailed('Paiement CMI rejeté: ' . ($callbackData['Response'] ?? 'Unknown'));
                    }
                }
                return [
                    'success' => false,
                    'message' => 'Paiement rejeté par CMI: ' . ($callbackData['Response'] ?? 'Unknown')
                ];
            }

            // Vérifier la signature CMI
            if (!$this->verifyCmiSignature($callbackData)) {
                Log::error('Signature CMI invalide dans le callback', [
                    'callback_data' => $callbackData
                ]);
                throw new \Exception('Signature CMI invalide');
            }

            $rechargeId = $callbackData['oid'] ?? null;
            if (!$rechargeId) {
                throw new \Exception('ID de recharge manquant dans le callback CMI');
            }

            $recharge = CreditRecharge::find($rechargeId);
            if (!$recharge) {
                throw new \Exception("Recharge non trouvée avec l'ID: {$rechargeId}");
            }

            if ($recharge->isCompleted()) {
                Log::info('Recharge CMI déjà complétée', [
                    'recharge_id' => $recharge->id
                ]);
                return [
                    'success' => true,
                    'message' => 'Recharge déjà complétée',
                    'recharge_id' => $recharge->id
                ];
            }

            return DB::transaction(function () use ($recharge, $callbackData) {
                // Obtenir le wallet
                $wallet = $recharge->wallet ?? $recharge->user->getOrCreateWallet();
                
                // Créditer le wallet
                $walletTransaction = $wallet->credit(
                    $recharge->amount,
                    $recharge->description ?? "Recharge de crédit CMI #{$recharge->reference}",
                    array_merge($recharge->metadata ?? [], [
                        'recharge_id' => $recharge->id,
                        'recharge_reference' => $recharge->reference,
                        'payment_method' => 'cmi',
                        'cmi_trans_id' => $callbackData['TransId'] ?? null
                    ]),
                    $recharge->currency
                );

                // Mettre à jour la recharge
                $recharge->update([
                    'status' => 'completed',
                    'external_id' => $callbackData['TransId'] ?? null,
                    'processed_at' => now(),
                    'wallet_id' => $wallet->id,
                    'payment_data' => array_merge($recharge->payment_data ?? [], $callbackData),
                    'metadata' => array_merge($recharge->metadata ?? [], [
                        'wallet_transaction_id' => $walletTransaction->id
                    ])
                ]);

                Log::info('Recharge CMI complétée', [
                    'recharge_id' => $recharge->id,
                    'user_id' => $recharge->user_id,
                    'amount' => $recharge->amount
                ]);

                $recharge->user?->unsetRelation('wallet');
                
                // Invalider le cache du solde client après l'ajout de crédits
                app(\App\Services\ClientBalanceService::class)->invalidate($recharge->user);

                return [
                    'success' => true,
                    'message' => 'Recharge complétée avec succès',
                    'recharge' => $recharge->fresh(),
                    'wallet_balance' => $wallet->fresh()->balance
                ];
            });

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
            Log::info('Webhook Stripe reçu', [
                'event_type' => $webhookData['type'] ?? null,
                'object_id' => $webhookData['data']['object']['id'] ?? null
            ]);

            $event = $webhookData['type'] ?? null;
            $sessionId = $webhookData['data']['object']['id'] ?? null;

            if ($event === 'checkout.session.completed' && $sessionId) {
                $stripeConfig = config('payments.stripe', []);
                if (empty($stripeConfig['secret_key'])) {
                    throw new \Exception('Configuration Stripe incomplète: secret_key manquant');
                }

                \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);
                
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                $rechargeId = $session->metadata['recharge_id'] ?? null;

                if (!$rechargeId) {
                    Log::warning('Webhook Stripe: recharge_id manquant dans les métadonnées', [
                        'session_id' => $sessionId,
                        'metadata' => $session->metadata ?? []
                    ]);
                    return [
                        'success' => false,
                        'message' => 'recharge_id manquant dans les métadonnées'
                    ];
                }

                $recharge = CreditRecharge::find($rechargeId);
                if (!$recharge) {
                    Log::error('Webhook Stripe: Recharge non trouvée', [
                        'recharge_id' => $rechargeId,
                        'session_id' => $sessionId
                    ]);
                    return [
                        'success' => false,
                        'message' => "Recharge non trouvée avec l'ID: {$rechargeId}"
                    ];
                }

                if ($recharge->isCompleted()) {
                    Log::info('Webhook Stripe: Recharge déjà complétée', [
                        'recharge_id' => $recharge->id
                    ]);
                    return [
                        'success' => true,
                        'message' => 'Recharge déjà complétée',
                        'recharge_id' => $recharge->id
                    ];
                }

                // Traiter la recharge
                return DB::transaction(function () use ($recharge, $session) {
                    // Obtenir le wallet
                    $wallet = $recharge->wallet ?? $recharge->user->getOrCreateWallet();
                    
                    // Créditer le wallet
                    $walletTransaction = $wallet->credit(
                        $recharge->amount,
                        $recharge->description ?? "Recharge de crédit Stripe #{$recharge->reference}",
                        array_merge($recharge->metadata ?? [], [
                            'recharge_id' => $recharge->id,
                            'recharge_reference' => $recharge->reference,
                            'payment_method' => 'stripe',
                            'stripe_session_id' => $session->id,
                            'stripe_payment_intent' => $session->payment_intent ?? null
                        ]),
                        $recharge->currency
                    );

                    // Mettre à jour la recharge
                    $recharge->update([
                        'status' => 'completed',
                        'external_id' => $session->payment_intent ?? $session->id,
                        'processed_at' => now(),
                        'wallet_id' => $wallet->id,
                        'payment_data' => array_merge($recharge->payment_data ?? [], [
                            'session_id' => $session->id,
                            'payment_intent' => $session->payment_intent ?? null
                        ]),
                        'metadata' => array_merge($recharge->metadata ?? [], [
                            'wallet_transaction_id' => $walletTransaction->id
                        ])
                    ]);

                    Log::info('Recharge Stripe complétée', [
                        'recharge_id' => $recharge->id,
                        'user_id' => $recharge->user_id,
                        'amount' => $recharge->amount
                    ]);

                    $recharge->user?->unsetRelation('wallet');
                    
                    // Invalider le cache du solde client après l'ajout de crédits
                    app(\App\Services\ClientBalanceService::class)->invalidate($recharge->user);

                    return [
                        'success' => true,
                        'message' => 'Recharge complétée avec succès',
                        'recharge' => $recharge->fresh(),
                        'wallet_balance' => $wallet->fresh()->balance
                    ];
                });
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
     * Annule une recharge
     * 
     * @param CreditRecharge $recharge
     * @param string $reason
     * @return array
     */
    public function cancelRecharge(CreditRecharge $recharge, string $reason = ''): array
    {
        try {
            if ($recharge->isCompleted()) {
                return [
                    'success' => false,
                    'message' => 'Impossible d\'annuler une recharge complétée'
                ];
            }

            $recharge->markAsCancelled($reason);

            Log::info('Recharge annulée', [
                'recharge_id' => $recharge->id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Recharge annulée avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur annulation recharge', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifie la signature CMI
     * 
     * @param array $data
     * @return bool
     */
    protected function verifyCmiSignature(array $data): bool
    {
        try {
            $cmiConfig = config('payments.cmi', []);
            
            // Vérifier que les données nécessaires sont présentes
            if (empty($data['clientid']) || empty($data['oid']) || empty($data['amount']) || 
                empty($data['okUrl']) || empty($data['failUrl']) || empty($data['rnd']) || 
                empty($data['hash']) || empty($cmiConfig['store_password'])) {
                Log::warning('Signature CMI: Données manquantes', [
                    'data_keys' => array_keys($data),
                    'has_store_password' => !empty($cmiConfig['store_password'])
                ]);
                return false;
            }
            
            // Reconstruire le hash selon la documentation CMI
            $hashString = $data['clientid'] . $data['oid'] . $data['amount'] . 
                         $data['okUrl'] . $data['failUrl'] . $data['rnd'] . 
                         $cmiConfig['store_password'];
            $expectedHash = base64_encode(pack('H*', sha1($hashString)));

            $isValid = hash_equals($expectedHash, $data['hash']);
            
            if (!$isValid) {
                Log::warning('Signature CMI invalide', [
                    'expected_hash' => substr($expectedHash, 0, 20) . '...',
                    'received_hash' => substr($data['hash'], 0, 20) . '...'
                ]);
            }
            
            return $isValid;
        } catch (\Exception $e) {
            Log::error('Erreur vérification signature CMI', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtient l'historique des recharges d'un utilisateur
     * 
     * @param User|ClientUser $user
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserRechargeHistory(User|ClientUser $user, array $filters = [])
    {
        $query = CreditRecharge::where('user_id', $user->id)
            ->with(['creditPack', 'wallet']) // Eager loading pour optimiser
            ->orderBy('created_at', 'desc');

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_method']) && !empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}

