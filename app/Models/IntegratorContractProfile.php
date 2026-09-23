<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Integrator Contract Profile Model
 * 
 * Represents the billing contract configuration for each integrator.
 * Supports three billing components:
 * - Maintenance Fees (Frais maintenance): Recurring periodic charges
 * - Terminal Fees (Frais par borne active): Per-active-terminal charges
 * - Transaction Commissions (Commission par transaction): Per-transaction fees
 * 
 * @property int $id
 * @property int $integrator_id
 * @property string $contract_number
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property bool $maintenance_fee_enabled
 * @property float $maintenance_fee_amount
 * @property string|null $maintenance_fee_period
 * @property \Carbon\Carbon|null $maintenance_fee_start_date
 * @property \Carbon\Carbon|null $maintenance_fee_next_due_date
 * @property bool $terminal_fee_enabled
 * @property float $terminal_fee_amount
 * @property string|null $terminal_fee_period
 * @property int $terminal_fee_minimum
 * @property int $terminal_fee_free_count
 * @property bool $transaction_commission_enabled
 * @property float $transaction_commission_percentage
 * @property float $transaction_commission_fixed_amount
 * @property float $transaction_commission_min_amount
 * @property float|null $transaction_commission_max_amount
 * @property string $transaction_commission_type
 * @property string $currency
 * @property \Carbon\Carbon|null $contract_start_date
 * @property \Carbon\Carbon|null $contract_end_date
 * @property bool $auto_renewal
 * @property string|null $notes
 * @property array|null $metadata
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class IntegratorContractProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'integrator_id',
        'contract_number',
        'name',
        'description',
        'status',
        
        // Maintenance Fee Configuration
        'maintenance_fee_enabled',
        'maintenance_fee_amount',
        'maintenance_fee_period',
        'maintenance_fee_start_date',
        'maintenance_fee_next_due_date',
        
        // Terminal Fee Configuration
        'terminal_fee_enabled',
        'terminal_fee_amount',
        'terminal_fee_period',
        'terminal_fee_minimum',
        'terminal_fee_free_count',
        
        // Transaction Commission Configuration
        'transaction_commission_enabled',
        'transaction_commission_percentage',
        'transaction_commission_fixed_amount',
        'transaction_commission_min_amount',
        'transaction_commission_max_amount',
        'transaction_commission_type',
        
        // Currency
        'currency',
        
        // Contract dates
        'contract_start_date',
        'contract_end_date',
        'auto_renewal',
        
        // Metadata
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'maintenance_fee_enabled' => 'boolean',
        'maintenance_fee_amount' => 'decimal:2',
        'maintenance_fee_start_date' => 'date',
        'maintenance_fee_next_due_date' => 'date',
        
        'terminal_fee_enabled' => 'boolean',
        'terminal_fee_amount' => 'decimal:2',
        'terminal_fee_minimum' => 'integer',
        'terminal_fee_free_count' => 'integer',
        
        'transaction_commission_enabled' => 'boolean',
        'transaction_commission_percentage' => 'decimal:2',
        'transaction_commission_fixed_amount' => 'decimal:2',
        'transaction_commission_min_amount' => 'decimal:2',
        'transaction_commission_max_amount' => 'decimal:2',
        
        'auto_renewal' => 'boolean',
        'metadata' => 'json',
        
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
    ];

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_DRAFT = 'draft';

    /**
     * Billing periods
     */
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_YEARLY = 'yearly';

    /**
     * Commission types
     */
    public const COMMISSION_TYPE_PERCENTAGE = 'percentage';
    public const COMMISSION_TYPE_FIXED = 'fixed';
    public const COMMISSION_TYPE_COMBINED = 'combined';

    /**
     * Get the integrator that owns this contract profile.
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Get the user who created this contract.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this contract.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all billing line items for this contract.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(IntegratorBillingLineItem::class, 'integrator_contract_profile_id');
    }

    /**
     * Get all invoices for this contract.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(IntegratorBillingInvoice::class, 'integrator_contract_profile_id');
    }

    /**
     * Get pending invoices.
     */
    public function pendingInvoices(): HasMany
    {
        return $this->invoices()->where('status', 'pending');
    }

    /**
     * Get overdue invoices.
     */
    public function overdueInvoices(): HasMany
    {
        return $this->invoices()->where('status', 'overdue');
    }

    /**
     * Scope for active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for contracts with maintenance fee enabled.
     */
    public function scopeWithMaintenanceFee($query)
    {
        return $query->where('maintenance_fee_enabled', true);
    }

    /**
     * Scope for contracts with terminal fee enabled.
     */
    public function scopeWithTerminalFee($query)
    {
        return $query->where('terminal_fee_enabled', true);
    }

    /**
     * Scope for contracts with transaction commission enabled.
     */
    public function scopeWithTransactionCommission($query)
    {
        return $query->where('transaction_commission_enabled', true);
    }

    /**
     * Check if contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if maintenance fee is enabled and due.
     */
    public function isMaintenanceFeeDue(): bool
    {
        if (!$this->maintenance_fee_enabled || !$this->isActive()) {
            return false;
        }

        return $this->maintenance_fee_next_due_date 
            && $this->maintenance_fee_next_due_date->lte(now()->toDateString());
    }

    /**
     * Check if terminal fee is enabled.
     */
    public function isTerminalFeeEnabled(): bool
    {
        return $this->terminal_fee_enabled && $this->isActive();
    }

    /**
     * Check if transaction commission is enabled.
     */
    public function isTransactionCommissionEnabled(): bool
    {
        return $this->transaction_commission_enabled && $this->isActive();
    }

    /**
     * Calculate the next maintenance fee due date.
     */
    public function calculateNextMaintenanceDueDate(): ?\Carbon\Carbon
    {
        if (!$this->maintenance_fee_enabled || !$this->maintenance_fee_period) {
            return null;
        }

        $startDate = $this->maintenance_fee_start_date ?? now();
        
        return match($this->maintenance_fee_period) {
            self::PERIOD_MONTHLY => $startDate->copy()->addMonth(),
            self::PERIOD_QUARTERLY => $startDate->copy()->addMonths(3),
            self::PERIOD_YEARLY => $startDate->copy()->addYear(),
            default => null,
        };
    }

    /**
     * Calculate commission for a transaction amount.
     */
    public function calculateTransactionCommission(float $transactionAmount): float
    {
        if (!$this->isTransactionCommissionEnabled()) {
            return 0;
        }

        $commission = 0;

        switch ($this->transaction_commission_type) {
            case self::COMMISSION_TYPE_PERCENTAGE:
                $commission = $transactionAmount * ($this->transaction_commission_percentage / 100);
                break;
                
            case self::COMMISSION_TYPE_FIXED:
                $commission = $this->transaction_commission_fixed_amount;
                break;
                
            case self::COMMISSION_TYPE_COMBINED:
                $percentageCommission = $transactionAmount * ($this->transaction_commission_percentage / 100);
                $commission = $percentageCommission + $this->transaction_commission_fixed_amount;
                break;
        }

        // Apply minimum amount
        if ($commission < $this->transaction_commission_min_amount) {
            $commission = $this->transaction_commission_min_amount;
        }

        // Apply maximum amount if set
        if ($this->transaction_commission_max_amount !== null 
            && $commission > $this->transaction_commission_max_amount) {
            $commission = $this->transaction_commission_max_amount;
        }

        return round($commission, 2);
    }

    /**
     * Calculate terminal fee for given number of active terminals.
     */
    public function calculateTerminalFee(int $activeTerminalCount): float
    {
        if (!$this->isTerminalFeeEnabled()) {
            return 0;
        }

        // Apply free terminal count
        $billableTerminals = max(0, $activeTerminalCount - $this->terminal_fee_free_count);
        
        // Apply minimum terminal count
        $billableTerminals = max(0, $billableTerminals - $this->terminal_fee_minimum);
        
        return round($billableTerminals * $this->terminal_fee_amount, 2);
    }

    /**
     * Get formatted status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_SUSPENDED => 'Suspendu',
            self::STATUS_TERMINATED => 'Terminé',
            self::STATUS_DRAFT => 'Brouillon',
            default => $this->status,
        };
    }

    /**
     * Get formatted currency.
     */
    public function getFormattedCurrency(): string
    {
        return app(\App\Services\CurrencyService::class)->format($this->maintenance_fee_amount, $this->currency);
    }

    /**
     * Generate a unique contract number.
     */
    public static function generateContractNumber(): string
    {
        $prefix = 'CNT';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastContract = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastContract 
            ? (int) substr($lastContract->contract_number, -4) + 1 
            : 1;
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all active contracts that need billing processing.
     */
    public static function getContractsNeedingBilling(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->where(function ($query) {
                $query->where('maintenance_fee_enabled', true)
                    ->orWhere('terminal_fee_enabled', true);
            })
            ->where(function ($query) {
                $query->whereNull('maintenance_fee_next_due_date')
                    ->orWhere('maintenance_fee_next_due_date', '<=', now()->toDateString());
            })
            ->get();
    }
}
