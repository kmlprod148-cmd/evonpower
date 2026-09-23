<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'transaction_fee_percentage',
        'transaction_fee_fixed',
        'transaction_fee_total',
        'admin_share_amount',
        'integrator_share_amount',
        'operator_share_amount',
        'admin_share_percentage',
        'integrator_share_percentage',
        'admin_creator_id',
        'integrator_creator_id',
        'operator_id',
        'admin_paid',
        'integrator_paid',
        'operator_paid',
        'admin_paid_at',
        'integrator_paid_at',
        'operator_paid_at',
        'notes',
        'calculation_details',
    ];

    protected $casts = [
        'transaction_fee_percentage' => 'decimal:2',
        'transaction_fee_fixed' => 'decimal:2',
        'transaction_fee_total' => 'decimal:2',
        'admin_share_amount' => 'decimal:2',
        'integrator_share_amount' => 'decimal:2',
        'operator_share_amount' => 'decimal:2',
        'admin_share_percentage' => 'decimal:2',
        'integrator_share_percentage' => 'decimal:2',
        'admin_paid' => 'boolean',
        'integrator_paid' => 'boolean',
        'operator_paid' => 'boolean',
        'admin_paid_at' => 'datetime',
        'integrator_paid_at' => 'datetime',
        'operator_paid_at' => 'datetime',
        'calculation_details' => 'array',
    ];

    /**
     * Relation avec la transaction principale
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Relation avec l'admin créateur
     */
    public function adminCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_creator_id');
    }

    /**
     * Relation avec l'intégrateur créateur
     */
    public function integratorCreator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'integrator_creator_id');
    }

    /**
     * Relation avec l'opérateur
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Obtenir le business profile utilisé pour cette transaction
     */
    public function getBusinessProfile(): ?\App\Models\BusinessProfile
    {
        if (isset($this->calculation_details['business_profile_id'])) {
            return \App\Models\BusinessProfile::find($this->calculation_details['business_profile_id']);
        }
        return null;
    }

    /**
     * Obtenir les détails des frais du business profile
     */
    public function getBusinessProfileFees(): array
    {
        return $this->calculation_details['transaction_fees'] ?? [];
    }

    /**
     * Obtenir les détails des parts du business profile
     */
    public function getBusinessProfileShares(): array
    {
        return $this->calculation_details['shares_calculation'] ?? [];
    }

    /**
     * Vérifier si tous les paiements sont effectués
     */
    public function areAllPaymentsCompleted(): bool
    {
        return $this->admin_paid && $this->integrator_paid && $this->operator_paid;
    }

    /**
     * Obtenir le total des parts
     */
    public function getTotalShares(): float
    {
        return round(
            (float) $this->admin_share_amount + 
            (float) $this->integrator_share_amount + 
            (float) $this->operator_share_amount, 
            2
        );
    }

    /**
     * Vérifier la cohérence entre le montant de la transaction et la somme des parts
     * 
     * @return array ['is_consistent' => bool, 'difference' => float, 'transaction_amount' => float, 'shares_total' => float]
     */
    public function validateAmountConsistency(): array
    {
        $transactionAmount = round((float) ($this->transaction->amount ?? 0), 2);
        $sharesTotal = $this->getTotalShares();
        $difference = abs($transactionAmount - $sharesTotal);
        
        // Tolérance de 0.01€ pour les arrondis
        $isConsistent = $difference <= 0.01;
        
        return [
            'is_consistent' => $isConsistent,
            'difference' => $difference,
            'transaction_amount' => $transactionAmount,
            'shares_total' => $sharesTotal,
            'tolerance' => 0.01
        ];
    }

    /**
     * Corriger automatiquement l'incohérence en ajustant le montant de la transaction
     * 
     * @return bool True si correction effectuée, False sinon
     */
    public function fixAmountConsistency(): bool
    {
        $validation = $this->validateAmountConsistency();
        
        if (!$validation['is_consistent'] && $validation['shares_total'] > 0) {
            // Mettre à jour le montant de la transaction pour correspondre à la somme des parts
            $this->transaction->update([
                'amount' => $validation['shares_total']
            ]);
            
            \Log::info('Correction automatique de l\'incohérence des montants dans TransactionDetail', [
                'transaction_detail_id' => $this->id,
                'transaction_id' => $this->transaction_id,
                'ancien_montant' => $validation['transaction_amount'],
                'nouveau_montant' => $validation['shares_total'],
                'difference' => $validation['difference']
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Obtenir le breakdown des calculs
     */
    public function getCalculationBreakdown(): array
    {
        return [
            'original_amount' => $this->transaction->amount ?? 0,
            'transaction_fees' => [
                'percentage' => $this->transaction_fee_percentage,
                'fixed' => $this->transaction_fee_fixed,
                'total' => $this->transaction_fee_total,
            ],
            'shares' => [
                'admin' => [
                    'percentage' => $this->admin_share_percentage,
                    'amount' => $this->admin_share_amount,
                ],
                'integrator' => [
                    'percentage' => $this->integrator_share_percentage,
                    'amount' => $this->integrator_share_amount,
                ],
                'operator' => [
                    'amount' => $this->operator_share_amount,
                ],
            ],
            'net_amount' => $this->getTotalShares(),
            'payment_status' => [
                'admin_paid' => $this->admin_paid,
                'integrator_paid' => $this->integrator_paid,
                'operator_paid' => $this->operator_paid,
            ],
        ];
    }
}
