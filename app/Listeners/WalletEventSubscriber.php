<?php

namespace App\Listeners;

use App\Events\WalletCreated;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Events\WalletTransferCompleted;
use App\Events\WalletAutoRechargeTriggered;
use App\Events\WalletBalanceLow;
use App\Events\WalletBalanceHigh;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WalletCreatedNotification;
use App\Notifications\WalletCreditedNotification;
use App\Notifications\WalletDebitedNotification;
use App\Notifications\WalletTransferNotification;
use App\Notifications\WalletAutoRechargeNotification;
use App\Notifications\WalletBalanceLowNotification;
use App\Notifications\WalletBalanceHighNotification;

class WalletEventSubscriber
{
    /**
     * Handle wallet created events.
     */
    public function handleWalletCreated(WalletCreated $event)
    {
        $wallet = $event->wallet;
        $owner = $wallet->owner;
        
        Log::info('Wallet created', [
            'wallet_id' => $wallet->id,
            'owner_type' => $wallet->owner_type,
            'owner_id' => $wallet->owner_id,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
        ]);
        
        // Send notification to owner if it's a user
        if ($owner instanceof \App\Models\User) {
            $owner->notify(new WalletCreatedNotification($wallet));
        }
    }

    /**
     * Handle wallet credited events.
     */
    public function handleWalletCredited(WalletCredited $event)
    {
        $wallet = $event->wallet;
        $transaction = $event->transaction;
        $owner = $wallet->owner;
        
        Log::info('Wallet credited', [
            'wallet_id' => $wallet->id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'new_balance' => $wallet->balance,
            'description' => $transaction->description,
        ]);
        
        // Send notification to owner if it's a user
        if ($owner instanceof \App\Models\User) {
            $owner->notify(new WalletCreditedNotification($wallet, $transaction));
        }
        
        // Check for high balance threshold
        if ($wallet->max_balance && $wallet->balance >= $wallet->max_balance) {
            event(new WalletBalanceHigh($wallet, $transaction));
        }
    }

    /**
     * Handle wallet debited events.
     */
    public function handleWalletDebited(WalletDebited $event)
    {
        $wallet = $event->wallet;
        $transaction = $event->transaction;
        $owner = $wallet->owner;
        
        Log::info('Wallet debited', [
            'wallet_id' => $wallet->id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'new_balance' => $wallet->balance,
            'description' => $transaction->description,
        ]);
        
        // Send notification to owner if it's a user
        if ($owner instanceof \App\Models\User) {
            $owner->notify(new WalletDebitedNotification($wallet, $transaction));
        }
        
        // Check for low balance threshold
        if ($wallet->min_balance && $wallet->balance <= $wallet->min_balance) {
            event(new WalletBalanceLow($wallet, $transaction));
        }
        
        // Check for auto-recharge threshold
        if ($wallet->auto_recharge && $wallet->balance <= $wallet->auto_recharge_threshold) {
            event(new WalletAutoRechargeTriggered($wallet, $transaction));
        }
    }

    /**
     * Handle wallet transfer completed events.
     */
    public function handleWalletTransferCompleted(WalletTransferCompleted $event)
    {
        $sourceWallet = $event->sourceWallet;
        $destinationWallet = $event->destinationWallet;
        $sourceTransaction = $event->sourceTransaction;
        $destinationTransaction = $event->destinationTransaction;
        
        Log::info('Wallet transfer completed', [
            'source_wallet_id' => $sourceWallet->id,
            'destination_wallet_id' => $destinationWallet->id,
            'amount' => $sourceTransaction->amount,
            'source_balance' => $sourceWallet->balance,
            'destination_balance' => $destinationWallet->balance,
        ]);
        
        // Send notifications to both owners if they are users
        if ($sourceWallet->owner instanceof \App\Models\User) {
            $sourceWallet->owner->notify(new WalletTransferNotification(
                $sourceWallet, 
                $sourceTransaction, 
                'outgoing'
            ));
        }
        
        if ($destinationWallet->owner instanceof \App\Models\User) {
            $destinationWallet->owner->notify(new WalletTransferNotification(
                $destinationWallet, 
                $destinationTransaction, 
                'incoming'
            ));
        }
    }

    /**
     * Handle wallet auto-recharge triggered events.
     */
    public function handleWalletAutoRechargeTriggered(WalletAutoRechargeTriggered $event)
    {
        $wallet = $event->wallet;
        $triggerTransaction = $event->triggerTransaction;
        
        Log::info('Wallet auto-recharge triggered', [
            'wallet_id' => $wallet->id,
            'current_balance' => $wallet->balance,
            'threshold' => $wallet->auto_recharge_threshold,
            'recharge_amount' => $wallet->auto_recharge_amount,
        ]);
        
        // Attempt auto-recharge
        try {
            $rechargeTransaction = $wallet->performAutoRecharge();
            
            Log::info('Auto-recharge completed', [
                'wallet_id' => $wallet->id,
                'recharge_amount' => $rechargeTransaction->amount,
                'new_balance' => $wallet->fresh()->balance,
            ]);
            
            // Send notification to owner if it's a user
            if ($wallet->owner instanceof \App\Models\User) {
                $wallet->owner->notify(new WalletAutoRechargeNotification($wallet, $rechargeTransaction));
            }
        } catch (\Exception $e) {
            Log::error('Auto-recharge failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle wallet balance low events.
     */
    public function handleWalletBalanceLow(WalletBalanceLow $event)
    {
        $wallet = $event->wallet;
        $transaction = $event->transaction;
        
        Log::warning('Wallet balance low', [
            'wallet_id' => $wallet->id,
            'current_balance' => $wallet->balance,
            'min_balance' => $wallet->min_balance,
            'transaction_id' => $transaction->id,
        ]);
        
        // Send notification to owner if it's a user
        if ($wallet->owner instanceof \App\Models\User) {
            $wallet->owner->notify(new WalletBalanceLowNotification($wallet, $transaction));
        }
    }

    /**
     * Handle wallet balance high events.
     */
    public function handleWalletBalanceHigh(WalletBalanceHigh $event)
    {
        $wallet = $event->wallet;
        $transaction = $event->transaction;
        
        Log::info('Wallet balance high', [
            'wallet_id' => $wallet->id,
            'current_balance' => $wallet->balance,
            'max_balance' => $wallet->max_balance,
            'transaction_id' => $transaction->id,
        ]);
        
        // Send notification to owner if it's a user
        if ($wallet->owner instanceof \App\Models\User) {
            $wallet->owner->notify(new WalletBalanceHighNotification($wallet, $transaction));
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(WalletCreated::class, [WalletEventSubscriber::class, 'handleWalletCreated']);
        $events->listen(WalletCredited::class, [WalletEventSubscriber::class, 'handleWalletCredited']);
        $events->listen(WalletDebited::class, [WalletEventSubscriber::class, 'handleWalletDebited']);
        $events->listen(WalletTransferCompleted::class, [WalletEventSubscriber::class, 'handleWalletTransferCompleted']);
        $events->listen(WalletAutoRechargeTriggered::class, [WalletEventSubscriber::class, 'handleWalletAutoRechargeTriggered']);
        $events->listen(WalletBalanceLow::class, [WalletEventSubscriber::class, 'handleWalletBalanceLow']);
        $events->listen(WalletBalanceHigh::class, [WalletEventSubscriber::class, 'handleWalletBalanceHigh']);
    }
}
