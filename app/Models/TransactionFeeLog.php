<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionFeeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'business_profile_id',
        'fee_type',
        'fee_category',
        'base_amount',
        'fee_rate',
        'fee_amount',
        'currency',
        'fee_config',
        'calculation_notes',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'fee_rate' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'fee_config' => 'json',
    ];

    /**
     * Relation avec la transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(EnhancedTransaction::class);
    }

    /**
     * Relation avec le business profile
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(EnhancedBusinessProfile::class);
    }

    /**
     * Obtenir le détail du calcul formaté
     */
    public function getCalculationDetails(): array
    {
        return [
            'fee_type' => $this->fee_type,
            'fee_category' => $this->fee_category,
            'base_amount' => $this->base_amount,
            'fee_rate' => $this->fee_rate,
            'fee_amount' => $this->fee_amount,
            'currency' => $this->currency,
            'calculation_formula' => $this->getCalculationFormula(),
            'notes' => $this->calculation_notes,
        ];
    }

    /**
     * Obtenir la formule de calcul
     */
    public function getCalculationFormula(): string
    {
        return match($this->fee_type) {
            'fixed' => "Montant fixe: {$this->fee_amount} {$this->currency}",
            'percentage' => "({$this->base_amount} × {$this->fee_rate}%) = {$this->fee_amount} {$this->currency}",
            'combined' => "Frais combinés: {$this->fee_amount} {$this->currency}",
            default => "Calcul non défini"
        };
    }

    /**
     * Scopes
     */
    public function scopeByFeeType($query, string $feeType)
    {
        return $query->where('fee_type', $feeType);
    }

    public function scopeByFeeCategory($query, string $feeCategory)
    {
        return $query->where('fee_category', $feeCategory);
    }

    public function scopeByTransaction($query, int $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    public function scopeByBusinessProfile($query, int $businessProfileId)
    {
        return $query->where('business_profile_id', $businessProfileId);
    }
}
