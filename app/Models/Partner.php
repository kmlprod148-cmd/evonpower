<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCreator;

class Partner extends Model
{
    use HasFactory, HasCreator;

    protected $fillable = [
        'name',
        'contact_name',
        'type',
        'email',
        'phone',
        'city',
        'address',
        'postal_code',
        'country',
        'website',
        'logo',
        'description',
        'is_active',
        'integrator_id',
        'business_profile_id',
        'created_by',
        'created_by_role',
        'created_by_type',
        'created_by_id',
        'add_to_list',
        'contact_title',
        'contact_email',
        'contact_phone',
        'notes',
        'tax_id',
        'company_registration',
        'language',
        'stations_count',
        // Champs de contact étendus
        'technical_contact_name',
        'technical_contact_email',
        'technical_contact_phone',
        'technical_contact_title',
        'commercial_contact_name',
        'commercial_contact_email',
        'commercial_contact_phone',
        'commercial_contact_title',
        'internal_notes',
        // Champs business étendus
        'bank_name',
        'bank_account',
        'iban',
        'bic',
        'sector',
        'company_size',
        'annual_revenue',
        'employee_count',
        'billing_address',
        'billing_email',
        'payment_terms',
        'currency',
        // Champs de paramètres
        'timezone',
        'date_format',
        'receive_reports',
        'receive_notifications',
        'receive_marketing',
        'receive_sms',
        'access_level',
        'api_access',
        'max_users',
        'max_charging_points',
        'two_factor_auth',
        'password_expiry',
        'ip_restriction',
        'allowed_ips',
        'billing_frequency',
        'auto_renewal',
        'discount_percentage',
        'credit_limit',
        'collection_mode',
    ];

    public function setCollectionModeAttribute($value): void
    {
        $this->attributes['collection_mode'] = $value ?? 'admin';
    }

    protected $casts = [
        'is_active' => 'boolean',
        'add_to_list' => 'boolean',
        'receive_reports' => 'boolean',
        'receive_notifications' => 'boolean',
        'receive_marketing' => 'boolean',
        'receive_sms' => 'boolean',
        'api_access' => 'boolean',
        'two_factor_auth' => 'boolean',
        'password_expiry' => 'boolean',
        'ip_restriction' => 'boolean',
        'auto_renewal' => 'boolean',
        'annual_revenue' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    // Relations
    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'partner_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function chargingPoints()
    {
        return $this->hasMany(ChargingPoint::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForIntegrator($query, $integratorId)
    {
        return $query->where('integrator_id', $integratorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInList($query)
    {
        return $query->where('add_to_list', true);
    }

    // Accessors
    public function getActiveGroupsCountAttribute()
    {
        return $this->groups()->count();
    }

    public function getActiveChargingPointsCountAttribute()
    {
        return $this->chargingPoints()->where('status', 'online')->count();
    }

    /**
     * Get the pricing plans associated with this partner (many-to-many).
     */
    public function pricingPlans()
    {
        return $this->belongsToMany(PricingPlan::class, 'partner_pricing_plan')
                    ->withTimestamps();
    }

    /**
     * Get the partner's account.
     */
    public function account(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Account::class, 'accountable');
    }

    /**
     * Get the partner's wallet
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Get or create the partner's wallet
     */
    public function getOrCreateWallet(array $attributes = [])
    {
        return $this->wallet ?: \App\Services\WalletService::createWallet($this, $attributes);
    }

    /**
     * Credit amount to partner's wallet
     */
    public function creditWallet(float $amount, string $description = null, array $metadata = [])
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->credit($amount, $description, $metadata);
    }

    /**
     * Debit amount from partner's wallet
     */
    public function debitWallet(float $amount, string $description = null, array $metadata = [])
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->debit($amount, $description, $metadata);
    }

    /**
     * Get partner's wallet balance
     */
    public function getWalletBalance(): float
    {
        return $this->wallet ? $this->wallet->balance : 0;
    }

    /**
     * Check if partner has sufficient wallet balance
     */
    public function hasSufficientWalletBalance(float $amount): bool
    {
        return $this->wallet ? $this->wallet->hasSufficientBalance($amount) : false;
    }

    /**
     * Get formatted wallet balance
     */
    public function getFormattedWalletBalance(): string
    {
        return $this->wallet ? $this->wallet->getFormattedBalance() : '0.00 EUR';
    }

}