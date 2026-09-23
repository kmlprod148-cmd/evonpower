<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Enums\ReservationStatus;
use App\Enums\ChargingSessionStatus;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Exceptions\ChargingPoint\ChargingPointNotFoundException;
use App\Exceptions\ChargingPoint\PaymentRequiredException;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de paiement public en 3 étapes avec QR Code
 * 
 * Étape 1: Scan QR → Identification borne et tarifs
 * Étape 2: Sélection paiement → Validation paiement
 * Étape 3: Démarrage session → Validation temps réel
 */
class PublicPaymentService
{
    /**
     * Délai maximum pour confirmer le paiement (secondes)
     */
    public const PAYMENT_CONFIRMATION_TIMEOUT = 300; // 5 minutes

    /**
     * Statut du paiement en attente
     */
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_CONFIRMED = 'confirmed';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_CANCELLED = 'cancelled';

    public function __construct(
        protected ChargingPointQRCodeService $qrCodeService,
        protected ChargingService $chargingService,
        protected TransactionService $transactionService,
        protected MoneyService $moneyService
    ) {}

    /**
     * ÉTAPE 1: Scanner le QR Code et récupérer les informations de la borne
     * 
     * @param string $qrCodeData Données encodées dans le QR Code
     * @return array Informations de la borne et des tarifs
     * @throws ChargingPointNotFoundException
     */
    public function scanQRCode(string $qrCodeData): array
    {
        Log::info('PublicPaymentService: Scan QR Code', ['qr_data' => $qrCodeData]);

        // Extraire l'ID de la borne depuis le QR Code
        $chargingPointId = $this->extractChargingPointIdFromQR($qrCodeData);
        
        if (!$chargingPointId) {
            throw new ChargingPointNotFoundException('QR Code invalide');
        }

        // Récupérer la borne
        $chargingPoint = ChargingPoint::with([
            'group.partner.integrator',
            'businessProfile',
            'stations',
            'pricingPlans'
        ])->find($chargingPointId);

        if (!$chargingPoint) {
            throw new ChargingPointNotFoundException("Borne de recharge non trouvée: {$chargingPointId}");
        }

        // Vérifier que la borne est disponible
        if (!$this->isChargingPointAvailable($chargingPoint)) {
            throw new ChargingPointNotFoundException('Cette borne n\'est pas disponible pour le moment');
        }

        // Récupérer les tarifs disponibles
        $pricingPlans = $this->getAvailablePricingPlans($chargingPoint);

        // Générer un token de session temporaire pour le paiement
        $paymentToken = $this->generatePaymentToken($chargingPointId);

        Log::info('PublicPaymentService: QR Code scanné avec succès', [
            'charging_point_id' => $chargingPointId,
            'pricing_plans_count' => count($pricingPlans)
        ]);

        return [
            'step' => 1,
            'charging_point' => [
                'id' => $chargingPoint->id,
                'name' => $chargingPoint->name,
                'location' => $chargingPoint->location ?? $chargingPoint->address,
                'latitude' => $chargingPoint->latitude,
                'longitude' => $chargingPoint->longitude,
                'connectors' => $chargingPoint->connectors->map(fn($c) => [
                    'id' => $c->id,
                    'type' => $c->type,
                    'power' => $c->power_output,
                ]),
                'status' => $chargingPoint->status,
            ],
            'pricing_plans' => $pricingPlans,
            'payment_token' => $paymentToken,
            'expires_at' => now()->addSeconds(self::PAYMENT_CONFIRMATION_TIMEOUT)->toISOString(),
        ];
    }

    /**
     * ÉTAPE 2: Initier le paiement avec la méthode sélectionnée
     * 
     * @param string $paymentToken Token de session paiement
     * @param int $pricingPlanId ID du plan tarifaire choisi
     * @param string $paymentMethod Méthode de paiement (wallet, qr_code, postpaid)
     * @param User|null $user Utilisateur connecté (optionnel)
     * @return array Informations de paiement
     * @throws \Exception
     */
    public function initiatePayment(
        string $paymentToken,
        int $pricingPlanId,
        string $paymentMethod,
        ?User $user = null
    ): array {
        Log::info('PublicPaymentService: Initiation paiement', [
            'payment_token' => $paymentToken,
            'pricing_plan_id' => $pricingPlanId,
            'payment_method' => $paymentMethod,
            'user_id' => $user?->id,
        ]);

        // Valider le token de paiement
        $paymentData = $this->validatePaymentToken($paymentToken);
        $chargingPoint = ChargingPoint::findOrFail($paymentData['charging_point_id']);
        $pricingPlan = PricingPlan::findOrFail($pricingPlanId);

        // Vérifier la disponibilité de la borne
        if (!$this->isChargingPointAvailable($chargingPoint)) {
            throw new \Exception('Borne non disponible');
        }

        // Valider la méthode de paiement
        $paymentType = PaymentType::from($paymentMethod);
        
        // Créer l'enregistrement de paiement
        $reservation = $this->createReservationForPayment(
            $chargingPoint,
            $pricingPlan,
            $paymentType,
            $user
        );

        // Préparer la réponse selon la méthode de paiement
        $paymentResponse = match ($paymentType) {
            PaymentType::WALLET => $this->prepareWalletPayment($reservation, $user),
            PaymentType::QR_CODE => $this->prepareQRCodePayment($reservation),
            PaymentType::POSTPAID => $this->preparePostpaidPayment($reservation, $user),
            default => throw new \Exception("Méthode de paiement non supportée: {$paymentMethod}"),
        };

        Log::info('PublicPaymentService: Paiement initialisé', [
            'reservation_id' => $reservation->id,
            'payment_method' => $paymentMethod,
        ]);

        return array_merge([
            'step' => 2,
            'reservation_id' => $reservation->id,
            'payment_token' => $paymentToken,
            'payment_method' => $paymentMethod,
            'pricing_plan' => [
                'id' => $pricingPlan->id,
                'name' => $pricingPlan->name,
                'price_per_kwh' => $pricingPlan->price_per_kwh,
                'price_per_minute' => $pricingPlan->price_per_minute,
                'activation_fee' => $pricingPlan->activation_fee,
            ],
            'expires_at' => now()->addSeconds(self::PAYMENT_CONFIRMATION_TIMEOUT)->toISOString(),
        ], $paymentResponse);
    }

    /**
     * ÉTAPE 3: Confirmer le paiement et démarrer la session de charge
     * 
     * @param int $reservationId ID de la réservation
     * @param string $paymentToken Token de session paiement
     * @param string $paymentMethod Méthode de paiement utilisée
     * @param User|null $user Utilisateur connecté
     * @return array Résultat du démarrage de session
     * @throws \Exception
     */
    public function confirmPaymentAndStartSession(
        int $reservationId,
        string $paymentToken,
        string $paymentMethod,
        ?User $user = null
    ): array {
        Log::info('PublicPaymentService: Confirmation paiement et démarrage session', [
            'reservation_id' => $reservationId,
            'payment_token' => $paymentToken,
            'payment_method' => $paymentMethod,
        ]);

        return DB::transaction(function () use ($reservationId, $paymentToken, $paymentMethod, $user) {
            // Récupérer la réservation
            $reservation = Reservation::with(['chargingPoint', 'pricingPlan'])
                ->findOrFail($reservationId);

            // Valider le token
            $this->validatePaymentToken($paymentToken);

            // Valider le statut de la réservation
            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                throw new \Exception('Réservation non valide pour ce paiement');
            }

            // Confirmer le paiement selon la méthode
            $paymentType = PaymentType::from($paymentMethod);
            $this->confirmPayment($reservation, $paymentType, $user);

            // Créer et démarrer la session de charge
            $session = $this->startChargingSession($reservation, $user);

            Log::info('PublicPaymentService: Session démarrée avec succès', [
                'session_id' => $session->id,
                'reservation_id' => $reservationId,
            ]);

            return [
                'step' => 3,
                'success' => true,
                'session' => [
                    'id' => $session->id,
                    'transaction_id' => $session->transaction_id,
                    'monitoring_token' => $session->monitoring_token,
                    'start_timestamp' => $session->start_timestamp,
                    'charging_point' => [
                        'id' => $reservation->chargingPoint->id,
                        'name' => $reservation->chargingPoint->name,
                    ],
                ],
                'payment' => [
                    'method' => $paymentMethod,
                    'status' => 'confirmed',
                    'reservation_id' => $reservation->id,
                ],
                'monitoring_url' => route('api.public.charging.session.monitoring', [
                    'session_id' => $session->id,
                    'token' => $session->monitoring_token,
                ]),
            ];
        });
    }

    /**
     * Valider le paiement en temps réel (pour QR Code)
     * 
     * @param int $reservationId ID de la réservation
     * @return array Statut de validation
     */
    public function validatePaymentInRealTime(int $reservationId): array
    {
        $reservation = Reservation::findOrFail($reservationId);

        $isValid = in_array($reservation->payment_status, ['confirmed', 'paid', 'PAID']);
        $isExpired = now()->greaterThan($reservation->created_at->addSeconds(self::PAYMENT_CONFIRMATION_TIMEOUT));

        return [
            'valid' => $isValid && !$isExpired,
            'payment_status' => $reservation->payment_status,
            'is_expired' => $isExpired,
            'reservation_status' => $reservation->status,
            'checked_at' => now()->toISOString(),
        ];
    }

    /**
     * Extraire l'ID de la borne depuis les données QR Code
     */
    protected function extractChargingPointIdFromQR(string $qrCodeData): ?int
    {
        // Essayer de parser comme JSON
        $decoded = json_decode($qrCodeData, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['charging_point_id'])) {
            return (int) $decoded['charging_point_id'];
        }

        // Essayer d'extraire l'ID directement si c'est un entier
        if (is_numeric($qrCodeData)) {
            return (int) $qrCodeData;
        }

        // Essayer d'extraire depuis une URL
        if (preg_match('/\/charging-points\/(\d+)/', $qrCodeData, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Vérifier si la borne est disponible
     */
    protected function isChargingPointAvailable(ChargingPoint $chargingPoint): bool
    {
        $availableStatuses = ['available', 'online', 'operative'];
        
        return in_array(strtolower($chargingPoint->status), $availableStatuses) 
            && $chargingPoint->is_active === true;
    }

    /**
     * Récupérer les plans tarifaires disponibles
     */
    protected function getAvailablePricingPlans(ChargingPoint $chargingPoint): array
    {
        $plans = $chargingPoint->pricingPlans()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        return $plans->map(fn($plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'description' => $plan->description,
            'price_per_kwh' => $plan->price_per_kwh,
            'price_per_minute' => $plan->price_per_minute,
            'activation_fee' => $plan->activation_fee,
            'currency' => $plan->currency ?? 'EUR',
        ])->toArray();
    }

    /**
     * Générer un token de session de paiement
     */
    protected function generatePaymentToken(int $chargingPointId): string
    {
        return 'PAY_' . $chargingPointId . '_' . Str::random(32);
    }

    /**
     * Valider le token de paiement
     */
    protected function validatePaymentToken(string $token): array
    {
        // Parser le token (format: PAY_{charging_point_id}_{random})
        if (!Str::startsWith($token, 'PAY_')) {
            throw new \Exception('Token de paiement invalide');
        }

        $parts = explode('_', $token);
        if (count($parts) < 3) {
            throw new \Exception('Format de token invalide');
        }

        return [
            'charging_point_id' => (int) $parts[1],
            'token' => $token,
        ];
    }

    /**
     * Créer une réservation pour le paiement
     */
    protected function createReservationForPayment(
        ChargingPoint $chargingPoint,
        PricingPlan $pricingPlan,
        PaymentType $paymentType,
        ?User $user
    ): Reservation {
        return Reservation::create([
            'user_id' => $user?->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'status' => ReservationStatus::PENDING,
            'payment_method' => $paymentType->value,
            'payment_status' => self::PAYMENT_STATUS_PENDING,
            'start_time' => now(),
            'end_time' => now()->addHours(4), // Valid for 4 hours
            'notes' => "Paiement public - {$paymentType->getLabel()}",
            'is_public' => true,
        ]);
    }

    /**
     * Préparer le paiement par wallet
     */
    protected function prepareWalletPayment(Reservation $reservation, ?User $user): array
    {
        if (!$user) {
            return [
                'requires_auth' => true,
                'redirect_url' => route('login', ['redirect' => url()->current()]),
            ];
        }

        // Vérifier le solde
        $wallet = $user->getOrCreateWallet();
        $estimatedCost = $this->estimateCost($reservation);
        
        if (!$wallet->hasSufficientBalance($estimatedCost)) {
            throw new InsufficientBalanceException('Solde insuffisant pour démarrer la session');
        }

        return [
            'wallet_balance' => $wallet->balance,
            'estimated_cost' => $estimatedCost,
            'can_start' => true,
        ];
    }

    /**
     * Préparer le paiement par QR Code
     */
    protected function prepareQRCodePayment(Reservation $reservation): array
    {
        // Génerer un QR code de paiement unique
        $paymentQRData = json_encode([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->pricingPlan->activation_fee ?? 0,
            'timestamp' => now()->timestamp,
            'checksum' => md5($reservation->id . config('app.key')),
        ]);

        return [
            'payment_qr_code' => $paymentQRData,
            'payment_qr_url' => route('api.public.payment.qr', $reservation->id),
            'amount' => $reservation->pricingPlan->activation_fee ?? 0,
            'currency' => $reservation->pricingPlan->currency ?? 'EUR',
        ];
    }

    /**
     * Préparer le paiement postpayé
     */
    protected function preparePostpaidPayment(Reservation $reservation, ?User $user): array
    {
        if (!$user) {
            return [
                'requires_auth' => true,
                'redirect_url' => route('login', ['redirect' => url()->current()]),
            ];
        }

        // Vérifier si l'utilisateur est autorisé pour le postpayé
        $isAuthorized = $this->isUserAuthorizedForPostpaid($user);

        return [
            'is_authorized' => $isAuthorized,
            'user_credit_limit' => $user->postpaid_credit_limit ?? 0,
            'user_current_usage' => $user->getPostpaidCurrentUsage(),
            'requires_approval' => !$isAuthorized,
        ];
    }

    /**
     * Confirmer le paiement
     */
    protected function confirmPayment(
        Reservation $reservation,
        PaymentType $paymentType,
        ?User $user
    ): void {
        switch ($paymentType) {
            case PaymentType::WALLET:
                $this->confirmWalletPayment($reservation, $user);
                break;
            case PaymentType::QR_CODE:
                $this->confirmQRCodePayment($reservation);
                break;
            case PaymentType::POSTPAID:
                $this->confirmPostpaidPayment($reservation, $user);
                break;
        }
    }

    /**
     * Confirmer le paiement par wallet
     */
    protected function confirmWalletPayment(Reservation $reservation, ?User $user): void
    {
        if (!$user) {
            throw new \Exception('Utilisateur requis pour le paiement par wallet');
        }

        $wallet = $user->getOrCreateWallet();
        
        // Réserver le montant estimé
        $estimatedCost = $this->estimateCost($reservation);
        
        $reservation->update([
            'payment_status' => 'confirmed',
            'status' => ReservationStatus::CONFIRMED,
            'estimated_cost' => $estimatedCost,
        ]);
    }

    /**
     * Confirmer le paiement par QR Code
     */
    protected function confirmQRCodePayment(Reservation $reservation): void
    {
        // Le paiement QR Code est considéré comme confirmé après validation externe
        $reservation->update([
            'payment_status' => 'confirmed',
            'status' => ReservationStatus::CONFIRMED,
        ]);
    }

    /**
     * Confirmer le paiement postpayé
     */
    protected function confirmPostpaidPayment(Reservation $reservation, ?User $user): void
    {
        if (!$user) {
            throw new \Exception('Utilisateur requis pour le paiement postpayé');
        }

        $reservation->update([
            'payment_status' => 'postpaid_authorized',
            'status' => ReservationStatus::CONFIRMED,
        ]);
    }

    /**
     * Démarrer la session de charge
     */
    protected function startChargingSession(Reservation $reservation, ?User $user): ChargingSession
    {
        // Créer une session de charge
        $session = ChargingSession::create([
            'charging_point_id' => $reservation->charging_point_id,
            'station_id' => $reservation->station_id,
            'user_id' => $user?->id,
            'reservation_id' => $reservation->id,
            'pricing_plan_id' => $reservation->pricing_plan_id,
            'transaction_id' => 'TR' . strtoupper(Str::random(10)) . time(),
            'session_id' => 'SESS_' . time() . '_' . Str::random(8),
            'start_timestamp' => now(),
            'status' => ChargingSessionStatus::ACTIVE,
            'meter_start' => 0,
            'auth_method' => 'qr_code',
            'is_public' => true,
            'email' => $user?->email,
        ]);

        return $session;
    }

    /**
     * Estimer le coût de la session
     */
    protected function estimateCost(Reservation $reservation): float
    {
        $plan = $reservation->pricingPlan;
        
        // Estimer sur 1 heure max
        $estimatedMinutes = 60;
        $estimatedKwh = 22; // Moyenne 22kW
        
        $cost = ($plan->activation_fee ?? 0) 
            + ($estimatedKwh * ($plan->price_per_kwh ?? 0))
            + ($estimatedMinutes * ($plan->price_per_minute ?? 0) / 60);
        
        return max($cost, $plan->minimum_charge ?? 0);
    }

    /**
     * Vérifier si l'utilisateur est autorisé pour le postpayé
     */
    public function isUserAuthorizedForPostpaid(User $user): bool
    {
        // Vérifier si l'utilisateur a un historique de crédit positif
        $hasGoodHistory = $user->postpaid_credit_limit > 0 
            && $user->postpaid_status === 'approved';
        
        // Ou vérifier si l'utilisateur a un historique de transactions positif
        if (!$hasGoodHistory) {
            $positiveTransactions = $user->transactions()
                ->where('status', 'completed')
                ->where('amount', '>', 0)
                ->count();
            
            $hasGoodHistory = $positiveTransactions >= 10;
        }

        return $hasGoodHistory;
    }
}
