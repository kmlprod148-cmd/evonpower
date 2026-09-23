<?php

namespace App\Models;

use App\Services\MoneyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
        'status',
        'reference',
        'external_id',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for transactions in a date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for transactions related to reservations
     * Vérifie si la transaction est liée à une réservation via metadata
     */
    public function scopeRelatedToReservations($query)
    {
        return $query->where(function($q) {
            // Vérifier si reservation_id existe dans metadata
            $q->where(function($subQ) {
                $subQ->whereNotNull('metadata')
                     ->where('metadata', 'like', '%"reservation_id":%')
                     ->where('metadata', 'not like', '%"reservation_id":null%')
                     ->where('metadata', 'not like', '%"reservation_id":0%');
            })
            // Ou vérifier si transaction_id existe et pointe vers une Transaction avec reservation_id
            ->orWhere(function($subQ) {
                $subQ->whereNotNull('metadata')
                     ->where('metadata', 'like', '%"transaction_id":%')
                     ->where('metadata', 'not like', '%"transaction_id":null%')
                     ->where('metadata', 'not like', '%"transaction_id":0%');
            })
            // Ou vérifier dans la description
            ->orWhere(function($subQ) {
                $subQ->where('description', 'like', '%Réservation #%')
                     ->orWhere('description', 'like', '%Reservation #%')
                     ->orWhere('description', 'like', '%réservation%');
            });
        });
    }

    /**
     * Scope for transactions NOT related to reservations
     * Exclut les transactions liées aux réservations
     */
    public function scopeNotRelatedToReservations($query)
    {
        return $query->where(function($q) {
            // Pas de reservation_id dans metadata
            $q->where(function($subQ) {
                $subQ->whereNull('metadata')
                     ->orWhere('metadata', 'not like', '%"reservation_id":%')
                     ->orWhere(function($metaSubQ) {
                         $metaSubQ->where('metadata', 'like', '%"reservation_id":null%')
                                  ->orWhere('metadata', 'like', '%"reservation_id":0%');
                     });
            })
            // ET pas de transaction_id qui pointe vers une Transaction avec reservation_id
            ->where(function($subQ) {
                $subQ->whereNull('metadata')
                     ->orWhere('metadata', 'not like', '%"transaction_id":%')
                     ->orWhere(function($metaSubQ) {
                         $metaSubQ->where('metadata', 'like', '%"transaction_id":null%')
                                  ->orWhere('metadata', 'like', '%"transaction_id":0%');
                     });
            })
            // ET pas de mention de réservation dans la description
            ->where(function($subQ) {
                $subQ->whereNull('description')
                     ->orWhere(function($descSubQ) {
                         $descSubQ->where('description', 'not like', '%Réservation #%')
                                  ->where('description', 'not like', '%Reservation #%')
                                  ->where('description', 'not like', '%réservation%');
                     });
            });
        });
    }

    /**
     * Check if this wallet transaction is related to a reservation
     * 
     * @return bool
     */
    public function isRelatedToReservation(): bool
    {
        return $this->getReservationId() !== null;
    }

    /**
     * Get the reservation ID related to this wallet transaction
     * 
     * @return int|null
     */
    public function getReservationId(): ?int
    {
        $metadata = $this->metadata ?? [];
        
        // Vérifier directement si reservation_id est présent dans metadata
        if (isset($metadata['reservation_id']) && $metadata['reservation_id'] > 0) {
            return (int) $metadata['reservation_id'];
        }
        
        // Vérifier via transaction_id si la Transaction associée a une réservation
        if (isset($metadata['transaction_id']) && $metadata['transaction_id'] > 0) {
            $transaction = \App\Models\Transaction::find($metadata['transaction_id']);
            if ($transaction && $transaction->reservation_id) {
                return (int) $transaction->reservation_id;
            }
        }
        
        // Essayer d'extraire depuis la description (format: "Réservation #123")
        if ($this->description) {
            if (preg_match('/Réservation\s*#(\d+)/i', $this->description, $matches)) {
                return (int) $matches[1];
            }
            if (preg_match('/Reservation\s*#(\d+)/i', $this->description, $matches)) {
                return (int) $matches[1];
            }
        }
        
        return null;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->type === 'credit' ? '+' : '-';
        $currency = $this->metadata['currency'] ?? MoneyService::DEFAULT_CURRENCY;
        $displayAmount = MoneyService::fromEur($this->amount, $currency);
        return $sign . MoneyService::format($displayAmount, $currency, false);
    }

    /**
     * Get formatted current balance (balance_after)
     */
    public function getFormattedCurrentBalanceAttribute(): string
    {
        $currency = $this->metadata['currency'] ?? MoneyService::DEFAULT_CURRENCY;
        $displayAmount = MoneyService::fromEur($this->balance_after ?? 0, $currency);
        return MoneyService::format($displayAmount, $currency);
    }
    
    /**
     * Get current balance (alias for balance_after for backward compatibility)
     */
    public function getCurrentBalanceAttribute(): float
    {
        return (float) ($this->balance_after ?? 0);
    }

    /**
     * Check if transaction is a credit
     */
    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    /**
     * Check if transaction is a debit
     */
    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get transaction type badge color
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->type) {
            'credit' => 'green',
            'debit' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }

    /**
     * Get transaction summary
     */
    public function getSummaryAttribute(): string
    {
        $action = $this->type === 'credit' ? 'Credited' : 'Debited';
        $amount = $this->formatted_amount;
        
        if ($this->description) {
            return "{$action} {$amount} - {$this->description}";
        }
        
        return "{$action} {$amount}";
    }

    /**
     * Get related transaction (for transfers)
     */
    public function getRelatedTransaction()
    {
        if (!$this->metadata || !isset($this->metadata['transfer_to']) && !isset($this->metadata['transfer_from'])) {
            return null;
        }

        $transferId = $this->metadata['transfer_to'] ?? $this->metadata['transfer_from'];
        $transferType = $this->metadata['transfer_type'] ?? null;

        if ($transferType === 'outgoing') {
            return static::where('wallet_id', $transferId)
                ->where('metadata->transfer_from', $this->wallet_id)
                ->where('metadata->transfer_type', 'incoming')
                ->first();
        } elseif ($transferType === 'incoming') {
            return static::where('wallet_id', $transferId)
                ->where('metadata->transfer_to', $this->wallet_id)
                ->where('metadata->transfer_type', 'outgoing')
                ->first();
        }

        return null;
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update(['status' => 'completed']);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }
        
        return $this->update([
            'status' => 'failed',
            'metadata' => $metadata
        ]);
    }

    /**
     * Mark transaction as pending
     */
    public function markAsPending(): bool
    {
        return $this->update(['status' => 'pending']);
    }

    /**
     * Get the related Transaction model (not WalletTransaction) if it exists
     * Utilise la relation déjà chargée si disponible, sinon charge depuis la base de données
     * 
     * @return \App\Models\Transaction|null
     */
    public function getTransactionModel()
    {
        // Vérifier si la relation est déjà chargée (optimisation pour éviter les requêtes N+1)
        if ($this->relationLoaded('relatedTransaction')) {
            return $this->getRelation('relatedTransaction');
        }
        
        // Méthode 1: Via metadata
        if ($this->metadata && isset($this->metadata['transaction_id'])) {
            return \App\Models\Transaction::with([
                'transactionDetail',
                'transactionDetail.adminCreator',
                'transactionDetail.integratorCreator',
                'transactionDetail.operator'
            ])->find($this->metadata['transaction_id']);
        }
        
        // Méthode 2: Via ID direct si WalletTransaction->id correspond à Transaction->id
        $transaction = \App\Models\Transaction::with([
            'transactionDetail',
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator'
        ])->find($this->id);
        
        return $transaction;
    }

    /**
     * Get admin fee from related Transaction model
     * Returns 0 if no related transaction or no admin fee
     * 
     * @return float
     */
    public function getAdminFee(): float
    {
        $transaction = $this->getTransactionModel();
        
        if ($transaction) {
            return $transaction->getAdminFee();
        }
        
        return 0.0;
    }

    /**
     * Get integrator fee from related Transaction model
     * Returns 0 if no related transaction or no integrator fee
     * 
     * @return float
     */
    public function getIntegratorFee(): float
    {
        $transaction = $this->getTransactionModel();
        
        if ($transaction) {
            return $transaction->getIntegratorFee();
        }
        
        return 0.0;
    }

    /**
     * Get operator share from related Transaction model
     * Returns 0 if no related transaction or no operator share
     * 
     * @return float
     */
    public function getOperatorShare(): float
    {
        $transaction = $this->getTransactionModel();
        
        if ($transaction) {
            return $transaction->getOperatorShare();
        }
        
        return 0.0;
    }

    /**
     * Get total fees from related Transaction model
     * Returns 0 if no related transaction or no fees
     * 
     * @return float
     */
    public function getTotalFees(): float
    {
        $transaction = $this->getTransactionModel();
        
        if ($transaction) {
            return $transaction->getTotalFees();
        }
        
        return 0.0;
    }

    /**
     * Get a descriptive label for the transaction
     * Génère une description automatique si elle est vide
     * 
     * @return string
     */
    public function getDescriptiveLabel(): string
    {
        // Si une description existe, l'utiliser
        if (!empty($this->description) && $this->description !== 'N/A') {
            return $this->description;
        }

        // Générer une description basée sur les métadonnées
        $metadata = $this->metadata ?? [];
        
        // Si liée à une Transaction
        if (isset($metadata['transaction_id'])) {
            $transactionId = $metadata['transaction_id'];
            $transaction = $this->getTransactionModel();
            
            if ($transaction) {
                if ($transaction->reservation) {
                    return "Crédit depuis Réservation #{$transaction->reservation->id} - Transaction #{$transactionId}";
                }
                return "Crédit depuis Transaction #{$transactionId}";
            }
            return "Crédit depuis Transaction #{$transactionId}";
        }
        
        // Si liée à un TransactionDetail
        if (isset($metadata['transaction_detail_id'])) {
            $detailId = $metadata['transaction_detail_id'];
            return "Part admin depuis TransactionDetail #{$detailId}";
        }
        
        // Si c'est un crédit de synchronisation
        if (isset($metadata['source']) && $metadata['source'] === 'balance_synchronization') {
            return "Synchronisation balance - Crédits depuis TransactionDetails approuvées";
        }
        
        // Si c'est un rechargement
        if (isset($metadata['source']) && $metadata['source'] === 'credit_recharge') {
            return "Rechargement de crédit";
        }
        
        // Description par défaut selon le type
        if ($this->type === 'credit') {
            return "Crédit de " . number_format($this->amount, 2) . " EUR";
        } elseif ($this->type === 'debit') {
            return "Débit de " . number_format($this->amount, 2) . " EUR";
        }
        
        return "Transaction wallet #{$this->id}";
    }

    /**
     * Get transaction source information
     * Retourne les informations sur l'origine de la transaction
     * 
     * @return array
     */
    public function getSourceInfo(): array
    {
        $metadata = $this->metadata ?? [];
        $info = [
            'type' => 'wallet',
            'description' => $this->getDescriptiveLabel(),
            'has_related_transaction' => false,
            'has_reservation' => false,
        ];
        
        // Vérifier si liée à une Transaction
        if (isset($metadata['transaction_id'])) {
            $transaction = $this->getTransactionModel();
            if ($transaction) {
                $info['has_related_transaction'] = true;
                $info['transaction_id'] = $transaction->id;
                $info['transaction'] = $transaction;
                
                if ($transaction->reservation) {
                    $info['has_reservation'] = true;
                    $info['reservation_id'] = $transaction->reservation->id;
                }
                
                if ($transaction->transactionDetail) {
                    $info['has_transaction_detail'] = true;
                    $info['transaction_detail_id'] = $transaction->transactionDetail->id;
                }
            }
        }
        
        // Vérifier si liée à un TransactionDetail directement
        if (isset($metadata['transaction_detail_id'])) {
            $info['has_transaction_detail'] = true;
            $info['transaction_detail_id'] = $metadata['transaction_detail_id'];
        }
        
        return $info;
    }
}
