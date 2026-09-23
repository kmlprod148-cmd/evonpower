<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\MoneyService;

class CreditRecharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'credit_pack_id',
        'is_custom',
        'amount',
        'currency',
        'payment_method',
        'status',
        'reference',
        'external_id',
        'description',
        'payment_data',
        'metadata',
        'processed_by',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_custom' => 'boolean',
        'payment_data' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Génère une référence unique pour la recharge
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recharge) {
            if (empty($recharge->reference)) {
                $recharge->reference = 'RCH-' . strtoupper(uniqid()) . '-' . time();
            }
        });
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le wallet
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Relation avec l'utilisateur qui a traité la recharge (pour offline)
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Relation avec le pack de crédit
     */
    public function creditPack(): BelongsTo
    {
        return $this->belongsTo(CreditPack::class);
    }

    /**
     * Scope pour les recharges en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les recharges complétées
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope pour les recharges échouées
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope pour les recharges par méthode de paiement
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope pour les recharges d'un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Vérifie si la recharge est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifie si la recharge est complétée
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Vérifie si la recharge a échoué
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Marque la recharge comme complétée
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Marque la recharge comme échouée
     */
    public function markAsFailed(string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Marque la recharge comme en traitement
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Marque la recharge comme annulée
     */
    public function markAsCancelled(string $reason = null): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Obtient le montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return MoneyService::format($this->amount, $this->currency);
    }

    /**
     * Obtient la couleur du badge de statut
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'processing' => 'blue',
            'pending' => 'yellow',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Obtient le nom de la méthode de paiement
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return match($this->payment_method) {
            'offline' => 'Paiement hors ligne',
            'cmi' => 'CMI (Maroc)',
            'stripe' => 'Stripe (International)',
            default => 'Inconnu'
        };
    }
}

