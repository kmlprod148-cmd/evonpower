<?php

namespace App\View\Components;

use App\Models\Transaction;
use App\Services\TransactionDisplayService;
use Illuminate\View\Component;

class TransactionAmounts extends Component
{
    public Transaction $transaction;
    public ?\App\Models\User $user;
    public array $amounts;
    public array $repartitionDetails;
    public bool $hasRealData;

    /**
     * Create a new component instance.
     */
    public function __construct(Transaction $transaction, ?\App\Models\User $user = null)
    {
        $this->transaction = $transaction;
        $this->user = $user ?? auth()->user();
        
        // Utiliser TransactionDisplayService pour obtenir les montants réels
        $displayService = app(TransactionDisplayService::class);
        $this->amounts = $displayService->getDisplayAmounts($transaction, $this->user);
        $this->repartitionDetails = $displayService->getRepartitionDetails($transaction, $this->user);
        
        // Vérifier si on a des données réelles depuis TransactionDetail
        $this->hasRealData = $this->transaction->transactionDetail !== null;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.transaction-amounts');
    }
    
    /**
     * Obtenir la part admin réelle
     */
    public function getAdminShare(): float
    {
        if ($this->transaction->transactionDetail) {
            return (float) ($this->transaction->transactionDetail->admin_share_amount ?? 0);
        }
        return $this->transaction->getAdminFee();
    }
    
    /**
     * Obtenir la part intégrateur réelle
     */
    public function getIntegratorShare(): float
    {
        if ($this->transaction->transactionDetail) {
            return (float) ($this->transaction->transactionDetail->integrator_share_amount ?? 0);
        }
        return $this->transaction->getIntegratorFee();
    }
    
    /**
     * Obtenir la part opérateur réelle
     */
    public function getOperatorShare(): float
    {
        if ($this->transaction->transactionDetail) {
            return (float) ($this->transaction->transactionDetail->operator_share_amount ?? 0);
        }
        return 0;
    }
    
    /**
     * Obtenir le montant total
     */
    public function getTotalAmount(): float
    {
        return $this->transaction->amount ?? $this->transaction->price_total ?? 0;
    }
}

