<?php

namespace App\Services;

use App\Models\ChargePoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Connector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Guest Checkout Service
 * 
 * Handles the business logic for guest checkout flow with GDPR compliance.
 * Provides methods for:
 * - Charging point information retrieval
 * - Price calculation with VAT
 * - Guest user session creation
 * - GDPR consent management
 * 
 * @package App\Services
 */
class GuestCheckoutService
{
    /**
     * Default duration options in minutes
     */
    protected array $durationOptions = [10, 20, 30, 40, 50, 60, 90, 120, 150];

    /**
     * Maximum allowed duration in minutes
     */
    protected int $maxDuration = 300;

    /**
     * Minimum allowed duration in minutes
     */
    protected int $minDuration = 1;

    /**
     * Calculate price based on duration and charge point
     * 
     * @param ChargePoint $chargePoint
     * @param int $durationMinutes
     * @return array Price breakdown with VAT
     */
    public function calculatePrice(ChargePoint $chargePoint, int $durationMinutes): array
    {
        // Validate duration
        $durationMinutes = max($this->minDuration, min($this->maxDuration, $durationMinutes));

        // Get tariff plan
        $tariffPlan = $chargePoint->tariffPlan ?? $chargePoint->pricingPlan;
        
        if (!$tariffPlan) {
            throw new \InvalidArgumentException('No pricing plan configured for this charge point');
        }

        // Get connector info for power calculation
        $connector = $chargePoint->connectors()->first();
        $avgPower = $connector?->max_power ?? 22; // kW
        $estimatedKwh = ($avgPower * $durationMinutes) / 60;

        // Calculate price based on billing type
        $priceExclVat = $this->calculateBasePrice($tariffPlan, $durationMinutes, $estimatedKwh);
        $currency = $tariffPlan->currency ?? 'MAD';

        // Apply VAT
        $vatRate = $tariffPlan->vat_rate ?? 20;
        $vatAmount = round($priceExclVat * ($vatRate / 100), 2);
        $totalPrice = round($priceExclVat + $vatAmount, 2);

        return [
            'estimated_kwh' => round($estimatedKwh, 2),
            'price_excl_vat' => round($priceExclVat, 2),
            'vat_amount' => $vatAmount,
            'vat_rate' => $vatRate,
            'total_price' => $totalPrice,
            'currency' => $currency,
            'billing_type' => $tariffPlan->price_per_minute > 0 ? 'per_minute' : 'per_kwh',
            'duration_minutes' => $durationMinutes,
            'power_kw' => $avgPower,
        ];
    }

    /**
     * Calculate base price (excluding VAT)
     * 
     * @param PricingPlan $tariffPlan
     * @param int $durationMinutes
     * @param float $estimatedKwh
     * @return float
     */
    protected function calculateBasePrice(PricingPlan $tariffPlan, int $durationMinutes, float $estimatedKwh): float
    {
        if ($tariffPlan->price_per_minute > 0) {
            // Per minute billing
            $basePrice = $tariffPlan->price_per_minute * $durationMinutes;
            $activationFee = $tariffPlan->activation_fee ?? 0;
            return $basePrice + $activationFee;
        } elseif ($tariffPlan->price_per_kwh > 0) {
            // Per kWh billing
            $basePrice = $tariffPlan->price_per_kwh * $estimatedKwh;
            $activationFee = $tariffPlan->activation_fee ?? 0;
            return $basePrice + $activationFee;
        } else {
            // Flat rate
            return $tariffPlan->base_rate ?? 0;
        }
    }

    /**
     * Create checkout session (guest or authenticated)
     * 
     * @param ChargePoint $chargePoint
     * @param array $checkoutData
     * @param array $guestInfo
     * @param bool $createAccount
     * @param string|null $password
     * @return Reservation
     */
    public function createCheckoutSession(
        ChargePoint $chargePoint,
        array $checkoutData,
        array $guestInfo,
        bool $createAccount = false,
        ?string $password = null
    ): Reservation {
        // Check if user already exists
        $user = User::where('email', $guestInfo['email'])->first();

        $isGuest = true;

        if ($user && !$createAccount) {
            // User exists but doesn't want to create account
            $isGuest = true;
        } elseif ($user && $createAccount) {
            // User wants to create account but email already exists
            throw new \InvalidArgumentException('An account with this email already exists');
        } elseif (!$user && $createAccount) {
            // Create new user account
            $user = User::create([
                'name' => $guestInfo['first_name'] . ' ' . $guestInfo['last_name'],
                'email' => $guestInfo['email'],
                'password' => Hash::make($password),
                'phone' => $guestInfo['phone'] ?? null,
                'address' => $guestInfo['address'] ?? null,
                'city' => $guestInfo['city'] ?? null,
                'is_active' => true,
            ]);
            $isGuest = false;
        }

        // Store GDPR consent
        $consentData = $this->storeGdprConsent($guestInfo);

        // Create checkout session record
        $sessionToken = Str::random(64);
        
        $reservation = Reservation::create([
            'user_id' => $user?->id,
            'charge_point_id' => $chargePoint->id,
            'partner_id' => $chargePoint->partner_id,
            'integrator_id' => $chargePoint->integrator_id,
            'start_time' => now(),
            'end_time' => now()->addMinutes($checkoutData['duration_minutes']),
            'duration_minutes' => $checkoutData['duration_minutes'],
            'estimated_energy' => $checkoutData['estimated_kwh'],
            'estimated_cost' => $checkoutData['total_price'],
            'amount' => $checkoutData['total_price'],
            'status' => 'pending',
            'guest_email' => $guestInfo['email'],
            'guest_phone' => $guestInfo['phone'] ?? null,
            'guest_info' => $guestInfo,
            'is_guest' => $isGuest,
            'payment_type' => 'cmi', // Default payment type
            'reservation_type' => 'public_qr',
            'metadata' => [
                'checkout_type' => 'public_qr',
                'estimated_price' => $checkoutData['total_price'],
                'currency' => $checkoutData['currency'],
                'session_token' => $sessionToken,
                'gdpr_consent' => $consentData,
                'created_at' => now()->toIso8601String(),
            ],
        ]);

        Log::info('Checkout session created', [
            'reservation_id' => $reservation->id,
            'charge_point_id' => $chargePoint->id,
            'user_id' => $user?->id,
            'is_guest' => $isGuest,
            'duration_minutes' => $checkoutData['duration_minutes'],
            'estimated_price' => $checkoutData['total_price'],
            'gdpr_consent_given' => $consentData['consented'] ?? false,
        ]);

        return $reservation;
    }

    /**
     * Store GDPR consent data
     * 
     * @param array $guestInfo
     * @return array
     */
    protected function storeGdprConsent(array $guestInfo): array
    {
        $now = now()->toIso8601String();
        
        return [
            'consented' => $guestInfo['gdpr_consent'] ?? false,
            'marketing_consent' => $guestInfo['marketing_consent'] ?? false,
            'consent_timestamp' => $now,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'privacy_policy_version' => '1.0',
        ];
    }

    /**
     * Validate guest information with GDPR requirements
     * 
     * @param array $guestInfo
     * @return array Validation errors
     */
    public function validateGuestInfo(array $guestInfo): array
    {
        $errors = [];

        // Required fields
        if (empty($guestInfo['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($guestInfo['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        if (empty($guestInfo['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($guestInfo['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }

        // GDPR consent is required
        if (empty($guestInfo['gdpr_consent']) || !$guestInfo['gdpr_consent']) {
            $errors['gdpr_consent'] = 'You must accept the privacy policy to continue';
        }

        // Password validation for account creation
        if (!empty($guestInfo['create_account'])) {
            if (empty($guestInfo['password'])) {
                $errors['password'] = 'Password is required to create an account';
            } elseif (strlen($guestInfo['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }
        }

        return $errors;
    }

    /**
     * Get charge point information for checkout display
     * 
     * @param ChargePoint $chargePoint
     * @return array
     */
    public function getChargePointInfo(ChargePoint $chargePoint): array
    {
        // Check if charge point is available
        if (!$chargePoint->is_active || $chargePoint->status === 'offline') {
            throw new \InvalidArgumentException('This charge point is currently unavailable');
        }

        // Get partner info
        $partner = $chargePoint->partner;
        
        // Get tariff plan info
        $tariffPlan = $chargePoint->tariffPlan ?? $chargePoint->pricingPlan;
        
        // Determine billing type
        $billingType = $tariffPlan?->price_per_minute > 0 ? 'per_minute' : 'per_kwh';

        // Get connector info
        $connector = $chargePoint->connectors()->first();
        $maxPower = $connector?->max_power ?? 22;
        $connectorType = $connector?->connector_type ?? 'Type 2';

        return [
            'id' => $chargePoint->id,
            'name' => $chargePoint->name,
            'address' => $chargePoint->address ?? $chargePoint->city ?? 'Location',
            'city' => $chargePoint->city,
            'partner_name' => $partner?->name,
            'tariff_plan' => $tariffPlan ? [
                'name' => $tariffPlan->name,
                'price_per_minute' => $tariffPlan->price_per_minute,
                'price_per_kwh' => $tariffPlan->price_per_kwh,
                'activation_fee' => $tariffPlan->activation_fee,
                'currency' => $tariffPlan->currency ?? 'MAD',
                'vat_rate' => $tariffPlan->vat_rate ?? 20,
            ] : null,
            'billing_type' => $billingType,
            'max_power' => $maxPower,
            'connector_type' => $connectorType,
            'is_available' => $chargePoint->is_active && $chargePoint->status !== 'offline',
        ];
    }

    /**
     * Get duration options for checkout
     * 
     * @return array
     */
    public function getDurationOptions(): array
    {
        return $this->durationOptions;
    }

    /**
     * Validate checkout session before payment
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function validateCheckoutSession(Reservation $reservation): array
    {
        $errors = [];

        if ($reservation->status !== 'pending') {
            $errors['status'] = 'Checkout session is not pending';
        }

        if (!$reservation->chargePoint || !$reservation->chargePoint->is_active) {
            $errors['charge_point'] = 'Charge point is no longer available';
        }

        // Check if session has expired (30 minutes)
        if ($reservation->created_at->diffInMinutes(now()) > 30) {
            $errors['expired'] = 'Checkout session has expired';
        }

        return $errors;
    }

    /**
     * Cancel checkout session
     * 
     * @param Reservation $reservation
     * @return bool
     */
    public function cancelCheckoutSession(Reservation $reservation): bool
    {
        if (in_array($reservation->status, ['completed', 'cancelled', 'payment_failed'])) {
            return false;
        }

        $reservation->update([
            'status' => 'cancelled',
            'metadata' => array_merge($reservation->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancellation_reason' => 'User cancelled checkout',
            ]),
        ]);

        Log::info('Checkout session cancelled', [
            'reservation_id' => $reservation->id,
        ]);

        return true;
    }

    /**
     * Get checkout summary for display
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function getCheckoutSummary(Reservation $reservation): array
    {
        $chargePoint = $reservation->chargePoint;
        
        return [
            'reservation_id' => $reservation->id,
            'charge_point' => [
                'name' => $chargePoint?->name,
                'address' => $chargePoint?->address ?? $chargePoint?->city,
            ],
            'duration_minutes' => $reservation->duration_minutes,
            'estimated_kwh' => $reservation->estimated_energy,
            'total_price' => $reservation->estimated_cost,
            'currency' => $reservation->metadata['currency'] ?? 'MAD',
            'guest_info' => [
                'name' => $reservation->guest_info['first_name'] . ' ' . $reservation->guest_info['last_name'],
                'email' => $reservation->guest_email,
                'phone' => $reservation->guest_phone,
            ],
            'status' => $reservation->status,
            'created_at' => $reservation->created_at->toIso8601String(),
        ];
    }
}
