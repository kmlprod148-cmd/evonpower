<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Guest Payment Method Model
 * 
 * Stores tokenized payment methods for guest users who want to use postpaid mode.
 * Unlike user payment methods, these are not linked to a user account but to
 * a guest reservation or charging session.
 * 
 * @property int $id
 * @property string $guest_email
 * @property string $gateway_type
 * @property string $payment_method_id
 * @property string $last_four
 * @property string $brand
 * @property string $expiry_month
 * @property string $expiry_year
 * @property bool $is_default
 * @property bool $is_active
 * @property int|null $reservation_id
 * @property array $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GuestPaymentMethod extends Model
{
    protected $fillable = [
        'guest_email',
        'gateway_type',
        'payment_method_id',
        'last_four',
        'brand',
        'expiry_month',
        'expiry_year',
        'is_default',
        'is_active',
        'reservation_id',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the reservation this payment method is associated with
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get charging sessions using this payment method
     */
    public function chargingSessions(): HasMany
    {
        return $this->hasMany(ChargingSession::class, 'guest_payment_method_id');
    }

    /**
     * Check if the payment method is expired
     */
    public function isExpired(): bool
    {
        $now = now();
        $expiry = \Carbon\Carbon::createFromFormat(
            'm/Y',
            $this->expiry_month . '/' . $this->expiry_year
        );
        
        return $expiry->isBefore($now);
    }

    /**
     * Get a masked display of the card
     */
    public function getMaskedNumber(): string
    {
        return '**** **** **** ' . $this->last_four;
    }

    /**
     * Get display name for the payment method
     */
    public function getDisplayName(): string
    {
        return ucfirst($this->brand) . ' ' . $this->getMaskedNumber();
    }

    /**
     * Scope to get active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default payment method
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to filter by guest email
     */
    public function scopeForGuest($query, string $email)
    {
        return $query->where('guest_email', $email);
    }

    /**
     * Find or create payment method for guest
     */
    public static function findOrCreateForGuest(
        string $email,
        string $gatewayType,
        string $paymentMethodId,
        array $cardDetails
    ): self {
        // Check if payment method already exists
        $existing = self::where('guest_email', $email)
            ->where('gateway_type', $gatewayType)
            ->where('payment_method_id', $paymentMethodId)
            ->first();

        if ($existing) {
            // Update existing payment method
            $existing->update([
                'is_active' => true,
                'last_four' => $cardDetails['last_four'] ?? $existing->last_four,
                'brand' => $cardDetails['brand'] ?? $existing->brand,
                'expiry_month' => $cardDetails['expiry_month'] ?? $existing->expiry_month,
                'expiry_year' => $cardDetails['expiry_year'] ?? $existing->expiry_year,
            ]);
            return $existing;
        }

        // Create new payment method
        return self::create([
            'guest_email' => $email,
            'gateway_type' => $gatewayType,
            'payment_method_id' => $paymentMethodId,
            'last_four' => $cardDetails['last_four'] ?? '0000',
            'brand' => $cardDetails['brand'] ?? 'Unknown',
            'expiry_month' => $cardDetails['expiry_month'] ?? '12',
            'expiry_year' => $cardDetails['expiry_year'] ?? '2025',
            'is_default' => self::where('guest_email', $email)->count() === 0,
            'is_active' => true,
            'metadata' => [
                'card_type' => $cardDetails['card_type'] ?? null,
                'country' => $cardDetails['country'] ?? null,
            ],
        ]);
    }
}
