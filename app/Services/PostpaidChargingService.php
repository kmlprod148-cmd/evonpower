<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\Wallet;
use App\Services\SteveService;
use App\Services\ChargingSessionCostService;
use App\Services\PostpaidInvoiceService;
use App\Events\PostpaidSessionStarted;
use App\Events\PostpaidSessionUpdated;
use App\Events\PostpaidSessionCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PostpaidChargingService
{
    protected $steveService;
    protected $costService;
    protected $invoiceService;

    public function __construct(
        SteveService $steveService,
        ChargingSessionCostService $costService,
        PostpaidInvoiceService $invoiceService
    ) {
        $this->steveService = $steveService;
        $this->costService = $costService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Démarrer une session de recharge postpayée
     * 
     * @param ChargingPoint $chargingPoint
     * @param User $user
     * @param array $params ['connector_id', 'min_threshold', 'metadata']
     * @return array
     */
    public function startPostpaidSession(ChargingPoint $chargingPoint, User $user, array $params = []): array
    {
        return DB::transaction(function () use ($chargingPoint, $user, $params) {
            try {
                // 1. Vérifier et valider le wallet avant de démarrer
                $walletValidation = $this->validateWalletBeforeStart($user, $chargingPoint, $params);
                
                if (!$walletValidation['valid']) {
                    return [
                        'success' => false,
                        'message' => $walletValidation['message'],
                        'error' => 'insufficient_balance',
                        'wallet' => [
                            'current_balance' => $walletValidation['current_balance'],
                            'required_balance' => $walletValidation['required_balance'],
                            'missing_balance' => $walletValidation['missing_balance'],
                            'needs_recharge' => true,
                        ],
                        'recharge_info' => [
                            'minimum_recharge' => $walletValidation['minimum_recharge'],
                            'recommended_recharge' => $walletValidation['recommended_recharge'],
                            'recharge_url' => url('/wallet/recharge'),
                            'wallet_id' => $walletValidation['wallet']->id ?? null,
                        ],
                    ];
                }
                
                $wallet = $walletValidation['wallet'];
                $minThreshold = $walletValidation['required_balance'];

                // 2. Vérifier que la borne est disponible
                if (!$this->isChargingPointAvailable($chargingPoint)) {
                    return [
                        'success' => false,
                        'message' => 'La borne n\'est pas disponible',
                        'error' => 'charging_point_unavailable',
                    ];
                }

                // 3. Démarrer la recharge via Steve
                $connectorId = $params['connector_id'] ?? 1;
                $idTag = $params['id_tag'] ?? $user->email ?? 'user_' . $user->id;
                
                $startResult = $this->steveService->startCharging($chargingPoint, $connectorId, $idTag);
                
                if (!$startResult['ok']) {
                    return [
                        'success' => false,
                        'message' => 'Échec du démarrage de la recharge: ' . ($startResult['error'] ?? 'Erreur inconnue'),
                        'error' => 'start_charging_failed',
                        'steve_response' => $startResult,
                    ];
                }

                // 4. Créer la session de recharge postpayée
                $session = ChargingSession::create([
                    'session_id' => 'POSTPAID_' . time() . '_' . strtoupper(\Str::random(8)),
                    'charging_point_id' => $chargingPoint->id,
                    'user_id' => $user->id,
                    'mode' => 'postpaid',
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'min_threshold' => $minThreshold,
                    'wallet_validation_passed' => true,
                    'wallet_validated_at' => now(),
                    'payment_status' => 'pending',
                    'metadata' => array_merge([
                        'connector_id' => $connectorId,
                        'id_tag' => $idTag,
                        'steve_transaction_id' => $startResult['body']['transactionId'] ?? null,
                        'started_via' => 'postpaid_service',
                    ], $params['metadata'] ?? []),
                ]);

                // Synchroniser avec les modèles Réservation et Transaction pour uniformiser
                $pricingPlanId = $chargingPoint->pricing_plan_id ?? \App\Models\PricingPlan::where('is_active', true)->first()->id ?? 1;
                $reservation = \App\Models\Reservation::create([
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'pricing_plan_id' => $pricingPlanId,
                    'reservation_type' => 'kwh',
                    'reservation_value' => 100, // Valeur arbitraire postpayée
                    'start_time' => now(),
                    'status' => \App\Enums\ReservationStatus::CONFIRMED,
                    'payment_method' => 'postpaid_credit',
                    'payment_mode' => 'postpaid',
                    'payment_status' => 'PENDING',
                    'amount' => 0,
                    'is_public' => false
                ]);

                $transactionIdStr = $startResult['body']['transactionId'] ?? ('POSTPAID_' . time() . rand(10, 99));
                \App\Models\Transaction::create([
                    'transaction_id' => $transactionIdStr,
                    'reservation_id' => $reservation->id,
                    'charging_point_id' => $chargingPoint->id,
                    'user_id' => $user->id,
                    'status' => 'in_progress',
                    'start_timestamp' => now(),
                    'pricing_plan_id' => $pricingPlanId,
                    'metadata' => [
                        'connector_id' => $connectorId,
                        'id_tag' => $idTag,
                        'postpaid_session_id' => $session->id ?? null
                    ]
                ]);
                $session->update(['metadata' => array_merge($session->metadata ?? [], ['reservation_id' => $reservation->id])]);

                // 5. Enregistrer les valeurs initiales du compteur
                $meterStart = $this->getMeterStartValue($chargingPoint, $connectorId);
                $session->update([
                    'meter_values' => [
                        'start' => [
                            'value' => $meterStart,
                            'timestamp' => now()->toISOString(),
                        ],
                    ],
                ]);

                Log::info('Session postpayée démarrée avec succès', [
                    'session_id' => $session->session_id,
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'min_threshold' => $minThreshold,
                ]);

                // Émettre l'événement de démarrage
                event(new PostpaidSessionStarted($session));

                return [
                    'success' => true,
                    'session' => $session,
                    'message' => 'Session postpayée démarrée avec succès',
                    'session_id' => $session->session_id,
                    'monitoring_token' => $session->monitoring_token ?? null,
                ];

            } catch (\Exception $e) {
                Log::error('Erreur lors du démarrage de la session postpayée', [
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Erreur lors du démarrage: ' . $e->getMessage(),
                    'error' => 'start_session_failed',
                ];
            }
        });
    }

    /**
     * Mettre à jour la consommation en temps réel
     * 
     * @param ChargingSession $session
     * @return array
     */
    public function updateRealTimeConsumption(ChargingSession $session): array
    {
        try {
            if ($session->status !== 'in_progress') {
                return [
                    'success' => false,
                    'message' => 'La session n\'est pas en cours',
                ];
            }

            $chargingPoint = $session->chargingPoint;
            if (!$chargingPoint || !$chargingPoint->steve_charging_point_id) {
                return [
                    'success' => false,
                    'message' => 'Point de charge non configuré',
                ];
            }

            // 1. Vérifier d'abord si la borne a arrêté la recharge
            $isStopped = $this->checkIfChargingStopped($session, $chargingPoint);
            if ($isStopped) {
                Log::info('Détection automatique d\'arrêt de recharge depuis Steve', [
                    'session_id' => $session->session_id,
                    'charging_point_id' => $chargingPoint->id,
                ]);
                
                // Terminer automatiquement la session
                return $this->endPostpaidSession($session, [
                    'stop_reason' => 'auto_stop_detected',
                ]);
            }

            // 2. Récupérer les valeurs de compteur depuis Steve
            $transactionId = $session->metadata['steve_transaction_id'] ?? null;
            if (!$transactionId) {
                // Essayer de récupérer depuis les métadonnées de session
                $transactionId = $session->transaction_id ?? null;
            }

            if ($transactionId) {
                $meterValues = $this->getMeterValuesFromSteve($transactionId);
                
                if ($meterValues['success']) {
                    $this->updateSessionWithMeterValues($session, $meterValues['data']);
                }
            }

            // 3. Calculer la durée
            $duration = $session->started_at->diffInMinutes(now());
            
            // 4. Calculer le coût actuel
            $currentCost = $this->calculateCurrentCost($session);

            // 5. Mettre à jour la session
            $oldCost = $session->cost ?? 0;
            $oldEnergy = $session->energy_consumed ?? 0;
            
            $session->update([
                'duration' => $duration,
                'cost' => $currentCost,
            ]);

            // 6. Émettre l'événement de mise à jour si des changements significatifs
            if (abs($currentCost - $oldCost) > 0.01 || abs(($session->energy_consumed ?? 0) - $oldEnergy) > 0.01) {
                event(new PostpaidSessionUpdated($session, [
                    'cost_change' => $currentCost - $oldCost,
                    'energy_change' => ($session->energy_consumed ?? 0) - $oldEnergy,
                ]));
            }

            return [
                'success' => true,
                'session' => [
                    'session_id' => $session->session_id,
                    'duration_minutes' => $duration,
                    'energy_consumed_kwh' => $session->energy_consumed ?? 0,
                    'current_cost' => $currentCost,
                    'status' => $session->status,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la consommation', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Terminer une session postpayée et effectuer le paiement
     * 
     * @param ChargingSession $session
     * @param array $params ['stop_reason', 'meter_end']
     * @return array
     */
    public function endPostpaidSession(ChargingSession $session, array $params = []): array
    {
        return DB::transaction(function () use ($session, $params) {
            try {
                if ($session->status !== 'in_progress') {
                    return [
                        'success' => false,
                        'message' => 'La session n\'est pas en cours',
                        'error' => 'session_not_in_progress',
                    ];
                }

                // 1. Vérifier le statut de la borne depuis Steve
                $chargingPoint = $session->chargingPoint;
                $isStopped = $this->checkIfChargingStopped($session, $chargingPoint);
                
                // 2. Arrêter la recharge via Steve si elle n'est pas déjà arrêtée
                if (!$isStopped) {
                    $stopResult = $this->stopChargingViaSteve($session, $chargingPoint);
                    
                    if (!$stopResult['ok']) {
                        Log::warning('Échec de l\'arrêt via Steve, mais continuation du traitement', [
                            'session_id' => $session->session_id,
                            'steve_response' => $stopResult,
                        ]);
                    } else {
                        Log::info('Recharge arrêtée avec succès via Steve', [
                            'session_id' => $session->session_id,
                        ]);
                    }
                } else {
                    Log::info('La recharge était déjà arrêtée côté borne', [
                        'session_id' => $session->session_id,
                    ]);
                }

                // 3. Récupérer les valeurs finales du compteur depuis Steve
                $meterEnd = $params['meter_end'] ?? $this->getMeterEndValue($session);
                $meterStart = $session->meter_values['start']['value'] ?? 0;
                $energyConsumed = max(0, $meterEnd - $meterStart);

                // 4. Calculer la durée finale
                $duration = $session->started_at->diffInMinutes(now());

                // 5. Calculer le coût final avec tous les tarifs
                $finalCost = $this->calculateFinalCost($session, $energyConsumed, $duration);

                // 6. Mettre à jour la session
                $session->update([
                    'status' => 'completed',
                    'ended_at' => now(),
                    'energy_consumed' => $energyConsumed,
                    'duration' => $duration,
                    'cost' => $finalCost,
                    'stop_reason' => $params['stop_reason'] ?? 'user_stop',
                    'meter_values' => array_merge($session->meter_values ?? [], [
                        'end' => [
                            'value' => $meterEnd,
                            'timestamp' => now()->toISOString(),
                        ],
                    ]),
                ]);

                // Synchroniser avec les modèles Réservation et Transaction
                $reservationId = $session->metadata['reservation_id'] ?? null;
                $reservation = $reservationId ? \App\Models\Reservation::find($reservationId) : null;
                if ($reservation) {
                    $reservation->update([
                        'actual_cost' => $finalCost,
                        'actual_energy' => $energyConsumed,
                        'actual_duration' => $duration,
                        'charging_stopped_at' => now(),
                        'status' => \App\Enums\ReservationStatus::COMPLETED
                    ]);
                    if ($reservation->transaction) {
                        $reservation->transaction->update([
                            'status' => 'completed',
                            'stop_timestamp' => now(),
                            'energy_delivered' => $energyConsumed,
                            'price_total' => $finalCost
                        ]);
                    }
                }

                // 7. Débiter automatiquement le wallet ou la carte (UnifiedPaymentService)
                $paymentResult = $this->processPostpaidPayment($session);

                if (!$paymentResult['success']) {
                    // Si le paiement échoue par le wallet, on tente UnifiedPaymentService pour générer une demande de paiement Stripe
                    if ($reservation) {
                        try {
                            $unifiedService = app(\App\Services\UnifiedPaymentService::class);
                            // Set amount on reservation correctly so it generates invoice
                            $reservation->update(['estimated_cost' => $finalCost, 'amount' => $finalCost]);
                            $unifiedResult = $unifiedService->initiatePayment($reservation, 'stripe', ['postpaid_invoice' => true]);
                            
                            $session->update([
                                'payment_status' => 'pending_invoice',
                                'error_code' => 'wallet_insufficient',
                                'metadata' => array_merge($session->metadata ?? [], [
                                    'stripe_payment_url' => $unifiedResult['payment_url'] ?? $unifiedResult['redirect_url'] ?? null
                                ])
                            ]);
                            
                            // On ne retourne pas une erreur, juste que c'est facturé en attente
                            $paymentResult = [
                                'success' => true,
                                'pending_invoice' => true,
                                'message' => 'Envoyé pour paiement externe (Solde wallet insuffisant)',
                                'payment_url' => $unifiedResult['payment_url'] ?? $unifiedResult['redirect_url'] ?? null
                            ];
                        } catch (\Exception $e) {
                            $session->update([
                                'payment_status' => 'failed',
                                'error_code' => 'payment_failed',
                            ]);
                            return [
                                'success' => false,
                                'message' => 'Échec du paiement: ' . $paymentResult['message'],
                                'error' => 'payment_failed',
                                'session' => $session,
                            ];
                        }
                    } else {
                        $session->update([
                            'payment_status' => 'failed',
                            'error_code' => 'payment_failed',
                        ]);
                        return [
                            'success' => false,
                            'message' => 'Échec du paiement: ' . $paymentResult['message'],
                            'error' => 'payment_failed',
                            'session' => $session,
                        ];
                    }
                } else {
                    if ($reservation) {
                        $reservation->update(['payment_status' => 'PAID', 'payment_mode' => 'postpaid']);
                        if ($reservation->transaction) {
                            $reservation->transaction->update(['status' => 'completed']);
                        }
                    }
                }

                // 8. Générer la facture détaillée
                $invoice = $this->invoiceService->generateInvoice($session);

                Log::info('Session postpayée terminée avec succès', [
                    'session_id' => $session->session_id,
                    'final_cost' => $finalCost,
                    'energy_consumed' => $energyConsumed,
                    'duration' => $duration,
                ]);

                // Émettre l'événement de complétion
                event(new PostpaidSessionCompleted($session, $invoice, $paymentResult));

                return [
                    'success' => true,
                    'session' => $session,
                    'invoice' => $invoice,
                    'payment' => $paymentResult,
                    'summary' => [
                        'energy_consumed_kwh' => $energyConsumed,
                        'duration_minutes' => $duration,
                        'final_cost' => $finalCost,
                        'invoice_number' => $invoice['invoice_number'] ?? null,
                    ],
                    'message' => 'Session terminée et facturée avec succès',
                ];

            } catch (\Exception $e) {
                Log::error('Erreur lors de la fin de la session postpayée', [
                    'session_id' => $session->session_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Erreur lors de la fin de la session: ' . $e->getMessage(),
                    'error' => 'end_session_failed',
                ];
            }
        });
    }

    /**
     * Valider le wallet avant de démarrer une session postpayée
     * 
     * @param User $user
     * @param ChargingPoint $chargingPoint
     * @param array $params
     * @return array
     */
    protected function validateWalletBeforeStart(User $user, ChargingPoint $chargingPoint, array $params = []): array
    {
        try {
            // Récupérer ou créer le wallet (refresh pour solde à jour)
            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();
            
            // Déterminer le seuil minimum requis
            $minThreshold = $params['min_threshold'] ?? config('charging.postpaid_min_threshold', 10.00);
            
            // Vérifier le solde actuel
            $currentBalance = (float) $wallet->balance;
            $hasSufficientBalance = $wallet->hasSufficientBalance($minThreshold);
            
            if (!$hasSufficientBalance) {
                $missingBalance = $minThreshold - $currentBalance;
                $minimumRecharge = max($missingBalance, 5.00); // Minimum de recharge recommandé
                $recommendedRecharge = max($minThreshold * 2, 20.00); // Recharge recommandée (2x le seuil ou 20€)
                
                return [
                    'valid' => false,
                    'wallet' => $wallet,
                    'current_balance' => $currentBalance,
                    'required_balance' => $minThreshold,
                    'missing_balance' => $missingBalance,
                    'minimum_recharge' => round($minimumRecharge, 2),
                    'recommended_recharge' => round($recommendedRecharge, 2),
                    'message' => sprintf(
                        'Solde insuffisant pour démarrer une session postpayée. Solde actuel: %.2f EUR. Minimum requis: %.2f EUR. Veuillez recharger votre wallet d\'au moins %.2f EUR.',
                        $currentBalance,
                        $minThreshold,
                        $minimumRecharge
                    ),
                ];
            }
            
            // Vérification supplémentaire : s'assurer que le wallet est actif
            if (isset($wallet->status) && $wallet->status !== 'active') {
                return [
                    'valid' => false,
                    'wallet' => $wallet,
                    'current_balance' => $currentBalance,
                    'required_balance' => $minThreshold,
                    'missing_balance' => 0,
                    'minimum_recharge' => 0,
                    'recommended_recharge' => 0,
                    'message' => 'Votre wallet n\'est pas actif. Veuillez contacter le support.',
                ];
            }
            
            return [
                'valid' => true,
                'wallet' => $wallet,
                'current_balance' => $currentBalance,
                'required_balance' => $minThreshold,
                'missing_balance' => 0,
                'minimum_recharge' => 0,
                'recommended_recharge' => 0,
                'message' => 'Wallet validé avec succès',
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la validation du wallet', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'wallet' => null,
                'current_balance' => 0,
                'required_balance' => $params['min_threshold'] ?? config('charging.postpaid_min_threshold', 10.00),
                'missing_balance' => 0,
                'minimum_recharge' => 0,
                'recommended_recharge' => 0,
                'message' => 'Erreur lors de la validation du wallet: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier si la borne est disponible
     */
    protected function isChargingPointAvailable(ChargingPoint $chargingPoint): bool
    {
        // Vérifier le statut de la borne
        if ($chargingPoint->status !== 'online' && $chargingPoint->status !== 'available') {
            return false;
        }

        // Vérifier via Steve si disponible
        if ($chargingPoint->steve_charging_point_id) {
            $status = $this->steveService->getChargingPoint($chargingPoint->steve_charging_point_id);
            if (!$status['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifier si la recharge est arrêtée côté borne via l'API Steve
     * 
     * @param ChargingSession $session
     * @param ChargingPoint $chargingPoint
     * @return bool True si la recharge est arrêtée, false sinon
     */
    protected function checkIfChargingStopped(ChargingSession $session, ChargingPoint $chargingPoint): bool
    {
        try {
            if (!$chargingPoint->steve_charging_point_id) {
                return false; // Impossible de vérifier sans ID Steve
            }

            // 1. Vérifier le statut du connecteur depuis Steve
            $connectorStatus = $this->steveService->getConnectorStatus($chargingPoint->steve_charging_point_id);
            
            if ($connectorStatus['ok'] && is_array($connectorStatus['body'])) {
                $status = $this->extractChargingStatusFromResponse($connectorStatus['body']);
                
                // Statuts indiquant que la recharge est arrêtée
                $stoppedStatuses = ['Available', 'Preparing', 'Finishing', 'Unavailable', 'Faulted', 'Offline'];
                
                if (in_array($status, $stoppedStatuses)) {
                    Log::info('Recharge détectée comme arrêtée depuis le statut du connecteur', [
                        'session_id' => $session->session_id,
                        'status' => $status,
                    ]);
                    return true;
                }
            }

            // 2. Vérifier le statut de la transaction depuis Steve (si disponible)
            $transactionId = $session->metadata['steve_transaction_id'] ?? $session->transaction_id ?? null;
            
            if ($transactionId) {
                $transactionStatus = $this->getTransactionStatusFromSteve($transactionId);
                
                if ($transactionStatus['success'] && isset($transactionStatus['data'])) {
                    $transactionData = $transactionStatus['data'];
                    
                    // Vérifier si la transaction est terminée
                    if (isset($transactionData['stopTimestamp']) && !empty($transactionData['stopTimestamp'])) {
                        Log::info('Transaction détectée comme terminée depuis Steve', [
                            'session_id' => $session->session_id,
                            'transaction_id' => $transactionId,
                            'stop_timestamp' => $transactionData['stopTimestamp'],
                        ]);
                        return true;
                    }
                    
                    // Vérifier le statut de la transaction
                    if (isset($transactionData['status']) && in_array($transactionData['status'], ['Completed', 'Stopped', 'Finished'])) {
                        Log::info('Transaction détectée comme arrêtée depuis le statut', [
                            'session_id' => $session->session_id,
                            'transaction_id' => $transactionId,
                            'status' => $transactionData['status'],
                        ]);
                        return true;
                    }
                }
            }

            // 3. Vérifier le statut du point de charge
            $chargingPointStatus = $this->steveService->getChargingPoint($chargingPoint->steve_charging_point_id);
            
            if ($chargingPointStatus['ok'] && is_array($chargingPointStatus['body'])) {
                $cpStatus = $this->extractChargingStatusFromResponse($chargingPointStatus['body']);
                
                if (in_array($cpStatus, ['Offline', 'Unavailable', 'Faulted'])) {
                    Log::info('Point de charge détecté comme non disponible', [
                        'session_id' => $session->session_id,
                        'status' => $cpStatus,
                    ]);
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::warning('Erreur lors de la vérification du statut de recharge depuis Steve', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);
            
            // En cas d'erreur, ne pas considérer comme arrêté pour éviter les faux positifs
            return false;
        }
    }

    /**
     * Extraire le statut de recharge depuis la réponse de l'API Steve
     * 
     * @param array $responseBody
     * @return string
     */
    protected function extractChargingStatusFromResponse(array $responseBody): string
    {
        // Essayer différents champs possibles pour le statut
        $statusFields = [
            'status',
            'availabilityStatus',
            'connectorStatus',
            'state',
            'chargePointStatus',
        ];

        foreach ($statusFields as $field) {
            if (isset($responseBody[$field])) {
                $status = $responseBody[$field];
                if (is_string($status)) {
                    return $status;
                }
            }
        }

        // Si c'est un tableau de connecteurs, vérifier le premier
        if (isset($responseBody['connectors']) && is_array($responseBody['connectors']) && !empty($responseBody['connectors'])) {
            $firstConnector = $responseBody['connectors'][0];
            foreach ($statusFields as $field) {
                if (isset($firstConnector[$field])) {
                    return $firstConnector[$field];
                }
            }
        }

        return 'Unknown';
    }

    /**
     * Récupérer le statut d'une transaction depuis Steve
     * 
     * @param string $transactionId
     * @return array
     */
    protected function getTransactionStatusFromSteve(string $transactionId): array
    {
        try {
            $steveApiService = app(\App\Services\SteVeApiEndpointService::class);
            $result = $steveApiService->getTransaction($transactionId);
            
            return [
                'success' => $result['success'] ?? false,
                'data' => $result['data'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning('Impossible de récupérer le statut de la transaction depuis Steve', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
            ];
        }
    }

    /**
     * Arrêter la recharge via l'API Steve pour une ChargingSession
     * 
     * @param ChargingSession $session
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    protected function stopChargingViaSteve(ChargingSession $session, ChargingPoint $chargingPoint): array
    {
        try {
            if (empty($chargingPoint->steve_charging_point_id)) {
                return [
                    'ok' => false,
                    'error' => 'Charging point does not have a Steve ID configured',
                ];
            }

            // Récupérer le transaction ID depuis les métadonnées ou la session
            $transactionId = $session->metadata['steve_transaction_id'] 
                ?? $session->transaction_id 
                ?? $session->session_id;

            if (empty($transactionId)) {
                return [
                    'ok' => false,
                    'error' => 'Transaction ID not found',
                ];
            }

            // Construire les endpoints possibles
            $endpoints = [
                '/commands/remoteStopTransaction',
                '/charge-points/' . $chargingPoint->steve_charging_point_id . '/remote-stop',
                '/charge-points/' . $chargingPoint->steve_charging_point_id . '/commands/remoteStopTransaction',
            ];

            $payload = [
                'transactionId' => $transactionId,
            ];

            // Utiliser la méthode httpClient de SteveService
            $baseUrl = rtrim(config('services.steve.url', ''), '/');
            
            foreach ($endpoints as $endpoint) {
                try {
                    $url = $baseUrl . $endpoint;
                    
                    Log::info('PostpaidChargingService: Tentative d\'arrêt via Steve', [
                        'url' => $url,
                        'chargePointId' => $chargingPoint->steve_charging_point_id,
                        'transactionId' => $transactionId,
                    ]);

                    // Utiliser Http avec les mêmes headers que SteveService
                    $response = \Illuminate\Support\Facades\Http::timeout(10.0)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])
                        ->withBasicAuth(
                            config('services.steve.user', ''),
                            config('services.steve.pass', '')
                        )
                        ->post($url, $payload);

                    $responseJson = $response->json();
                    $status = $responseJson['status'] ?? ($response->successful() ? 'Accepted' : 'Rejected');

                    $responseData = [
                        'ok' => $response->successful() && ($status === 'Accepted' || $status === 'accepted'),
                        'status' => $response->status(),
                        'body' => $responseJson,
                        'steve_status' => $status,
                        'endpoint' => $endpoint,
                    ];

                    if ($response->successful() && ($status === 'Accepted' || $status === 'accepted')) {
                        Log::info('PostpaidChargingService: Recharge arrêtée avec succès via Steve', [
                            'session_id' => $session->session_id,
                            'transactionId' => $transactionId,
                            'steve_status' => $status,
                        ]);
                        return $responseData;
                    }

                    if ($status === 'Rejected' || $status === 'rejected') {
                        Log::warning('PostpaidChargingService: Arrêt rejeté par Steve', [
                            'session_id' => $session->session_id,
                            'steve_status' => $status,
                        ]);
                        return $responseData;
                    }

                } catch (\Exception $e) {
                    Log::debug("PostpaidChargingService: Erreur avec endpoint {$endpoint}: " . $e->getMessage());
                    continue;
                }
            }

            return [
                'ok' => false,
                'error' => 'All endpoints failed',
            ];

        } catch (\Exception $e) {
            Log::error('PostpaidChargingService: Erreur lors de l\'arrêt via Steve', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Récupérer la valeur initiale du compteur
     */
    protected function getMeterStartValue(ChargingPoint $chargingPoint, int $connectorId): float
    {
        // Essayer de récupérer depuis Steve
        if ($chargingPoint->steve_charging_point_id) {
            // Logique pour récupérer la valeur du compteur depuis Steve
            // Pour l'instant, retourner 0
        }

        return 0.0;
    }

    /**
     * Récupérer la valeur finale du compteur
     */
    protected function getMeterEndValue(ChargingSession $session): float
    {
        $transactionId = $session->metadata['steve_transaction_id'] ?? $session->transaction_id ?? null;
        
        if ($transactionId) {
            $meterValues = $this->getMeterValuesFromSteve($transactionId);
            if ($meterValues['success'] && !empty($meterValues['data'])) {
                // Extraire la dernière valeur de compteur
                $lastValue = end($meterValues['data']);
                return $lastValue['value'] ?? 0;
            }
        }

        // Fallback: utiliser la consommation calculée
        return ($session->meter_values['start']['value'] ?? 0) + ($session->energy_consumed ?? 0);
    }

    /**
     * Récupérer les valeurs de compteur depuis Steve
     */
    protected function getMeterValuesFromSteve(string $transactionId): array
    {
        try {
            // Utiliser SteVeApiEndpointService si disponible
            $steveApiService = app(\App\Services\SteVeApiEndpointService::class);
            $result = $steveApiService->getMeterValues($transactionId);
            
            return [
                'success' => $result['success'] ?? false,
                'data' => $result['data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::warning('Impossible de récupérer les valeurs de compteur depuis Steve', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => [],
            ];
        }
    }

    /**
     * Mettre à jour la session avec les valeurs de compteur
     */
    protected function updateSessionWithMeterValues(ChargingSession $session, array $meterValues): void
    {
        if (empty($meterValues)) {
            return;
        }

        $lastValue = end($meterValues);
        $meterStart = $session->meter_values['start']['value'] ?? 0;
        $currentValue = $lastValue['value'] ?? $lastValue['meterValue'] ?? 0;
        $energyConsumed = max(0, $currentValue - $meterStart);

        $session->update([
            'energy_consumed' => $energyConsumed,
            'meter_values' => array_merge($session->meter_values ?? [], [
                'latest' => [
                    'value' => $currentValue,
                    'timestamp' => $lastValue['timestamp'] ?? now()->toISOString(),
                ],
                'updates' => $meterValues,
            ]),
        ]);
    }

    /**
     * Calculer le coût actuel de la session
     */
    protected function calculateCurrentCost(ChargingSession $session): float
    {
        $duration = $session->started_at->diffInMinutes(now());
        $energyConsumed = $session->energy_consumed ?? 0;

        return $this->calculateFinalCost($session, $energyConsumed, $duration);
    }

    /**
     * Calculer le coût final avec tous les tarifs (prix/min, prix/kWh, prix fixe, TVA)
     */
    protected function calculateFinalCost(ChargingSession $session, float $energyConsumed, float $duration): float
    {
        try {
            $chargingPoint = $session->chargingPoint;
            if (!$chargingPoint) {
                return 0;
            }

            // Utiliser le service de calcul de coût amélioré
            $costBreakdown = ChargingSessionCostService::calculateDetailedCost(
                $chargingPoint,
                $energyConsumed,
                $duration
            );

            return $costBreakdown['total_with_vat'] ?? $costBreakdown['total'] ?? 0;

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul du coût final', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Traiter le paiement postpayé
     */
    protected function processPostpaidPayment(ChargingSession $session): array
    {
        try {
            $wallet = $session->getUserWallet();
            if (!$wallet) {
                return [
                    'success' => false,
                    'message' => 'Wallet utilisateur introuvable',
                ];
            }

            // Vérifier le solde
            if (!$wallet->hasSufficientBalance($session->cost)) {
                return [
                    'success' => false,
                    'message' => 'Solde insuffisant pour effectuer le paiement',
                    'required' => $session->cost,
                    'available' => $wallet->balance,
                ];
            }

            // Débiter le wallet
            $transaction = $wallet->debit(
                $session->cost,
                "Paiement session postpayée - {$session->session_id}",
                [
                    'session_id' => $session->session_id,
                    'charging_point_id' => $session->charging_point_id,
                    'mode' => 'postpaid',
                    'energy_consumed' => $session->energy_consumed,
                    'duration' => $session->duration,
                ]
            );

            $session->update([
                'payment_status' => 'paid',
            ]);

            return [
                'success' => true,
                'transaction' => $transaction,
                'amount' => $session->cost,
                'new_balance' => $wallet->fresh()->balance,
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors du paiement postpayé', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du paiement: ' . $e->getMessage(),
            ];
        }
    }
}

