<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BalanceSynchronizationService;
use App\Models\User;

class SynchronizeAllBalances extends Command
{
    protected $signature = 'balances:sync {--user-id= : Synchronize balance for specific user ID}';
    protected $description = 'Synchronize all user balances from approved TransactionDetails';

    public function handle()
    {
        $balanceSyncService = app(BalanceSynchronizationService::class);
        
        if ($this->option('user-id')) {
            $user = User::find($this->option('user-id'));
            if (!$user) {
                $this->error("User not found!");
                return 1;
            }
            
            $this->info("Synchronizing balance for user: {$user->name} (ID: {$user->id})");
            $result = $balanceSyncService->synchronizeUserBalance($user);
            
            if ($result['synchronized']) {
                $this->info("✓ Balance synchronized successfully!");
                $this->info("  Old balance: " . number_format($result['old_balance'], 2) . " €");
                $this->info("  New balance: " . number_format($result['new_balance'], 2) . " €");
                $this->info("  Total Credits: " . number_format($result['total_credits'], 2) . " €");
                $this->info("  Total Debits: " . number_format($result['total_debits'], 2) . " €");
            } else {
                $this->info("Balance was already up to date.");
                $this->info("  Current balance: " . number_format($result['new_balance'], 2) . " €");
                $this->info("  Total Credits: " . number_format($result['total_credits'], 2) . " €");
                $this->info("  Total Debits: " . number_format($result['total_debits'], 2) . " €");
            }
        } else {
            $this->info("Synchronizing balances for all users...");
            $results = $balanceSyncService->synchronizeAllUserBalances();
            
            $synchronizedCount = collect($results)->where('synchronized', true)->count();
            $upToDateCount = count($results) - $synchronizedCount;
            
            $this->info("Synchronization complete!");
            $this->info("  Synchronized: {$synchronizedCount}");
            $this->info("  Already up to date: {$upToDateCount}");
            $this->info("  Total users: " . count($results));
        }
        
        return 0;
    }
}

