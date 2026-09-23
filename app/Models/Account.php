<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'accountable_type',
        'accountable_id',
        'balance',
        'currency',
    ];

    /**
     * Get the parent accountable model (BusinessProfile, Integrator, or Partner).
     */
    public function accountable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the financial transactions where this account is the payer.
     */
    public function sentTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'payer_account_id');
    }

    /**
     * Get the financial transactions where this account is the payee.
     */
    public function receivedTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'payee_account_id');
    }
}
