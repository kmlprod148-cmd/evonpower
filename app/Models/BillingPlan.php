<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'billing_cycle_id',
        'billable_type', // User, BusinessProfile, ChargingPoint, etc.
        'billable_id',
        'amount',
        'currency',
        'tax_rate',
        'is_active',
        'start_date',
        'end_date',
        'auto_renew',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'auto_renew' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

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
     * Relation avec les factures
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
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
     * Scope pour les plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    /**
     * Scope pour les plans actifs à une date donnée
     */
    public function scopeActiveAt($query, $date)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    });
    }

    /**
     * Calculer le montant avec taxes
     */
    public function getAmountWithTaxAttribute(): float
    {
        return $this->amount + ($this->amount * $this->tax_rate / 100);
    }

    /**
     * Calculer le montant des taxes
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->amount * $this->tax_rate / 100;
    }

    /**
     * Vérifier si le plan est actif à une date donnée
     */
    public function isActiveAt($date = null): bool
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        
        return $this->is_active 
            && $this->start_date <= $date
            && ($this->end_date === null || $this->end_date >= $date);
    }

    /**
     * Obtenir la prochaine date de facturation
     */
    public function getNextBillingDate($fromDate = null): \Carbon\Carbon
    {
        return $this->billingCycle->getNextBillingDate($fromDate);
    }
}
