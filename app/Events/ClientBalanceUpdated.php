<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenché quand le solde client change (crédit, débit, recharge).
 * Permet aux listeners de synchroniser/invalider le cache.
 */
class ClientBalanceUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public float $balance,
        public string $formattedBalance,
        public string $currency = 'EUR',
    ) {}
}
