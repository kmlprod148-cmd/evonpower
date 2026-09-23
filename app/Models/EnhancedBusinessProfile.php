<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnhancedBusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'owner_type',
        'admin_fixed_fee',
        'integrator_fixed_fee',
        'operator_fixed_fee',
        'admin_percentage_fee',
        'integrator_percentage_fee',
        'operator_percentage_fee',
        'use_fixed_fees',
        'use_percentage_fees',
        'combine_fees',
        'min_transaction_amount',
        'max_transaction_amount',
        'daily_limit',
        'monthly_limit',
        'supports_admin_to_integrator',
        'supports_integrator_to_operator',
        'supports_recharge',
        'is_active',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'admin_fixed_fee' => 'decimal:2',
        'integrator_fixed_fee' => 'decimal:2',
        'operator_fixed_fee' => 'decimal:2',
        'admin_percentage_fee' => 'decimal:2',
        'integrator_percentage_fee' => 'decimal:2',
        'operator_percentage_fee' => 'decimal:2',
        'use_fixed_fees' => 'boolean',
        'use_percentage_fees' => 'boolean',
        'combine_fees' => 'boolean',
        'min_transaction_amount' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'supports_admin_to_integrator' => 'boolean',
        'supports_integrator_to_operator' => 'boolean',
        'supports_recharge' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Relation avec le propriétaire (utilisateur)
     */
    public function owner()
    {
        return $this->belongsTo(EnhancedUser::class, 'owner_id');
    }

    /**
     * Utilisateurs utilisant ce business profile
     */
    public function users()
    {
        return $this->hasMany(EnhancedUser::class);
    }

    /**
     * Transactions utilisant ce business profile
     */
    public function transactions()
    {
        return $this->hasMany(EnhancedTransaction::class);
    }

    /**
     * Logs de frais pour ce business profile
     */
    public function feeLogs()
    {
        return $this->hasMany(TransactionFeeLog::class);
    }

    /**
     * Calculer les frais pour un montant donné
     */
    public function calculateFees(float $amount, string $transactionType): array
    {
        $fees = [
            'admin_fee' => 0.00,
            'integrator_fee' => 0.00,
            'operator_fee' => 0.00,
            'total_fees' => 0.00,
            'breakdown' => []
        ];

        // Déterminer quels frais appliquer selon le type de transaction
        $applicableFees = $this->getApplicableFees($transactionType);

        foreach ($applicableFees as $feeType) {
            $feeAmount = 0.00;
            $feeDetails = [];

            // Frais fixes
            if ($this->use_fixed_fees) {
                $fixedFee = $this->getFixedFee($feeType);
                $feeAmount += $fixedFee;
                $feeDetails['fixed'] = $fixedFee;
            }

            // Frais en pourcentage
            if ($this->use_percentage_fees) {
                $percentageFee = $this->getPercentageFee($feeType, $amount);
                $feeAmount += $percentageFee;
                $feeDetails['percentage'] = $percentageFee;
            }

            // Si combine_fees est false, utiliser seulement le type de frais activé
            if (!$this->combine_fees) {
                if ($this->use_fixed_fees && !$this->use_percentage_fees) {
                    $feeAmount = $this->getFixedFee($feeType);
                    $feeDetails = ['fixed' => $feeAmount];
                } elseif ($this->use_percentage_fees && !$this->use_fixed_fees) {
                    $feeAmount = $this->getPercentageFee($feeType, $amount);
                    $feeDetails = ['percentage' => $feeAmount];
                }
            }

            $fees[$feeType . '_fee'] = $feeAmount;
            $fees['total_fees'] += $feeAmount;
            $fees['breakdown'][$feeType] = $feeDetails;
        }

        return $fees;
    }

    /**
     * Obtenir les frais fixes pour un type donné
     */
    private function getFixedFee(string $feeType): float
    {
        return match($feeType) {
            'admin' => $this->admin_fixed_fee,
            'integrator' => $this->integrator_fixed_fee,
            'operator' => $this->operator_fixed_fee,
            default => 0.00
        };
    }

    /**
     * Obtenir les frais en pourcentage pour un type donné
     */
    private function getPercentageFee(string $feeType, float $amount): float
    {
        $percentage = match($feeType) {
            'admin' => $this->admin_percentage_fee,
            'integrator' => $this->integrator_percentage_fee,
            'operator' => $this->operator_percentage_fee,
            default => 0.00
        };

        return ($amount * $percentage) / 100;
    }

    /**
     * Déterminer quels frais sont applicables selon le type de transaction
     */
    private function getApplicableFees(string $transactionType): array
    {
        return match($transactionType) {
            'admin_to_integrator' => ['admin', 'integrator'],
            'integrator_to_operator' => ['integrator', 'operator'],
            'recharge' => ['admin'],
            'refund' => ['admin'],
            'commission' => ['admin', 'integrator', 'operator'],
            default => []
        };
    }

    /**
     * Vérifier si ce business profile supporte un type de transaction
     */
    public function supportsTransactionType(string $transactionType): bool
    {
        return match($transactionType) {
            'admin_to_integrator' => $this->supports_admin_to_integrator,
            'integrator_to_operator' => $this->supports_integrator_to_operator,
            'recharge' => $this->supports_recharge,
            default => false
        };
    }

    /**
     * Vérifier si un montant est dans les limites autorisées
     */
    public function isAmountWithinLimits(float $amount): bool
    {
        return $amount >= $this->min_transaction_amount && 
               $amount <= $this->max_transaction_amount;
    }

    /**
     * Obtenir la configuration des frais
     */
    public function getFeeConfiguration(): array
    {
        return [
            'fixed_fees' => [
                'admin' => $this->admin_fixed_fee,
                'integrator' => $this->integrator_fixed_fee,
                'operator' => $this->operator_fixed_fee,
            ],
            'percentage_fees' => [
                'admin' => $this->admin_percentage_fee,
                'integrator' => $this->integrator_percentage_fee,
                'operator' => $this->operator_percentage_fee,
            ],
            'settings' => [
                'use_fixed_fees' => $this->use_fixed_fees,
                'use_percentage_fees' => $this->use_percentage_fees,
                'combine_fees' => $this->combine_fees,
            ],
            'limits' => [
                'min_transaction_amount' => $this->min_transaction_amount,
                'max_transaction_amount' => $this->max_transaction_amount,
                'daily_limit' => $this->daily_limit,
                'monthly_limit' => $this->monthly_limit,
            ]
        ];
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByOwnerType($query, string $ownerType)
    {
        return $query->where('owner_type', $ownerType);
    }

    public function scopeSupportsTransactionType($query, string $transactionType)
    {
        return $query->where("supports_{$transactionType}", true);
    }

    /**
     * Scopes pour le contrôle d'accès basé sur les rôles
     */
    public function scopeAccessibleByUser($query, EnhancedUser $user)
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

    public function scopeConfigurableByUser($query, EnhancedUser $user)
    {
        switch ($user->role) {
            case 'admin':
                // Admin peut configurer tous les business profiles
                return $query;
                
            case 'integrator':
                // Integrator peut configurer ses business profiles + ceux de ses opérateurs
                $operatorIds = EnhancedUser::where('created_by', $user->id)
                    ->where('role', 'operator')
                    ->pluck('id')
                    ->toArray();
                
                $accessibleIds = array_merge([$user->id], $operatorIds);
                
                return $query->whereIn('owner_id', $accessibleIds);
                
            case 'operator':
                // Operator ne peut configurer aucun business profile
                return $query->whereRaw('1 = 0');
                
            default:
                return $query->whereRaw('1 = 0');
        }
    }

    public function scopeWithOwnerDetails($query)
    {
        return $query->with('owner:id,name,role,email');
    }

    public function scopeWithTransactionStats($query)
    {
        return $query->withCount([
            'transactions as total_transactions',
            'transactions as completed_transactions' => function($q) {
                $q->where('status', 'completed');
            },
            'transactions as pending_transactions' => function($q) {
                $q->where('status', 'pending');
            },
            'transactions as canceled_transactions' => function($q) {
                $q->where('status', 'canceled');
            },
            'transactions as failed_transactions' => function($q) {
                $q->where('status', 'failed');
            }
        ]);
    }
}
