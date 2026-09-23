<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnhancedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_reference',
        'source_user_id',
        'target_user_id',
        'business_profile_id',
        'amount',
        'fixed_fee_amount',
        'percentage_fee_amount',
        'total_amount',
        'currency',
        'fee_breakdown',
        'admin_fee',
        'integrator_fee',
        'operator_fee',
        'transaction_type',
        'status',
        'description',
        'metadata',
        'external_reference',
        'processed_at',
        'completed_at',
        'canceled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fixed_fee_amount' => 'decimal:2',
        'percentage_fee_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'integrator_fee' => 'decimal:2',
        'operator_fee' => 'decimal:2',
        'fee_breakdown' => 'json',
        'metadata' => 'json',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * Générer une référence de transaction unique
     */
    public static function generateTransactionReference(): string
    {
        return 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Boot method pour générer automatiquement la référence
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_reference)) {
                $transaction->transaction_reference = static::generateTransactionReference();
            }
        });
    }

    /**
     * Relation avec l'utilisateur source
     */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(EnhancedUser::class, 'source_user_id');
    }

    /**
     * Relation avec l'utilisateur cible
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(EnhancedUser::class, 'target_user_id');
    }

    /**
     * Relation avec le business profile
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(EnhancedBusinessProfile::class);
    }

    /**
     * Logs de frais pour cette transaction
     */
    public function feeLogs(): HasMany
    {
        return $this->hasMany(TransactionFeeLog::class);
    }

    /**
     * Récupérer les détails de transaction depuis TransactionDetail
     * via la référence de transaction (transaction_reference peut correspondre à transaction_id)
     * 
     * @return \App\Models\TransactionDetail|null
     */
    public function getTransactionDetail(): ?TransactionDetail
    {
        // Méthode 1: Chercher directement TransactionDetail par transaction_id si external_reference contient un ID
        if ($this->external_reference) {
            // Si external_reference est un ID numérique, chercher directement
            if (is_numeric($this->external_reference)) {
                $detail = \App\Models\TransactionDetail::where('transaction_id', (int) $this->external_reference)->first();
                if ($detail) {
                    return $detail;
                }
            }
            
            // Chercher Transaction via external_reference avec TransactionDetail eager loaded
            $transaction = \App\Models\Transaction::with('transactionDetail')
                ->where(function($q) {
                    $q->where('transaction_id', $this->external_reference)
                      ->orWhere('id', $this->external_reference)
                      ->orWhere('reference_id', $this->external_reference);
                })
                ->first();
            
            if ($transaction && $transaction->transactionDetail) {
                return $transaction->transactionDetail;
            }
        }

        // Méthode 2: Chercher via transaction_reference
        if ($this->transaction_reference) {
            // Si transaction_reference est un ID numérique, chercher directement
            if (is_numeric($this->transaction_reference)) {
                $detail = \App\Models\TransactionDetail::where('transaction_id', (int) $this->transaction_reference)->first();
                if ($detail) {
                    return $detail;
                }
            }
            
            $transaction = \App\Models\Transaction::with('transactionDetail')
                ->where(function($q) {
                    $q->where('transaction_id', $this->transaction_reference)
                      ->orWhere('reference_id', $this->transaction_reference)
                      ->orWhere('id', $this->transaction_reference);
                })
                ->first();
                
            if ($transaction && $transaction->transactionDetail) {
                return $transaction->transactionDetail;
            }
        }

        // Méthode 3: Chercher via l'ID de EnhancedTransaction si c'est un ID de Transaction
        if (is_numeric($this->id)) {
            // Chercher directement TransactionDetail
            $detail = \App\Models\TransactionDetail::where('transaction_id', $this->id)->first();
            if ($detail) {
                return $detail;
            }
            
            // Chercher Transaction avec cet ID
            $transaction = \App\Models\Transaction::with('transactionDetail')->find($this->id);
            if ($transaction && $transaction->transactionDetail) {
                return $transaction->transactionDetail;
            }
        }

        // Méthode 4: Chercher via metadata si elle contient des références
        if ($this->metadata && is_array($this->metadata)) {
            // Chercher transaction_id ou reservation_id dans metadata
            $transactionId = $this->metadata['transaction_id'] ?? $this->metadata['original_transaction_id'] ?? null;
            if ($transactionId) {
                $detail = \App\Models\TransactionDetail::where('transaction_id', $transactionId)->first();
                if ($detail) {
                    return $detail;
                }
                
                $transaction = \App\Models\Transaction::with('transactionDetail')->find($transactionId);
                if ($transaction && $transaction->transactionDetail) {
                    return $transaction->transactionDetail;
                }
            }
        }

        return null;
    }

    /**
     * Accesseur pour obtenir les parts depuis TransactionDetail
     * 
     * @return array
     */
    public function getSharesAttribute(): array
    {
        // Vérifier si TransactionDetail est déjà chargé en cache (évite requêtes répétées)
        if (isset($this->cachedTransactionDetail)) {
            $detail = $this->cachedTransactionDetail;
        } else {
            $detail = $this->getTransactionDetail();
            // Mettre en cache pour éviter les requêtes répétées
            $this->cachedTransactionDetail = $detail;
        }
        
        if ($detail) {
            return [
                'admin_share' => (float) ($detail->admin_share_amount ?? 0),
                'integrator_share' => (float) ($detail->integrator_share_amount ?? 0),
                'operator_share' => (float) ($detail->operator_share_amount ?? 0),
                'admin_percentage' => (float) ($detail->admin_share_percentage ?? 0),
                'integrator_percentage' => (float) ($detail->integrator_share_percentage ?? 0),
                'source' => 'transaction_detail',
                'has_real_data' => true
            ];
        }

        // Fallback sur les données de EnhancedTransaction
        return [
            'admin_share' => (float) ($this->admin_fee ?? 0),
            'integrator_share' => (float) ($this->integrator_fee ?? 0),
            'operator_share' => (float) ($this->operator_fee ?? 0),
            'admin_percentage' => 0,
            'integrator_percentage' => 0,
            'source' => 'enhanced_transaction',
            'has_real_data' => false
        ];
    }

    /**
     * Obtenir le montant total des frais (priorité à TransactionDetail)
     * 
     * @return float
     */
    public function getTotalFeesFromDetail(): float
    {
        $detail = $this->getTransactionDetail();
        
        if ($detail) {
            return (float) ($detail->admin_share_amount ?? 0) + 
                   (float) ($detail->integrator_share_amount ?? 0);
        }

        return $this->getTotalFees();
    }

    /**
     * Marquer la transaction comme traitée
     */
    public function markAsProcessed(): bool
    {
        $this->status = 'completed';
        $this->processed_at = now();
        $this->completed_at = now();
        return $this->save();
    }

    /**
     * Marquer la transaction comme annulée
     */
    public function markAsCanceled(string $reason = null): bool
    {
        $this->status = 'canceled';
        $this->canceled_at = now();
        
        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['cancelation_reason'] = $reason;
            $this->metadata = $metadata;
        }
        
        return $this->save();
    }

    /**
     * Marquer la transaction comme échouée
     */
    public function markAsFailed(string $reason = null): bool
    {
        $this->status = 'failed';
        
        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['failure_reason'] = $reason;
            $this->metadata = $metadata;
        }
        
        return $this->save();
    }

    /**
     * Vérifier si la transaction est terminée
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Vérifier si la transaction est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si la transaction est annulée
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Vérifier si la transaction a échoué
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Obtenir le montant total des frais
     */
    public function getTotalFees(): float
    {
        return $this->admin_fee + $this->integrator_fee + $this->operator_fee;
    }

    /**
     * Obtenir le montant net (montant principal sans frais)
     */
    public function getNetAmount(): float
    {
        return $this->amount;
    }

    /**
     * Obtenir le montant brut (montant principal + frais)
     */
    public function getGrossAmount(): float
    {
        return $this->total_amount;
    }

    /**
     * Obtenir le détail des frais formaté
     */
    public function getFormattedFeeBreakdown(): array
    {
        $breakdown = $this->fee_breakdown ?? [];
        $formatted = [];

        foreach ($breakdown as $feeType => $details) {
            $formatted[$feeType] = [
                'total' => $this->{$feeType . '_fee'},
                'details' => $details
            ];
        }

        return $formatted;
    }

    /**
     * Obtenir les statistiques de la transaction
     */
    public function getTransactionStats(): array
    {
        return [
            'reference' => $this->transaction_reference,
            'type' => $this->transaction_type,
            'status' => $this->status,
            'amount' => $this->amount,
            'total_fees' => $this->getTotalFees(),
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'source_user' => $this->sourceUser->name ?? 'N/A',
            'target_user' => $this->targetUser->name ?? 'N/A',
            'created_at' => $this->created_at,
            'processed_at' => $this->processed_at,
        ];
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('source_user_id', $userId)
                    ->orWhere('target_user_id', $userId);
    }

    public function scopeByBusinessProfile($query, int $businessProfileId)
    {
        return $query->where('business_profile_id', $businessProfileId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scopes pour le contrôle d'accès basé sur les rôles
     */
    public function scopeAccessibleByUser($query, EnhancedUser $user)
    {
        switch ($user->role) {
            case 'admin':
                // Admin peut voir toutes les transactions
                return $query;
                
            case 'integrator':
                // Integrator peut voir ses transactions + celles de ses opérateurs
                $operatorIds = EnhancedUser::where('created_by', $user->id)
                    ->where('role', 'operator')
                    ->pluck('id')
                    ->toArray();
                
                $accessibleIds = array_merge([$user->id], $operatorIds);
                
                return $query->where(function($q) use ($accessibleIds) {
                    $q->whereIn('source_user_id', $accessibleIds)
                      ->orWhereIn('target_user_id', $accessibleIds);
                });
                
            case 'operator':
                // Operator peut voir seulement ses propres transactions
                return $query->where(function($q) use ($user) {
                    $q->where('source_user_id', $user->id)
                      ->orWhere('target_user_id', $user->id);
                });
                
            default:
                // Par défaut, aucune transaction visible
                return $query->whereRaw('1 = 0');
        }
    }

    public function scopeAccessibleBusinessProfiles($query, EnhancedUser $user)
    {
        switch ($user->role) {
            case 'admin':
                // Admin peut voir tous les business profiles
                return $query;
                
            case 'integrator':
                // Integrator peut voir ses business profiles + ceux de ses opérateurs
                $operatorIds = EnhancedUser::where('created_by', $user->id)
                    ->where('role', 'operator')
                    ->pluck('id')
                    ->toArray();
                
                $accessibleIds = array_merge([$user->id], $operatorIds);
                
                return $query->whereIn('owner_id', $accessibleIds);
                
            case 'operator':
                // Operator peut voir seulement son propre business profile
                return $query->where('owner_id', $user->id)
                    ->orWhere('id', $user->business_profile_id);
                
            default:
                // Par défaut, aucun business profile visible
                return $query->whereRaw('1 = 0');
        }
    }

    public function scopeWithFeeDetails($query, EnhancedUser $user)
    {
        // Ajouter les relations nécessaires pour les détails des frais
        return $query->with([
            'sourceUser:id,name,role',
            'targetUser:id,name,role',
            'businessProfile:id,name,owner_id',
            'feeLogs' => function($q) use ($user) {
                // Filtrer les logs de frais selon les permissions
                if ($user->role !== 'admin') {
                    $q->where('fee_category', '!=', 'admin'); // Masquer les frais admin pour les non-admins
                }
            }
        ]);
    }
}
