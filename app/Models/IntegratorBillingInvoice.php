<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Integrator Billing Invoice Model
 * 
 * Represents an invoice generated for integrator billing.
 * Aggregates line items and tracks payment status.
 * 
 * @property int $id
 * @property int $integrator_contract_profile_id
 * @property int $integrator_id
 * @property string $invoice_number
 * @property string $invoice_type
 * @property string $status
 * @property \Carbon\Carbon $billing_period_start
 * @property \Carbon\Carbon $billing_period_end
 * @property \Carbon\Carbon $issue_date
 * @property \Carbon\Carbon $due_date
 * @property \Carbon\Carbon|null $paid_date
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total_amount
 * @property string $currency
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property string|null $payment_notes
 * @property string|null $notes
 * @property string|null $internal_notes
 * @property array|null $metadata
 * @property int|null $related_invoice_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $approved_by
 * @property int|null $paid_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class IntegratorBillingInvoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'integrator_contract_profile_id',
        'integrator_id',
        'invoice_number',
        'invoice_type',
        'status',
        'billing_period_start',
        'billing_period_end',
        'issue_date',
        'due_date',
        'paid_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'payment_method',
        'payment_reference',
        'payment_notes',
        'notes',
        'internal_notes',
        'metadata',
        'related_invoice_id',
        'created_by',
        'updated_by',
        'approved_by',
        'paid_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'json',
    ];

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Invoice type constants
     */
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_TERMINAL = 'terminal';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_CONSOLIDATED = 'consolidated';

    /**
     * Get the contract profile this invoice belongs to.
     */
    public function contractProfile(): BelongsTo
    {
        return $this->belongsTo(IntegratorContractProfile::class, 'integrator_contract_profile_id');
    }

    /**
     * Get the integrator this invoice belongs to.
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Get the user who created this invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this invoice.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this invoice.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who marked this invoice as paid.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get related invoice (for credits/adjustments).
     */
    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(IntegratorBillingInvoice::class, 'related_invoice_id');
    }

    /**
     * Get all line items for this invoice.
     */
    public function lineItems(): BelongsToMany
    {
        return $this->belongsToMany(
            IntegratorBillingLineItem::class,
            'integrator_billing_invoice_line_item',
            'invoice_id',
            'line_item_id'
        )->withPivot(['amount', 'tax_amount', 'total_amount']);
    }

    /**
     * Scope for draft invoices.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(function ($q) {
                $q->where('status', self::STATUS_PENDING)
                  ->where('due_date', '<', now()->toDateString());
            });
    }

    /**
     * Scope for invoices by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('invoice_type', $type);
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_OVERDUE])
            && $this->due_date->lt(now()->toDateString());
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(string $paymentMethod = null, string $paymentReference = null, int $paidBy = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_date' => now()->toDateString(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'paid_by' => $paidBy,
        ]);

        // Update all line items as paid
        $this->lineItems->each(function ($item) {
            $item->markAsPaid();
        });
    }

    /**
     * Mark invoice as cancelled.
     */
    public function markAsCancelled(string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'internal_notes' => $notes,
        ]);

        // Cancel all line items
        $this->lineItems->each(function ($item) {
            $item->markAsCancelled();
        });
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue(): void
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PAID => 'Payé',
            self::STATUS_OVERDUE => 'En retard',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_REFUNDED => 'Remboursé',
            default => $this->status,
        };
    }

    /**
     * Get the invoice type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->invoice_type) {
            self::TYPE_MAINTENANCE => 'Frais de maintenance',
            self::TYPE_TERMINAL => 'Frais par borne active',
            self::TYPE_COMMISSION => 'Commission par transaction',
            self::TYPE_CONSOLIDATED => 'Facture consolidée',
            default => $this->invoice_type,
        };
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
        return $this->billing_period_start->format('d/m/Y') . ' - ' . $this->billing_period_end->format('d/m/Y');
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(string $type = 'INV'): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        $lastInvoice = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice 
            ? (int) substr($lastInvoice->invoice_number, -4) + 1 
            : 1;
        
        return $type . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals from line items.
     */
    public function calculateTotals(): void
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($this->lineItems as $item) {
            $subtotal += $item->pivot->amount;
            $taxAmount += $item->pivot->tax_amount;
        }

        $this->subtotal = round($subtotal, 2);
        $this->tax_amount = round($taxAmount, 2);
        $this->total_amount = round($subtotal + $taxAmount, 2);
    }

    /**
     * Add a line item to this invoice.
     */
    public function addLineItem(IntegratorBillingLineItem $item, float $amount = null, float $taxAmount = null): void
    {
        $amount = $amount ?? $item->subtotal;
        $taxAmount = $taxAmount ?? $item->tax_amount;

        $this->lineItems()->attach($item->id, [
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $amount + $taxAmount,
        ]);

        $item->markAsInvoiced();
        $this->calculateTotals();
    }

    /**
     * Remove a line item from this invoice.
     */
    public function removeLineItem(IntegratorBillingLineItem $item): void
    {
        $this->lineItems()->detach($item->id);
        
        $item->update(['status' => IntegratorBillingLineItem::STATUS_CALCULATED]);
        $this->calculateTotals();
    }
}
