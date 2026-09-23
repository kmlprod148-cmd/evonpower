<?php

namespace App\Services;

use App\Support\AppCurrency;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WithdrawalRequested;
use App\Notifications\WithdrawalStatusUpdated;

class WalletService
{
    /**
     * Create a new wallet for a user or entity
     */
    public static function createWallet($owner, array $attributes = []): \App\Models\Wallet
    {
        $defaults = [
            'balance' => 0,
            'currency' => AppCurrency::code(),
            'is_active' => true,
            'name' => $owner->name ?? 'Wallet',
            'description' => null,
            'min_balance' => null,
            'max_balance' => null,
            'auto_recharge' => false,
            'auto_recharge_threshold' => null,
            'auto_recharge_amount' => null,
        ];

        $walletData = array_merge($defaults, $attributes);

        return $owner->wallet()->create($walletData);
    }

    /**
     * Returns the total balance available to withdraw for a given user/entity
     */
    public function getBalance(int $userId, string $userType): array
    {
        $transactions = Transaction::where('collect_user_id', $userId)
            ->where('collect_user_type', $userType)
            ->get();

        $total = '0.00';
        $withdrawn = '0.00';

        foreach ($transactions as $tx) {
            // Sum all transactions for this collect_user (total transactions)
            $total = bcadd($total, (string)$tx->total_amount, 2);
            
            // Sum withdrawn transactions
            if ($tx->collect_status === 'withdrawn') {
                $withdrawn = bcadd($withdrawn, (string)$tx->total_amount, 2);
            }
        }

        $available = bcsub($total, $withdrawn, 2);

        return [
            'total' => $total,
            'withdrawn' => $withdrawn,
            'available' => $available,
            'currency' => AppCurrency::code(),
        ];
    }

    /**
     * Credit a transaction to the correct collect_user based on partner settings
     */
    public function assignCollectUser(Transaction $transaction): void
    {
        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint) return;

        $partner = $chargingPoint->partner;
        if (!$partner) return;

        // Get partner's collect setting
        $collectSetting = $partner->collect_setting ?? 'admin'; // admin, integrator, partner

        $collectUserId = null;
        $collectUserType = 'admin';

        if ($collectSetting === 'partner') {
            $collectUserId = $partner->user_id; // Partner collects
            $collectUserType = 'partner';
        } elseif ($collectSetting === 'integrator') {
            $integrator = $partner->integrator;
            if (!$integrator) {
                $admin = User::role('super_admin')->first();
                $collectUserId = $admin->id;
                $collectUserType = 'admin';
            } else {
                $collectUserId = $integrator->user_id;
                $collectUserType = 'integrator';
            }
        } else {
            // Admin collects
            $admin = User::role('super_admin')->first();
            $collectUserId = $admin->id;
            $collectUserType = 'admin';
        }

        $transaction->update([
            'collect_user_id' => $collectUserId,
            'collect_user_type' => $collectUserType,
            'collect_status' => 'to_collect',
            'collect_date' => now()
        ]);
    }

    /**
     * Process a withdrawal request
     */
    public function createWithdrawalRequest(int $requesterId, string $requesterType, float $amount): WithdrawalRequest
    {
        $balance = $this->getBalance($requesterId, $requesterType);

        if (bccomp((string)$amount, $balance['available'], 2) > 0) {
            throw new \Exception("Insufficient balance for withdrawal.");
        }

        $user = User::findOrFail($requesterId);
        
        // Find collector based on who should collect funds for this requester
        $collectorId = null;
        $collectorType = 'admin';
        
        if ($requesterType === 'partner') {
            $partner = $user->partner;
            // Determine who collects for this partner based on partner's collect setting
            $collectSetting = $partner->collect_setting ?? 'admin';
            
            if ($collectSetting === 'partner') {
                // Partner collects their own funds
                $collectorId = $partner->user_id;
                $collectorType = 'partner';
            } elseif ($collectSetting === 'integrator') {
                $integrator = $partner->integrator;
                if (!$integrator) {
                    $admin = User::role('super_admin')->first();
                    $collectorId = $admin->id;
                    $collectorType = 'admin';
                } else {
                    $collectorId = $integrator->user_id;
                    $collectorType = 'integrator';
                }
            } else {
                // Admin collects
                $admin = User::role('super_admin')->first();
                $collectorId = $admin->id;
                $collectorType = 'admin';
            }
        } else {
            // For admin/integrator requesting withdrawal, admin is the collector
            $admin = User::role('super_admin')->first();
            $collectorId = $admin->id;
            $collectorType = 'admin';
        }

        $request = WithdrawalRequest::create([
            'requester_id' => $requesterId,
            'requester_type' => $requesterType,
            'collector_id' => $collectorId,
            'collector_type' => $collectorType,
            'amount' => $amount,
            'currency' => $balance['currency'],
            'status' => 'pending',
        ]);

        // Notify collector via database notification
        $collector = User::find($collectorId);
        if ($collector) {
            Notification::send($collector, new WithdrawalRequested($request));
        }

        return $request;
    }

    /**
     * Approve a withdrawal (called by admin or integrator)
     */
    public function approveWithdrawal(WithdrawalRequest $request, string $externalTxId, string $note): void
    {
        DB::transaction(function () use ($request, $externalTxId, $note) {
            $request->update([
                'status' => 'paid',
                'external_transaction_id' => $externalTxId,
                'collector_note' => $note,
                'processed_at' => now(),
            ]);

            // Update all related transactions collect_status = 'withdrawn'
            Transaction::where('collect_user_id', $request->requester_id)
                ->where('collect_user_type', $request->requester_type)
                ->whereIn('collect_status', ['to_collect', 'collected'])
                ->update(['collect_status' => 'withdrawn']);

            // Send notification to requester
            $requester = User::find($request->requester_id);
            if ($requester) {
                Notification::send($requester, new WithdrawalStatusUpdated($request));
            }
        });
    }

    /**
     * Reject a withdrawal (called by admin or integrator)
     */
    public function rejectWithdrawal(WithdrawalRequest $request, string $note): void
    {
        $request->update([
            'status' => 'rejected',
            'collector_note' => $note,
            'processed_at' => now(),
        ]);

        // Send notification to requester
        $requester = User::find($request->requester_id);
        if ($requester) {
            Notification::send($requester, new WithdrawalStatusUpdated($request));
        }
    }
}
