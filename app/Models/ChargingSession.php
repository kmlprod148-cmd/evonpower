<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargingSession extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_INITIATING = 'initiating';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TIMEOUT = 'timeout';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ERROR = 'error';

    public const PAYMENT_MODE_PREPAID = 'prepaid';
    public const PAYMENT_MODE_POSTPAID = 'postpaid';

    // Capture status for postpaid
    public const CAPTURE_STATUS_PENDING = 'pending';
    public const CAPTURE_STATUS_PROCESSING = 'processing';
    public const CAPTURE_STATUS_CAPTURED = 'captured';
    public const CAPTURE_STATUS_FAILED = 'failed';
    public const CAPTURE_STATUS_CANCELLED = 'cancelled';
    public const CAPTURE_STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'session_id',
        'reservation_id',
        'charging_point_id',
        'user_id',
        'steve_transaction_id',
        'connector_id',
        'ocpp_tag',
        'payment_status',
        'status',
        'started_at',
        'stopped_at',
        'payment_mode',
        'prepaid_amount',
        'estimated_cost',
        'estimated_energy',
        'estimated_duration',
        'actual_cost',
        'actual_energy',
        'actual_duration',
        'stop_reason',
        'refund_amount',
        'wallet_transaction_id',
        'refund_transaction_id',
        'steve_response',
        'steve_stop_response',
        'meter_start',
        'meter_stop',
        'expected_stop_at',
        'start_transaction_received_at',
        'guest_payment_method_id',
        'authorization_hold_id',
        'authorization_hold_amount',
        'authorization_hold_created_at',
        'capture_status',
        'capture_attempted_at',
        'capture_transaction_id',
        'capture_error',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'expected_stop_at' => 'datetime',
        'start_transaction_received_at' => 'datetime',
        'prepaid_amount' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'estimated_energy' => 'decimal:2',
        'estimated_duration' => 'integer',
        'actual_cost' => 'decimal:2',
        'actual_energy' => 'decimal:2',
        'actual_duration' => 'integer',
        'refund_amount' => 'decimal:2',
        'steve_response' => 'array',
        'steve_stop_response' => 'array',
        'meter_start' => 'decimal:2',
        'meter_stop' => 'decimal:2',
        'authorization_hold_amount' => 'decimal:2',
        'capture_attempted_at' => 'datetime',
        'authorization_hold_created_at' => 'datetime',
    ];

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = strtolower((string) $value);
    }

    public function setPaymentModeAttribute($value): void
    {
        $this->attributes['payment_mode'] = $value ? strtolower((string) $value) : null;
    }

    public function setPaymentStatusAttribute($value): void
    {
        $this->attributes['payment_status'] = $value ? strtolower((string) $value) : null;
    }

    public function getTotalCostAttribute(): ?float
    {
        return $this->attributes['actual_cost'] ?? null;
    }

    public function setTotalCostAttribute($value): void
    {
        $this->attributes['actual_cost'] = $value;
    }

    /**
     * Relation avec la réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation avec la borne de recharge
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec la méthode de paiement guest (pour postpaid)
     */
    public function guestPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(GuestPaymentMethod::class, 'guest_payment_method_id');
    }

    /**
     * Vérifier si la session est active
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_IN_PROGRESS,
            self::STATUS_INITIATING,
        ], true);
    }

    /**
     * Vérifier si la session est terminée
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_STOPPED,
        ], true);
    }

    /**
     * Obtenir la durée de la session en minutes
     */
    public function getDuration(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->stopped_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }

    /**
     * Scope pour les sessions actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les sessions terminées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope pour les sessions d'un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les sessions d'une borne
     */
    public function scopeForChargingPoint($query, $chargingPointId)
    {
        return $query->where('charging_point_id', $chargingPointId);
    }

    /**
     * Vérifier si la session est en mode postpaid
     */
    public function isPostpaid(): bool
    {
        return $this->payment_mode === self::PAYMENT_MODE_POSTPAID;
    }

    /**
     * Vérifier si la session est en mode prepaid
     */
    public function isPrepaid(): bool
    {
        return $this->payment_mode === self::PAYMENT_MODE_PREPAID;
    }

    /**
     * Vérifier si la capture du paiement est en attente
     */
    public function isCapturePending(): bool
    {
        return $this->capture_status === self::CAPTURE_STATUS_PENDING;
    }

    /**
     * Vérifier si la capture a échoué
     */
    public function isCaptureFailed(): bool
    {
        return $this->capture_status === self::CAPTURE_STATUS_FAILED;
    }

    /**
     * Vérifier si le paiement a été capturé avec succès
     */
    public function isCaptureSuccess(): bool
    {
        return $this->capture_status === self::CAPTURE_STATUS_CAPTURED;
    }

    /**
     * Vérifier si la session nécessite une capture de paiement (guest postpaid)
     */
    public function needsPaymentCapture(): bool
    {
        return $this->isPostpaid() 
            && $this->guest_payment_method_id 
            && $this->authorization_hold_id 
            && $this->isCapturePending();
    }
}
