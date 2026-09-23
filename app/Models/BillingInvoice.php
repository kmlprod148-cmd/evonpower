<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillingInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'billing_plan_id',
        'billable_type',
        'billable_id',
        'billing_cycle_id',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'status', // pending, paid, overdue, cancelled
        'billing_period_start',
        'billing_period_end',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_reference',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime'
    ];

    /**
     * Relation avec le plan de facturation
     */
    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }

    /**
     * Relation avec le cycle de facturation
     */
    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class);
    }

    /**
     * Relation polymorphe avec l'entité facturable
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation avec les paiements
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur modificateur
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope pour les factures en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les factures payées
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope pour les factures en retard
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Scope pour les factures en retard (date d'échéance dépassée)
     */
    public function scopeOverdueByDate($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope pour une période donnée
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('billing_period_start', [$startDate, $endDate])
                    ->orWhereBetween('billing_period_end', [$startDate, $endDate]);
    }

    /**
     * Vérifier si la facture est en retard
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date < now();
    }

    /**
     * Marquer la facture comme payée
     */
    public function markAsPaid($paymentMethod = null, $paymentReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference
        ]);
    }

    /**
     * Marquer la facture comme en retard
     */
    public function markAsOverdue(): void
    {
        $this->update(['status' => 'overdue']);
    }

    /**
     * Obtenir le statut en français
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'paid' => 'Payée',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
            default => $this->status
        };
    }

    /**
     * Obtenir la durée de la période de facturation
     */
    public function getPeriodDurationAttribute(): int
    {
        return $this->billing_period_start->diffInDays($this->billing_period_end);
    }

    /**
     * Générer un numéro de facture unique
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastInvoice = static::whereYear('created_at', $year)
                           ->whereMonth('created_at', $month)
                           ->orderBy('id', 'desc')
                           ->first();
        
        $sequence = $lastInvoice ? 
            (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
