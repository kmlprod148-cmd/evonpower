<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\User;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     * Automatically assigns collect_user based on partner settings.
     */
    public function created(Transaction $transaction): void
    {
        $this->assignCollectUser($transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Log status changes for collect_status
        if ($transaction->wasChanged('collect_status')) {
            $this->logCollectStatusChange($transaction);
        }
    }

    /**
     * Assign collect_user based on partner's collect_setting
     */
    public function assignCollectUser(Transaction $transaction): void
    {
        // Only process charge transactions
        if (!in_array($transaction->transaction_type, ['charge', null])) {
            return;
        }

        // Skip if already assigned
        if ($transaction->collect_user_id && $transaction->collect_user_type) {
            return;
        }

        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint) {
            return;
        }

        // Get partner from charging point
        $partner = $chargingPoint->partner ?? null;
        
        if (!$partner) {
            // Try to get partner from group
            if ($chargingPoint->group && $chargingPoint->group->partner) {
                $partner = $chargingPoint->group->partner;
            }
        }

        if (!$partner) {
            // Default to admin if no partner found
            $this->assignToAdmin($transaction);
            return;
        }

        // Get partner's collect setting (default to admin)
        $collectSetting = $partner->collect_setting ?? 'admin';

        switch ($collectSetting) {
            case 'partner':
                $this->assignToPartner($transaction, $partner);
                break;
            case 'integrator':
                $this->assignToIntegrator($transaction, $partner);
                break;
            default:
                $this->assignToAdmin($transaction);
                break;
        }
    }

    /**
     * Assign collect_user to partner
     */
    private function assignToPartner(Transaction $transaction, Partner $partner): void
    {
        // Get the partner's user (the user associated with the partner)
        $partnerUser = $partner->user ?? User::where('partner_id', $partner->id)->first();
        
        if ($partnerUser) {
            $transaction->update([
                'collect_user_id' => $partnerUser->id,
                'collect_user_type' => 'partner',
                'collect_status' => 'to_collect',
                'collect_date' => now(),
            ]);
        } else {
            // Fallback to admin if no partner user found
            $this->assignToAdmin($transaction);
        }
    }

    /**
     * Assign collect_user to integrator
     */
    private function assignToIntegrator(Transaction $transaction, Partner $partner): void
    {
        $integrator = $partner->integrator;
        
        if (!$integrator) {
            // Fallback to admin if no integrator found
            $this->assignToAdmin($transaction);
            return;
        }

        // Get the integrator's user
        $integratorUser = $integrator->user ?? User::where('integrator_id', $integrator->id)->first();
        
        if ($integratorUser) {
            $transaction->update([
                'collect_user_id' => $integratorUser->id,
                'collect_user_type' => 'integrator',
                'collect_status' => 'to_collect',
                'collect_date' => now(),
            ]);
        } else {
            // Fallback to admin if no integrator user found
            $this->assignToAdmin($transaction);
        }
    }

    /**
     * Assign collect_user to admin
     */
    private function assignToAdmin(Transaction $transaction): void
    {
        $admin = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'super_admin');
            })
            ->first();

        if (!$admin) {
            $admin = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->first();
        }
        
        if ($admin) {
            $transaction->update([
                'collect_user_id' => $admin->id,
                'collect_user_type' => 'admin',
                'collect_status' => 'to_collect',
                'collect_date' => now(),
            ]);
        }
    }

    /**
     * Log collect_status changes
     */
    private function logCollectStatusChange(Transaction $transaction): void
    {
        $oldStatus = $transaction->getOriginal('collect_status');
        $newStatus = $transaction->collect_status;
        
        // Only log if status actually changed
        if ($oldStatus !== $newStatus) {
            \App\Models\TransactionLog::create([
                'transaction_id' => $transaction->id,
                'action' => 'collect_status_changed',
                'performed_by' => auth()->id(),
                'note' => "Status changed from {$oldStatus} to {$newStatus}",
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
                'created_at' => now(),
            ]);
        }
    }
}
