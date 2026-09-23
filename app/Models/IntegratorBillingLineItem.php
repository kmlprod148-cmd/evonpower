<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Integrator Billing Line Item Model
 * 
 * Represents individual billing line items for integrator contracts.
 * Can be of type: maintenance, terminal, or commission.
 * 
 * @property int $id
 * @property int $integrator_contract_profile_id
 * @property int $integrator_id
 * @property string $type
 * @property int|null $transaction_id
 * @property int|null $charging_point_id
 * @property \Carbon\Carbon|null $billing_period_start
 * @property \Carbon\Carbon|null $billing_period_end
 * @property float $quantity
 * @property float $unit_price
 * @property float $subtotal
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $total_amount
 * @property string $currency
 * @property string $status
 * @property int|null $terminal_count
 * @property int|null $active_terminal_count
 * @property string|null $commission_type
 * @property float|null $commission_percentage
 * @property float|null $commission_fixed_amount
 * @property float|null $transaction_amount
 * @property string|null $description
 * @property string|null $notes
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class IntegratorBillingLineItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'integrator_contract_profile_id',
        'integrator_id',
        'type',
        'transaction_id',
        'charging_point_id',
        'billing_period_start',
        'billing_period_end',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'terminal_count',
        'active_terminal_count',
        'commission_type',
        'commission_percentage',
        'commission_fixed_amount',
        'transaction_amount',
        'description',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'commission_percentage' => 'decimal:2',
        'commission_fixed_amount' => 'decimal:2',
        'transaction_amount' => 'decimal:2',
        'metadata' => 'json',
    ];

    /**
     * Type constants
     */
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_TERMINAL = 'terminal';
    public const TYPE_COMMISSION = 'commission';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_INVOICED = 'invoiced';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the contract profile this line item belongs to.
     */
    public function contractProfile(): BelongsTo
    {
        return $this->belongsTo(IntegratorContractProfile::class, 'integrator_contract_profile_id');
    }

    /**
     * Get the integrator this line item belongs to.
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Get the related transaction if this is a commission line item.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the related charging point if this is a terminal fee line item.
     */
    public function chargingPoint(): BelongsTo
    {
        return $this->belongsTo(ChargingPoint::class);
    }

    /**
     * Get the invoices that contain this line item.
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(
            IntegratorBillingInvoice::class,
            'integrator_billing_invoice_line_item',
            'line_item_id',
            'invoice_id'
        )->withPivot(['amount', 'tax_amount', 'total_amount']);
    }

    /**
     * Scope for maintenance type line items.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('type', self::TYPE_MAINTENANCE);
    }

    /**
     * Scope for terminal type line items.
     */
    public function scopeTerminal($query)
    {
        return $query->where('type', self::TYPE_TERMINAL);
    }

    /**
     * Scope for commission type line items.
     */
    public function scopeCommission($query)
    {
        return $query->where('type', self::TYPE_COMMISSION);
    }

    /**
     * Scope for pending line items.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for calculated line items.
     */
    public function scopeCalculated($query)
    {
        return $query->where('status', self::STATUS_CALCULATED);
    }

    /**
     * Scope for invoiced line items.
     */
    public function scopeInvoiced($query)
    {
        return $query->where('status', self::STATUS_INVOICED);
    }

    /**
     * Scope for paid line items.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_MAINTENANCE => 'Frais de maintenance',
            self::TYPE_TERMINAL => 'Frais par borne active',
            self::TYPE_COMMISSION => 'Commission par transaction',
            default => $this->type,
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CALCULATED => 'Calculé',
            self::STATUS_INVOICED => 'Facturé',
            self::STATUS_PAID => 'Payé',
            self::STATUS_CANCELLED => 'Annulé',
            default => $this->status,
        };
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): void
    {
        $this->subtotal = round($this->quantity * $this->unit_price, 2);
        $this->tax_amount = round($this->subtotal * ($this->tax_rate / 100), 2);
        $this->total_amount = round($this->subtotal + $this->tax_amount, 2);
    }

    /**
     * Mark line item as calculated.
     */
    public function markAsCalculated(): void
    {
        $this->update(['status' => self::STATUS_CALCULATED]);
    }

    /**
     * Mark line item as invoiced.
     */
    public function markAsInvoiced(): void
    {
        $this->update(['status' => self::STATUS_INVOICED]);
    }

    /**
     * Mark line item as paid.
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    /**
     * Mark line item as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Get formatted total amount.
     */
    public function getFormattedTotalAmount(): string
    {
        return app(\App\Services\CurrencyService::class)->format($this->total_amount, $this->currency);
    }

    /**
     * Get formatted period.
     */
    public function getFormattedPeriodAttribute(): string
    {
        if (!$this->billing_period_start || !$this->billing_period_end) {
            return 'N/A';
        }

        return $this->billing_period_start->format('d/m/Y') . ' - ' . $this->billing_period_end->format('d/m/Y');
    }
}
