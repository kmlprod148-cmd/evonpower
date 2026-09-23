<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\ChargingSession;
use App\Models\Payment;
use App\Enums\ChargingSessionStatus;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service pour gérer les flux de paiement prépayés via QR Code
 * Gère la création, confirmation, et remboursement des réservations prépayées
 */
class PrepaidPaymentService
{
    protected CMIReservationPaymentService $cmiPaymentService;
    protected StripePaymentService $stripePaymentService;
    protected WalletService $walletService;
    protected PricingService $pricingService;
    protected QRCodeService $qrCodeService;

    public function __construct(
        CMIReservationPaymentService $cmiPaymentService,
        StripePaymentService $stripePaymentService,
        WalletService $walletService,
        PricingService $pricingService,
        QRCodeService $qrCodeService
    ) {
        $this->cmiPaymentService = $cmiPaymentService;
        $this->stripePaymentService = $stripePaymentService;
        $this->walletService = $walletService;
        $this->pricingService = $pricingService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Créer une réservation prépayée avec paiement
     * 
     * @param array $data Données de la réservation
     * @param string $paymentGateway 'cmi' ou 'stripe'
     * @return array
     */
    public function createPrepaidReservation(array $data, string $paymentGateway = 'cmi'): array
    {
        return DB::transaction(function () use ($data, $paymentGateway) {
            try {
                // 1. Validation des données
                $validation = $this->validateReservationData($data);
                if (!$validation['valid']) {
                    return $validation;
                }

                // 2. Récupérer la borne et le plan tarifaire
                $chargingPoint = \App\Models\ChargingPoint::with(['pricingPlan', 'group'])
                    ->findOrFail($data['charging_point_id']);
                
                $pricingPlan = $chargingPoint->pricingPlan;
                if (!$pricingPlan) {
                    return ['success' => false, 'error' => 'Plan tarifaire non disponible'];
                }

                // 3. Calculer le coût estimé
                $estimatedCost = $this->pricingService->calculateEstimatedCost(
                    $data['reservation_type'], // 'energy' ou 'duration'
                    $data['reservation_value'],
                    $pricingPlan
                );

                // 4. Créer la réservation
                $reservation = new Reservation();
                $reservation->charging_point_id = $chargingPoint->id;
                $reservation->pricing_plan_id = $pricingPlan->id;
                $reservation->reservation_type = $data['reservation_type'] === 'energy' ? 'kwh' : 'minute';
                $reservation->reservation_value = $data['reservation_value'];
                $reservation->estimated_cost = $estimatedCost;
                $reservation->amount = $estimatedCost;
                $reservation->prepaid_amount = $estimatedCost;
                $reservation->status = ReservationStatus::PENDING;
                $reservation->payment_mode = 'prepaid';
                $reservation->payment_method = $paymentGateway;
                $reservation->payment_type = $paymentGateway;
                $reservation->payment_status = 'PENDING';

                // Informations client
                $reservation->guest_name = $data['customer_name'] ?? null;
                $reservation->guest_email = $data['customer_email'] ?? null;
                $reservation->guest_phone = $data['customer_phone'] ?? null;
                
                // Horodatages
                $reservation->start_time = isset($data['start_time']) 
                    ? Carbon::parse($data['start_time']) 
                    : now();

                // Créer l'utilisateur si nécessaire et demandé
                if (!empty($data['register_account']) && !empty($data['customer_email'])) {
                    $user = $this->createOrGetCustomerUser($data);
                    if ($user) {
                        $reservation->user_id = $user->id;
                    }
                }

                if (!$reservation->save()) {
                    throw new \Exception('Impossible de créer la réservation');
                }

                // 5. Créer la transaction associée
                $transaction = $this->createTransaction($reservation, $chargingPoint, $pricingPlan, $estimatedCost);

                // 6. Générer le paiement initial (en attente)
                $payment = $this->createPayment($reservation, $transaction, $estimatedCost, $paymentGateway);

                // 7. Générer l'URL de paiement selon la passerelle
                $paymentUrl = $this->generatePaymentUrl($reservation, $transaction, $paymentGateway);

                Log::info('Réservation prépayée créée', [
                    'reservation_id' => $reservation->id,
                    'estimated_cost' => $estimatedCost,
                    'payment_gateway' => $paymentGateway,
                ]);

                return [
                    'success' => true,
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transaction->id,
                    'payment_id' => $payment->id,
                    'estimated_cost' => $estimatedCost,
                    'payment_url' => $paymentUrl,
                    'currency' => $pricingPlan->currency ?? 'EUR',
                ];

            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de la réservation prépayée', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Confirmer le paiement d'une réservation prépayée
     * Called par le webhook de paiement
     * 
     * @param Reservation $reservation
     * @param string $transactionRef Référence de transaction de la passerelle
     * @return array
     */
    public function confirmPrepaidPayment(Reservation $reservation, string $transactionRef): array
    {
        return DB::transaction(function () use ($reservation, $transactionRef) {
            try {
                // Mettre à jour le statut de la réservation
                $reservation->status = ReservationStatus::CONFIRMED;
                $reservation->payment_status = 'PAID';
                $reservation->confirmed_at = now();
                
                // Sauvegarder la référence de transaction
                $reservation->payment_transaction_ref = $transactionRef;
                $reservation->save();

                // Mettre à jour la transaction
                $transaction = $reservation->transaction;
                if ($transaction) {
                    $transaction->status = TransactionStatus::CONFIRMED;
                    $transaction->save();
                }

                // Mettre à jour le paiement
                $payment = $reservation->payment;
                if ($payment) {
                    $payment->status = 'completed';
                    $payment->transaction_ref = $transactionRef;
                    $payment->paid_at = now();
                    $payment->save();
                }

                // Générer le token d'activation pour la borne
                $activationToken = $this->generateActivationToken($reservation);

                // Envoyer la confirmation par email/SMS
                $this->sendConfirmationNotifications($reservation);

                Log::info('Paiement prépayé confirmé', [
                    'reservation_id' => $reservation->id,
                    'transaction_ref' => $transactionRef,
                ]);

                return [
                    'success' => true,
                    'reservation' => $reservation,
                    'activation_token' => $activationToken,
                ];

            } catch (\Exception $e) {
                Log::error('Erreur lors de la confirmation du paiement prépayé', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Calculer et traiter le remboursement à la fin de la session
     * 
     * @param Reservation $reservation
     * @param float $actualConsumption Energie réellement consommée en kWh
     * @param int $actualDuration Durée réelle en minutes
     * @return array
     */
    public function processRefund(Reservation $reservation, float $actualConsumption, int $actualDuration): array
    {
        return DB::transaction(function () use ($reservation, $actualConsumption, $actualDuration) {
            try {
                // Calculer le coût réel
                $pricingPlan = $reservation->pricingPlan;
                $actualCost = $this->pricingService->calculateActualCost(
                    $actualConsumption,
                    $actualDuration,
                    $pricingPlan
                );

                // Mettre à jour la réservation avec les valeurs réelles
                $reservation->actual_energy = $actualConsumption;
                $reservation->actual_duration = $actualDuration;
                $reservation->actual_cost = $actualCost;
                $reservation->end_time = now();

                // Calculer le remboursement (crédit non consommé)
                $prepaidAmount = $reservation->prepaid_amount ?? $reservation->amount;
                $refundAmount = max(0, $prepaidAmount - $actualCost);
                $reservation->refund_amount = $refundAmount;

                // Si le coût réel est supérieur au montant prépayé
                if ($actualCost > $prepaidAmount) {
                    // Mode postpaid : facturer la différence
                    $reservation->payment_status = 'PARTIAL_POSTPAID';
                    $additionalCharge = $actualCost - $prepaidAmount;
                    Log::info('Coût réel supérieur au prépayé - facturation additionnelle', [
                        'reservation_id' => $reservation->id,
                        'prepaid_amount' => $prepaidAmount,
                        'actual_cost' => $actualCost,
                        'additional_charge' => $additionalCharge,
                    ]);
                } elseif ($refundAmount > 0) {
                    // Effectuer le remboursement
                    $refundResult = $this->processRefundToPaymentMethod($reservation, $refundAmount);
                    
                    if ($refundResult['success']) {
                        $reservation->status = ReservationStatus::COMPLETED;
                        $reservation->payment_status = 'REFUNDED';
                    } else {
                        $reservation->payment_status = 'REFUND_FAILED';
                        Log::warning('Échec du remboursement', [
                            'reservation_id' => $reservation->id,
                            'refund_amount' => $refundAmount,
                            'error' => $refundResult['error'] ?? 'Inconnu',
                        ]);
                    }
                } else {
                    // Aucun remboursement nécessaire
                    $reservation->status = ReservationStatus::COMPLETED;
                    $reservation->payment_status = 'PAID';
                }

                $reservation->save();

                // Mettre à jour la transaction
                $transaction = $reservation->transaction;
                if ($transaction) {
                    $transaction->status = TransactionStatus::COMPLETED;
                    $transaction->meter_stop = $actualConsumption * 1000; // Convertir en Wh
                    $transaction->save();
                }

                // Envoyer la notification de fin de session
                $this->sendCompletionNotifications($reservation, $refundAmount);

                Log::info('Traitement de fin de session prépayée terminé', [
                    'reservation_id' => $reservation->id,
                    'actual_cost' => $actualCost,
                    'refund_amount' => $refundAmount,
                ]);

                return [
                    'success' => true,
                    'actual_cost' => $actualCost,
                    'refund_amount' => $refundAmount,
                    'reservation' => $reservation,
                ];

            } catch (\Exception $e) {
                Log::error('Erreur lors du traitement du remboursement', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Traiter le remboursement vers le moyen de paiement utilisé
     * 
     * @param Reservation $reservation
     * @param float $amount
     * @return array
     */
    protected function processRefundToPaymentMethod(Reservation $reservation, float $amount): array
    {
        $paymentGateway = $reservation->payment_method ?? 'cmi';
        $transactionRef = $reservation->payment_transaction_ref ?? null;

        if ($amount <= 0) {
            return ['success' => true, 'amount' => 0];
        }

        try {
            if ($paymentGateway === 'stripe') {
                return $this->stripePaymentService->processRefund($transactionRef, $amount);
            } else {
                // CMI refund
                return $this->cmiPaymentService->processRefund($transactionRef, $amount);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors du remboursement', [
                'reservation_id' => $reservation->id,
                'gateway' => $paymentGateway,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Valider les données de réservation
     * 
     * @param array $data
     * @return array
     */
    protected function validateReservationData(array $data): array
    {
        $required = ['charging_point_id', 'reservation_type', 'reservation_value'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'error' => "Le champ {$field} est requis"];
            }
        }

        // Valider le type de réservation
        if (!in_array($data['reservation_type'], ['energy', 'duration'])) {
            return ['valid' => false, 'error' => 'Type de réservation invalide'];
        }

        // Valider la valeur
        if (!is_numeric($data['reservation_value']) || $data['reservation_value'] <= 0) {
            return ['valid' => false, 'error' => 'Valeur de réservation invalide'];
        }

        // Valider l'email si fourni
        if (!empty($data['customer_email']) && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Adresse email invalide'];
        }

        // Valider le téléphone si fourni (format international)
        if (!empty($data['customer_phone'])) {
            $phone = preg_replace('/[^0-9+]/', '', $data['customer_phone']);
            if (strlen($phone) < 8) {
                return ['valid' => false, 'error' => 'Numéro de téléphone invalide'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Créer ou récupérer un utilisateur client
     * 
     * @param array $data
     * @return \App\Models\User|null
     */
    protected function createOrGetCustomerUser(array $data): ?\App\Models\User
    {
        // Vérifier si l'utilisateur existe déjà
        $existingUser = \App\Models\User::where('email', $data['customer_email'])->first();
        
        if ($existingUser) {
            return $existingUser;
        }

        // Créer un nouvel utilisateur
        try {
            return \App\Models\User::create([
                'name' => $data['customer_name'] ?? 'Client',
                'email' => $data['customer_email'],
                'phone' => $data['customer_phone'] ?? null,
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
            ]);
        } catch (\Exception $e) {
            Log::warning('Impossible de créer le compte client', [
                'email' => $data['customer_email'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Créer la transaction associée
     */
    protected function createTransaction(Reservation $reservation, ChargingPoint $chargingPoint, $pricingPlan, float $amount): Transaction
    {
        $transactionId = 'PRE-' . strtoupper(uniqid());
        
        $transaction = new Transaction();
        $transaction->transaction_id = $transactionId;
        $transaction->charging_point_id = $chargingPoint->id;
        $transaction->user_id = $reservation->user_id;
        $transaction->reservation_id = $reservation->id;
        $transaction->pricing_plan_id = $pricingPlan->id;
        $transaction->status = TransactionStatus::PENDING;
        $transaction->auth_method = 'prepaid_qr';
        $transaction->amount = $amount;
        $transaction->price_total = $amount;
        $transaction->currency = $pricingPlan->currency ?? 'EUR';
        $transaction->meter_start = 0;
        $transaction->start_timestamp = $reservation->start_time;
        
        $transaction->save();
        
        return $transaction;
    }

    /**
     * Créer l'enregistrement de paiement
     */
    protected function createPayment(Reservation $reservation, Transaction $transaction, float $amount, string $gateway): Payment
    {
        $payment = new Payment();
        $payment->reservation_id = $reservation->id;
        $payment->transaction_id = $transaction->id;
        $payment->amount = $amount;
        $payment->currency = $transaction->currency ?? 'EUR';
        $payment->payment_method = $gateway;
        $payment->status = 'pending';
        $payment->type = 'prepaid';
        
        $payment->save();
        
        return $payment;
    }

    /**
     * Générer l'URL de paiement selon la passerelle
     */
    protected function generatePaymentUrl(Reservation $reservation, Transaction $transaction, string $gateway): string
    {
        $baseUrl = config('app.url');
        
        if ($gateway === 'stripe') {
            return $this->stripePaymentService->createPaymentLink($reservation, $transaction);
        } else {
            // CMI payment URL
            return $this->cmiPaymentService->generatePaymentUrl($reservation, $transaction);
        }
    }

    /**
     * Générer le token d'activation pour la borne
     */
    protected function generateActivationToken(Reservation $reservation): string
    {
        $token = \Illuminate\Support\Str::random(32);
        
        // Stocker le token dans la réservation
        $reservation->activation_token = $token;
        $reservation->save();
        
        return $token;
    }

    /**
     * Envoyer les notifications de confirmation
     */
    protected function sendConfirmationNotifications(Reservation $reservation): void
    {
        try {
            // Envoyer un email de confirmation
            if ($reservation->guest_email) {
                \Illuminate\Support\Facades\Mail::to($reservation->guest_email)->send(
                    new \App\Mail\PrepaidReservationConfirmed($reservation)
                );
            }

            // Envoyer un SMS si disponible
            if ($reservation->guest_phone) {
                $this->sendConfirmationSms($reservation);
            }

            Log::info('Notifications de confirmation envoyées', [
                'reservation_id' => $reservation->id,
                'email' => $reservation->guest_email,
                'phone' => $reservation->guest_phone,
            ]);
        } catch (\Exception $e) {
            Log::warning('Échec de l\'envoi des notifications de confirmation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer les notifications de fin de session
     */
    protected function sendCompletionNotifications(Reservation $reservation, float $refundAmount): void
    {
        try {
            if ($reservation->guest_email) {
                \Illuminate\Support\Facades\Mail::to($reservation->guest_email)->send(
                    new \App\Mail\PrepaidSessionCompleted($reservation, $refundAmount)
                );
            }

            if ($reservation->guest_phone) {
                $this->sendCompletionSms($reservation, $refundAmount);
            }
        } catch (\Exception $e) {
            Log::warning('Échec de l\'envoi des notifications de fin de session', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer un SMS de confirmation
     */
    protected function sendConfirmationSms(Reservation $reservation): void
    {
        // Implémentation取决于 le provider SMS utilisé
        $message = "Votre réservation EVON est confirmée. Code: " . ($reservation->activation_token ?? '');
        
        // Log pour l'instant
        Log::info('SMS de confirmation', [
            'phone' => $reservation->guest_phone,
            'message' => $message,
        ]);
    }

    /**
     * Envoyer un SMS de fin de session
     */
    protected function sendCompletionSms(Reservation $reservation, float $refundAmount): void
    {
        $message = "Votre session de recharge est terminée. ";
        
        if ($refundAmount > 0) {
            $message .= "Remboursement de " . number_format($refundAmount, 2) . "€ effectué.";
        } else {
            $message .= "Coût: " . number_format($reservation->actual_cost ?? 0, 2) . "€";
        }

        Log::info('SMS de fin de session', [
            'phone' => $reservation->guest_phone,
            'message' => $message,
        ]);
    }

    /**
     * Annuler une réservation prépayée et procéder au remboursement
     * 
     * @param Reservation $reservation
     * @param string $reason
     * @return array
     */
    public function cancelPrepaidReservation(Reservation $reservation, string $reason = ''): array
    {
        return DB::transaction(function () use ($reservation, $reason) {
            try {
                // Vérifier que la réservation peut être annulée
                if (!in_array($reservation->status, [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])) {
                    return ['success' => false, 'error' => 'La réservation ne peut pas être annulée'];
                }

                // Mettre à jour le statut
                $reservation->status = ReservationStatus::CANCELED;
                $reservation->payment_status = 'CANCELLED';
                $reservation->notes = $reason;
                $reservation->save();

                // Si le paiement a déjà été effectué, procéder au remboursement
                if ($reservation->payment_status === 'PAID' && $reservation->prepaid_amount > 0) {
                    $refundResult = $this->processRefundToPaymentMethod($reservation, $reservation->prepaid_amount);
                    
                    if ($refundResult['success']) {
                        $reservation->refund_amount = $reservation->prepaid_amount;
                        $reservation->payment_status = 'REFUNDED';
                        $reservation->save();
                    }
                }

                // Mettre à jour la transaction
                $transaction = $reservation->transaction;
                if ($transaction) {
                    $transaction->status = TransactionStatus::CANCELLED;
                    $transaction->save();
                }

                Log::info('Réservation prépayée annulée', [
                    'reservation_id' => $reservation->id,
                    'reason' => $reason,
                    'refund_processed' => $reservation->payment_status === 'REFUNDED',
                ]);

                return ['success' => true];

            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'annulation de la réservation', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }
}
