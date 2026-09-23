<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'revenue_share_id',
        'payer_account_id',
        'payee_account_id',
        'amount',
        'type',
        'status',
        'description',
    ];

    /**
     * Get the charging session transaction associated with the financial transaction.
     */
    public function chargingSessionTransaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Get the revenue share associated with the financial transaction.
     */
    public function revenueShare(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RevenueShare::class);
    }

    /**
     * Get the payer account.
     */
    public function payerAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'payer_account_id');
    }

    /**
     * Get the payee account.
     */
    public function payeeAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Account::class, 'payee_account_id');
    }
}
