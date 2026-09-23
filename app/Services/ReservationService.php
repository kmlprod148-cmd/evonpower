<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Services\FinancialService;
use App\Services\CreditPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CMIReservationPaymentService;
use Carbon\Carbon;

class ReservationService
{
    protected $financialService;
    protected $creditPaymentService;

    public function __construct(FinancialService $financialService, CreditPaymentService $creditPaymentService)
    {
        $this->financialService = $financialService;
        $this->creditPaymentService = $creditPaymentService;
    }

    public function createReservation(array $validatedData, $chargingPoint = null)
    {
        \Log::info('ReservationService createReservation called', [
            'validated_data' => $validatedData,
            'charging_point_id' => $chargingPoint ? $chargingPoint->id : null,
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check()
        ]);
        
        $validated = $validatedData;
        $user = Auth::user();

        // Vérification pour les clients : balance ne doit pas être négative
        if ($user && $user->hasRole('client')) {
            $wallet = $user->getOrCreateWallet();
            $wallet->refresh();
            $currentBalance = (float) $wallet->balance;
            
            if ($currentBalance < 0) {
                \Log::warning('Client attempted to create reservation with negative balance', [
                    'user_id' => $user->id,
                    'balance' => $currentBalance
                ]);
                
                return [
                    'status' => 'error',
                    'message' => 'Votre solde est insuffisant. Veuillez recharger votre compte avant de créer une réservation.',
                    'error' => 'insufficient_balance_negative'
                ];
            }
        }

        try {
            DB::beginTransaction();

            // Si le chargingPoint n'est pas fourni, le récupérer depuis les données validées
            if (!$chargingPoint) {
                $chargingPoint = ChargingPoint::with('group')->findOrFail($validated['charging_point_id']);
            } elseif (!$chargingPoint->relationLoaded('group')) {
                $chargingPoint->load('group');
            }
            $pricingPlan   = PricingPlan::findOrFail($validated['pricing_plan_id']);

            // VALIDATION CRITIQUE: Vérifier la limite maximale de durée par réservation
            // Cette validation doit être faite AVANT tout calcul financier pour éviter les erreurs
            $reservationType = $validated['reservation_type'] ?? null;
            $reservationValue = (float) ($validated['reservation_value'] ?? 0);
            
            if ($reservationType === 'minute' && $pricingPlan->max_duration) {
                // Pour les réservations en minutes, vérifier que la valeur ne dépasse pas max_duration
                if ($reservationValue > $pricingPlan->max_duration) {
                    \Log::warning('Reservation limit exceeded', [
                        'reservation_value' => $reservationValue,
                        'max_duration' => $pricingPlan->max_duration,
                        'pricing_plan_id' => $pricingPlan->id,
                        'charging_point_id' => $chargingPoint->id
                    ]);
                    
                    DB::rollBack();
                    return [
                        'status' => 'error',
                        'message' => "La durée de réservation ne peut pas dépasser {$pricingPlan->max_duration} minutes selon le plan tarifaire associé à ce point de charge.",
                        'error' => 'limit_exceeded',
                        'max_duration' => $pricingPlan->max_duration
                    ];
                }
            } elseif ($reservationType === 'kwh' && $pricingPlan->max_duration && $chargingPoint->power_output) {
                // Pour les réservations en kWh, calculer la durée équivalente et vérifier
                $estimatedDurationFromEnergy = ($reservationValue / $chargingPoint->power_output) * 60;
                if ($estimatedDurationFromEnergy > $pricingPlan->max_duration) {
                    $maxEnergy = $chargingPoint->power_output * ($pricingPlan->max_duration / 60);
                    \Log::warning('Reservation energy limit exceeded', [
                        'reservation_value_kwh' => $reservationValue,
                        'max_energy_kwh' => $maxEnergy,
                        'estimated_duration' => $estimatedDurationFromEnergy,
                        'max_duration' => $pricingPlan->max_duration,
                        'pricing_plan_id' => $pricingPlan->id,
                        'charging_point_id' => $chargingPoint->id
                    ]);
                    
                    DB::rollBack();
                    return [
                        'status' => 'error',
                        'message' => "La quantité d'énergie ne peut pas dépasser " . number_format($maxEnergy, 2) . " kWh (équivalent à {$pricingPlan->max_duration} minutes) selon le plan tarifaire associé à ce point de charge.",
                        'error' => 'limit_exceeded',
                        'max_duration' => $pricingPlan->max_duration,
                        'max_energy' => $maxEnergy
                    ];
                }
            }

            // Calculate costs and commissions
            $financials = $this->financialService->calculateAndDistributeCommissions($pricingPlan, $validated);
            
            \Log::info('Financial calculation result', [
                'financials' => $financials,
                'pricing_plan_id' => $pricingPlan->id,
                'validated_data' => $validated
            ]);
            
            // Validate financial calculation
            if (!isset($financials['total_price']) || $financials['total_price'] <= 0) {
                \Log::error('Invalid financial calculation', [
                    'financials' => $financials,
                    'pricing_plan_id' => $pricingPlan->id,
                    'validated_data' => $validated
                ]);
                throw new \Exception('Invalid financial calculation: total_price is missing or invalid');
            }
            
            // Vérification pour les clients (utilisateurs avec rôle 'user' ou 'client')
            $userRoles = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
            $clientRoles = ['user', 'client'];
            $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
            $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
            $isClientOnly = $user && !$hasSystemRole && !empty(array_intersect($userRoles, $clientRoles));
            
            if ($isClientOnly) {
                $wallet = $user->getOrCreateWallet();
                $wallet->refresh();
                $totalPrice = (float) $financials['total_price'];
                $currentBalance = (float) $wallet->balance;
                
                // Si le solde est insuffisant et que la méthode de paiement n'est pas CMI ou Stripe
                if ($currentBalance < $totalPrice) {
                    $paymentMethod = $validated['payment_method'] ?? $validated['payment_type'] ?? 'credit';
                    
                    // Si la méthode est 'credit' et le solde est insuffisant, permettre CMI/Stripe
                    if ($paymentMethod === 'credit' || $paymentMethod === 'prepaid_credit') {
                        \Log::info('Client has insufficient balance, allowing CMI/Stripe payment', [
                            'user_id' => $user->id,
                            'balance' => $currentBalance,
                            'required' => $totalPrice,
                            'shortage' => $totalPrice - $currentBalance,
                            'payment_method' => $paymentMethod
                        ]);
                        
                        // Ne pas bloquer, permettre le paiement via CMI/Stripe
                        // Le paiement sera traité lors de la confirmation
                        $validated['payment_method'] = 'cmi'; // Par défaut, proposer CMI
                        $validated['payment_type'] = 'cmi';
                        $validated['requires_payment'] = true; // Flag pour indiquer qu'un paiement est requis
                    }
                } else {
                    // Solde suffisant : utiliser le crédit
                    if ($validated['payment_method'] !== 'credit' && $validated['payment_method'] !== 'prepaid_credit') {
                        \Log::info('Client payment method changed to credit (balance sufficient)', [
                            'user_id' => $user->id,
                            'original_method' => $validated['payment_method'],
                            'balance' => $currentBalance,
                            'required' => $totalPrice
                        ]);
                        $validated['payment_method'] = 'credit';
                        $validated['payment_type'] = 'credit';
                    }
                }
            }
            
            // Vérification supplémentaire des données critiques
            if (!$chargingPoint || !$pricingPlan) {
                throw new \Exception('Missing critical data: charging point or pricing plan not found');
            }

            // Vérification supplémentaire avant création de l'ordre
            if (!$chargingPoint->id || !$validated['pricing_plan_id']) {
                throw new \Exception('Missing critical IDs for order creation');
            }
            
            $order = Order::create([
                'user_id' => $user ? $user->id : null, // Permettre null pour les réservations publiques
                'plan_id' => $validated['pricing_plan_id'],
                'charging_point_id' => $chargingPoint->id,
                'amount' => (float) $financials['total_price'],
                'status' => 'pending',
                'details' => json_encode($validated),
            ]);
            
            // Vérification que l'ordre a été créé
            if (!$order || !$order->id) {
                throw new \Exception('Failed to create order');
            }

            // Handle 'immediate' start time or parse the provided time
            if (!empty($validated['start_time'])) {
                if ($validated['start_time'] === 'immediate') {
                    $startTime = now();
                } else {
                    try {
                        $startTime = Carbon::parse($validated['start_time']);
                    } catch (\Exception $e) {
                        \Log::warning('Invalid start_time format, using current time', [
                            'start_time' => $validated['start_time'],
                            'error' => $e->getMessage()
                        ]);
                        $startTime = now();
                    }
                }
            } else {
                $startTime = now();
            }
            $endTime = null;
            
            // Calculate estimated duration and energy based on reservation type
            $estimatedDuration = 0;
            $estimatedEnergy = 0;
            
            if ($validated['reservation_type'] === 'kwh') {
                $estimatedEnergy = (float) $validated['reservation_value'];
                // Calculate duration based on charging point power
                if ($chargingPoint->power_output && $chargingPoint->power_output > 0) {
                    $estimatedDuration = round(($estimatedEnergy / $chargingPoint->power_output) * 60);
                }
                $endTime = $startTime->copy()->addMinutes((int) $estimatedDuration);
            } elseif ($validated['reservation_type'] === 'minute') {
                $estimatedDuration = (float) $validated['reservation_value'];
                // Calculate energy based on charging point power
                if ($chargingPoint->power_output && $chargingPoint->power_output > 0) {
                    $estimatedEnergy = round(($estimatedDuration / 60) * $chargingPoint->power_output, 2);
                }
                $endTime = $startTime->copy()->addMinutes((int) $estimatedDuration);
            }

            // Statut initial : pending_confirmation pour offline (approbation manuelle requise)
            // Pour credit/prepaid_credit : on garde 'pending' car processPrepaidPayment va immédiatement
            // confirmer + débiter + approuver automatiquement (pas de bouton Approuver)
            $paymentMethod = $validated['payment_method'] ?? ($validated['payment_type'] ?? 'cmi');
            $initialStatus = $validated['status'] ?? 'pending';
            $isCreditPayment = in_array($paymentMethod, ['credit', 'prepaid_credit', 'postpaid_credit']);
            if (in_array($paymentMethod, ['offline']) && $user) {
                $initialStatus = 'pending_confirmation';
            } elseif ($isCreditPayment && $user) {
                // Prépayé par solde : pas de pending_confirmation, on traite immédiatement
                $initialStatus = 'pending';
            }
            
            // Validation des données de réservation avant création
            $reservationData = [
                'user_id'           => $user ? $user->id : null,
                'charging_point_id' => $chargingPoint->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'reservation_type'  => $validated['reservation_type'],
                'reservation_value' => (float) ($validated['reservation_value'] ?? 0),
                'estimated_duration'  => (float) $estimatedDuration,
                'estimated_energy'  => (float) $estimatedEnergy,
                // Remove energy_kwh and duration_minutes as they don't exist in the table
                // The data is already stored in estimated_energy and estimated_duration above
                'estimated_cost'    => (float) $financials['total_price'],
                'max_duration'      => $pricingPlan->max_duration ? (float) $pricingPlan->max_duration : null,
                'max_energy'        => $pricingPlan->max_duration && $chargingPoint->power_output ? 
                    (float) (($pricingPlan->max_duration / 60) * $chargingPoint->power_output) : null,
                'status'            => $initialStatus,
                'payment_status'    => 'PENDING',
                'payment_method'     => $paymentMethod,
                'payment_type'       => in_array($paymentMethod, ['cmi', 'offline']) ? $paymentMethod : 'cmi',
                'payment_mode'       => $paymentMethod === 'postpaid_credit' ? 'postpaid' : ($isCreditPayment ? 'prepaid' : null),
                'start_time'        => $startTime,
                'end_time'          => $endTime,
                'order_id'          => $order->id,
                'guest_email'       => $validated['guest_email'] ?? null,
                'guest_phone'       => $validated['guest_phone'] ?? null,
            ];
            
            // Vérification des données critiques
            if (!$reservationData['charging_point_id'] || !$reservationData['pricing_plan_id']) {
                throw new \Exception('Missing critical reservation data');
            }
            
            if (!$reservationData['reservation_type'] || !$reservationData['reservation_value']) {
                throw new \Exception('Invalid reservation type or value');
            }
            
            \Log::info('Reservation data:', $reservationData);
            $reservation = Reservation::create($reservationData);
            
            // Vérification que la réservation a été créée
            if (!$reservation || !$reservation->id) {
                throw new \Exception('Failed to create reservation');
            }

            // Synchroniser les participants (parts) si fournis, sinon créer un participant par défaut
            try {
                $participantService = app(\App\Services\ReservationParticipantService::class);
                $participantService->syncParticipants(
                    $reservation,
                    $validated['participants'] ?? null,
                    (float) $financials['total_price']
                );
            } catch (\Throwable $e) {
                \Log::warning('ReservationService: sync participants failed', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Créer la transaction avec user_id null pour les réservations publiques
            // Use withoutEvents to prevent observer from firing during transaction creation
            
            \Log::info('Creating transaction', [
                'reservation_id' => $reservation->id,
                'order_id' => $order->id,
                'total_price' => $financials['total_price']
            ]);
            
            // Utiliser le TransactionService pour créer la transaction de manière cohérente
            $transactionService = app(\App\Services\TransactionService::class);
            // Pour paiement crédit : statut pending car processPrepaidPayment va immédiatement débiter et mettre completed
            $initialTransactionStatus = 'pending';
            $transactionData = [
                'user_id' => $user ? $user->id : null, // Permettre null pour les réservations publiques
                'order_id' => $order->id,
                'reservation_id' => $reservation->id, // Associer la transaction à la réservation
                'charging_point_id' => $chargingPoint->id,
                'pricing_plan_id' => $validated['pricing_plan_id'],
                'amount' => (float) $financials['total_price'],
                'price_total' => (float) $financials['total_price'], // Ensure price_total is set
                'status' => $initialTransactionStatus,
                'admin_commission' => (float) $financials['admin_commission'],
                'integrator_commission' => (float) $financials['integrator_commission'],
                'partner_commission' => (float) $financials['partner_commission'],
                'price_details' => json_encode($financials['price_details']),
                'meter_start' => 0, // Added default value for meter_start
                // Add transaction_type for internal logic (not stored in DB)
                'transaction_type' => 'client'
            ];
            
            $transaction = $transactionService->create($transactionData);
            
            \Log::info('Transaction created successfully', [
                'transaction_id' => $transaction->id,
                'reservation_id' => $transaction->reservation_id
            ]);

            // Traiter le paiement par crédit : toujours prépayé (débit immédiat + auto-approbation)
            if ($isCreditPayment && Auth::check()) {
                try {
                    // Paiement par solde = débit immédiat + réservation approuvée automatiquement
                    $paymentResult = $this->creditPaymentService->processPrepaidPayment($reservation);
                    
                    if (!$paymentResult['success']) {
                        DB::rollBack();
                        return [
                            'status' => 'error',
                            'message' => $paymentResult['error'] ?? 'Erreur lors du paiement par crédit',
                            'error' => 'credit_payment_failed'
                        ];
                    }

                    // Mettre à jour la réservation (déjà fait par CreditPaymentService : confirmée + approuvée)
                    $reservation->refresh();
                    
                    // Mettre à jour le statut de la transaction
                    $transaction->status = 'completed';
                    $transaction->save();
                    
                    // Mettre à jour le statut de la commande
                    $order->status = 'paid';
                    $order->save();

                    DB::commit();
                    
                    // Apply revenue distribution after transaction is committed
                    try {
                        $revenueService = app(\App\Services\ReservationRevenueDistributionService::class);
                        $revenueService->applyRevenueDistributionToTransaction($transaction);
                    } catch (\Exception $e) {
                        \Log::error('Error applying revenue distribution after credit payment: ' . $e->getMessage(), [
                            'transaction_id' => $transaction->id,
                            'reservation_id' => $reservation->id,
                            'error' => $e
                        ]);
                    }

                    Log::info('Paiement par crédit traité avec succès', [
                        'reservation_id' => $reservation->id,
                        'user_id' => Auth::id(),
                        'amount' => $financials['total_price'],
                        'remaining_balance' => $paymentResult['remaining_balance'] ?? 0
                    ]);

                    return [
                        'status' => 'success',
                        'message' => 'Réservation créée et payée avec succès',
                        'order_id' => $order->id,
                        'reservation' => $reservation,
                        'payment_processed' => true,
                        'remaining_balance' => $paymentResult['remaining_balance'] ?? 0,
                        'formatted_balance' => $paymentResult['formatted_balance'] ?? null
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur paiement par crédit', [
                        'reservation_id' => $reservation->id,
                        'user_id' => Auth::id(),
                        'error' => $e->getMessage()
                    ]);
                    
                    return [
                        'status' => 'error',
                        'message' => 'Erreur lors du paiement par crédit: ' . $e->getMessage(),
                        'error' => 'credit_payment_error'
                    ];
                }
            } elseif ($paymentMethod === 'cmi' && Route::has('payment.success')) {
                // Prepare payment data for CMI
                $paymentData = [
                    'amount' => $financials['total_price'],
                    'currency' => 'EUR', // Assuming EUR as currency, adjust if needed
                    'billToName' => $user ? $user->name : 'Guest',
                    'billToEmail' => $user ? $user->email : $validated['guest_email'],
                    'oid' => $order->id, // Order ID as unique identifier for payment
                    'rnd' => microtime(true), // Random number
                    'shopurl' => route('payment.cmi.callback'), // Callback URL after payment
                    'okUrl' => route('payment.success', ['order' => $order->id]), // Success URL
                    'failUrl' => route('payment.fail', ['order' => $order->id]), // Failure URL
                    'hashAlgorithm' => 'SHA512', // Or SHA256 based on CMI configuration
                ];

                try {
                    // Utiliser CMIReservationPaymentService pour initier le paiement CMI
                    $cmiService = app(CMIReservationPaymentService::class);
                    
                    // Préparer les données de paiement selon l'API CMI
                    $cmiPaymentData = $cmiService->preparePaymentData($reservation, $paymentData);
                    $paymentUrl = $cmiService->getPaymentUrl();
                    
                    // Générer l'URL de redirection vers la page d'envoi des données CMI
                    $redirectUrl = route('payment.cmi.reservation.send', $reservation->id);

                    // Update order and transaction status to 'pending_payment'
                    $order->update(['status' => 'pending_payment']);
                    $transaction->update(['status' => 'pending_payment']);

                    DB::commit();
                    
                    // Apply revenue distribution after transaction is committed
                    try {
                        $revenueService = app(\App\Services\ReservationRevenueDistributionService::class);
                        $revenueService->applyRevenueDistributionToTransaction($transaction);
                    } catch (\Exception $e) {
                        \Log::error('Error applying revenue distribution after CMI payment initiation: ' . $e->getMessage(), [
                            'transaction_id' => $transaction->id,
                            'reservation_id' => $reservation->id,
                            'error' => $e
                        ]);
                    }

                    return ['status' => 'redirect', 'url' => $redirectUrl];

                } catch (\Exception $e) {
                    DB::rollBack();
                    return ['status' => 'error', 'message' => 'Payment initiation failed.', 'error' => $e->getMessage()];
                }
            } else {
                // For offline payments or if payment.success route is not defined, just commit the transaction and return a success response
                DB::commit();
                
                // Apply revenue distribution after transaction is committed
                try {
                    $revenueService = app(\App\Services\ReservationRevenueDistributionService::class);
                    $revenueService->applyRevenueDistributionToTransaction($transaction);
                } catch (\Exception $e) {
                    \Log::error('Error applying revenue distribution after reservation creation: ' . $e->getMessage(), [
                        'transaction_id' => $transaction->id,
                        'reservation_id' => $reservation->id,
                        'error' => $e
                    ]);
                    
                    // Ne pas faire échouer la réservation pour une erreur de répartition des revenus
                    // La réservation est créée avec succès, la répartition peut être recalculée plus tard
                }
                
                return [
                    'status' => 'success',
                    'message' => 'Votre réservation a été créée avec succès. Le paiement se fera sur place.',
                    'order_id' => $order->id,
                    'reservation' => $reservation
                ];
            }

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            // Gestion spécifique des erreurs de base de données
            $errorMessage = 'Erreur de base de données lors de la création de la réservation.';
            $errorCode = 'database_error';
            
            if (str_contains($e->getMessage(), 'Connection refused') || 
                str_contains($e->getMessage(), 'server has gone away') ||
                str_contains($e->getMessage(), 'Lost connection')) {
                $errorMessage = 'Erreur de connexion à la base de données. Le serveur peut être temporairement indisponible.';
                $errorCode = 'connection_error';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'Délai d\'attente dépassé lors de la création de la réservation.';
                $errorCode = 'timeout_error';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage = 'Une réservation similaire existe déjà.';
                $errorCode = 'duplicate_error';
            }
            
            \Log::error('Database error creating reservation: ' . $e->getMessage(), [
                'validated_data' => $validated,
                'charging_point_id' => $chargingPoint ? $chargingPoint->id : null,
                'user_id' => $user ? $user->id : null,
                'error_code' => $errorCode,
                'exception' => $e
            ]);
            
            return [
                'status' => 'error',
                'message' => $errorMessage,
                'error' => $errorCode,
                'details' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating reservation: ' . $e->getMessage(), [
                'validated_data' => $validated,
                'charging_point_id' => $chargingPoint ? $chargingPoint->id : null,
                'user_id' => $user ? $user->id : null,
                'exception' => $e
            ]);
            
            return [
                'status' => 'error',
                'message' => 'Une erreur inattendue est survenue lors de la création de la réservation.',
                'error' => 'unexpected_error',
                'details' => $e->getMessage()
            ];
        }
    }
}
