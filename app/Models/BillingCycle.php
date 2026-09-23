<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'frequency', // daily, weekly, monthly, yearly
        'interval', // number of frequency units
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'interval' => 'integer'
    ];

    /**
     * Relation avec les plans de facturation
     */
    public function billingPlans(): HasMany
    {
        return $this->hasMany(BillingPlan::class);
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
     * Scope pour les cycles actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtenir la fréquence en français
     */
    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'yearly' => 'Annuel',
            default => $this->frequency
        };
    }

    /**
     * Calculer la prochaine date de facturation
     */
    public function getNextBillingDate($fromDate = null): \Carbon\Carbon
    {
        $date = $fromDate ? \Carbon\Carbon::parse($fromDate) : now();
        
        return match($this->frequency) {
            'daily' => $date->addDays($this->interval),
            'weekly' => $date->addWeeks($this->interval),
            'monthly' => $date->addMonths($this->interval),
            'yearly' => $date->addYears($this->interval),
            default => $date->addDay()
        };
    }
}
