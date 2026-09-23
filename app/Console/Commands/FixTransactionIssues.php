<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Enums\TransactionStatus;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixTransactionIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fix-all-issues 
                            {--fix-price-total : Fix NULL price_total values}
                            {--fix-status : Fix transaction status based on reservation status}
                            {--fix-activation-fees : Fix activation fees from pricing plans}
                            {--all : Fix all issues}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix transaction issues: NULL price_total and status mismatches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting transaction issues fix...');

        $fixPriceTotal = $this->option('fix-price-total');
        $fixStatus = $this->option('fix-status');
        $fixActivationFees = $this->option('fix-activation-fees');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if ($all || $fixPriceTotal) {
                $this->fixPriceTotalIssues($dryRun);
            }

            if ($all || $fixStatus) {
                $this->fixStatusIssues($dryRun);
            }

            if ($all || $fixActivationFees) {
                $this->fixActivationFeesIssues($dryRun);
            }

            $this->info('Transaction issues fix completed successfully!');
        } catch (\Exception $e) {
            $this->error('Error during fix: ' . $e->getMessage());
            Log::error('Transaction issues fix failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function fixPriceTotalIssues($dryRun)
    {
        $this->info('Fixing NULL price_total values...');

        // Find transactions with NULL price_total but with amount values
        $problematicTransactions = Transaction::where(function ($query) {
            $query->whereNull('price_total')
                  ->orWhere('price_total', 0)
                  ->orWhere('price_total', '');
        })->where('amount', '>', 0)->get();

        if ($problematicTransactions->isEmpty()) {
            $this->info('No transactions with NULL price_total found.');
            return;
        }

        $this->info("Found {$problematicTransactions->count()} transactions to fix.");

        $bar = $this->output->createProgressBar($problematicTransactions->count());
        $bar->start();

        foreach ($problematicTransactions as $transaction) {
            $this->fixTransactionPriceTotal($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function fixStatusIssues($dryRun)
    {
        $this->info('Fixing transaction status based on reservation status...');

        // Find transactions that should be completed based on their reservation status
        $transactionsToFix = Transaction::whereHas('reservation', function ($query) {
            $query->whereIn('status', [
                ReservationStatus::CONFIRMED->value,
                ReservationStatus::COMPLETED->value
            ]);
        })->where('status', TransactionStatus::PENDING->value)->get();

        if ($transactionsToFix->isEmpty()) {
            $this->info('No transactions with status mismatches found.');
            return;
        }

        $this->info("Found {$transactionsToFix->count()} transactions with status mismatches.");

        $bar = $this->output->createProgressBar($transactionsToFix->count());
        $bar->start();

        foreach ($transactionsToFix as $transaction) {
            $this->fixTransactionStatus($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function fixTransactionPriceTotal(Transaction $transaction, $dryRun)
    {
        try {
            // Use amount as price_total if available
            $newPriceTotal = (float) $transaction->amount;

            if ($newPriceTotal <= 0) {
                $this->warn("Transaction #{$transaction->id} cannot be fixed: amount is {$newPriceTotal}");
                return;
            }

            if ($dryRun) {
                $this->line("Would fix transaction #{$transaction->id}: price_total from NULL to €{$newPriceTotal}");
                return;
            }

            // Update the transaction
            $transaction->update([
                'price_total' => $newPriceTotal
            ]);

            $this->info("✓ Transaction #{$transaction->id} price_total fixed: €{$newPriceTotal}");

        } catch (\Exception $e) {
            $this->error("✗ Error fixing price_total for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }

    protected function fixTransactionStatus(Transaction $transaction, $dryRun)
    {
        try {
            $reservation = $transaction->reservation;
            if (!$reservation) {
                $this->warn("Transaction #{$transaction->id} has no associated reservation");
                return;
            }

            $newStatus = null;
            $reason = '';

            // Determine the correct status based on reservation status
            $reservationStatusValue = $reservation->status->value ?? $reservation->status;
            
            switch ($reservationStatusValue) {
                case ReservationStatus::CONFIRMED->value:
                case ReservationStatus::COMPLETED->value:
                    $newStatus = TransactionStatus::COMPLETED->value;
                    $reason = "Reservation is {$reservationStatusValue}";
                    break;
                case ReservationStatus::CANCELED->value:
                    $newStatus = TransactionStatus::FAILED->value;
                    $reason = "Reservation is {$reservationStatusValue}";
                    break;
                default:
                    $this->warn("Transaction #{$transaction->id}: Reservation status '{$reservationStatusValue}' not handled");
                    return;
            }

            if ($dryRun) {
                $transactionStatusValue = $transaction->status->value ?? $transaction->status;
                $this->line("Would fix transaction #{$transaction->id}: status from {$transactionStatusValue} to {$newStatus} ({$reason})");
                return;
            }

            // Update the transaction
            $transaction->update([
                'status' => $newStatus,
                'payment_status' => $newStatus === TransactionStatus::COMPLETED->value ? 'completed' : 'failed'
            ]);

            $transactionStatusValue = $transaction->status->value ?? $transaction->status;
            $this->info("✓ Transaction #{$transaction->id} status fixed: {$newStatus} ({$reason})");

        } catch (\Exception $e) {
            $this->error("✗ Error fixing status for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }

    protected function fixActivationFeesIssues($dryRun)
    {
        $this->info('Fixing activation fees from business profiles and pricing plans...');

        // Find transactions with missing or incorrect activation fees
        $transactionsToFix = Transaction::where(function ($query) {
            $query->whereNull('activation_fee')
                  ->orWhere('activation_fee', 0)
                  ->orWhere('activation_fee', '');
        })->get();

        if ($transactionsToFix->isEmpty()) {
            $this->info('No transactions with missing activation fees found.');
            return;
        }

        $this->info("Found {$transactionsToFix->count()} transactions to fix activation fees.");

        $bar = $this->output->createProgressBar($transactionsToFix->count());
        $bar->start();

        foreach ($transactionsToFix as $transaction) {
            $this->fixTransactionActivationFee($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function fixTransactionActivationFee(Transaction $transaction, $dryRun)
    {
        try {
            // Use the comprehensive fee calculation service
            $feeService = app(\App\Services\TransactionFeeCalculationService::class);
            $fees = $feeService->calculateAllFees($transaction);
            
            $newActivationFee = $fees['activation_fee'];
            $newRechargeFee = $fees['recharge_fee'];
            $source = $fees['source'];

            if ($newActivationFee <= 0 && $newRechargeFee <= 0) {
                $this->warn("Transaction #{$transaction->id}: No activation fee or recharge fee found in business profile or pricing plan");
                return;
            }

            if ($dryRun) {
                $activationFeeText = $newActivationFee > 0 ? "activation_fee: €{$newActivationFee}" : "";
                $rechargeFeeText = $newRechargeFee > 0 ? "recharge_fee: €{$newRechargeFee}" : "";
                $feesText = implode(", ", array_filter([$activationFeeText, $rechargeFeeText]));
                $this->line("Would fix transaction #{$transaction->id}: {$feesText} (from {$source})");
                return;
            }

            // Update the transaction
            $updateData = [];
            if ($newActivationFee > 0) {
                $updateData['activation_fee'] = $newActivationFee;
            }
            if ($newRechargeFee > 0) {
                $updateData['price_service'] = $newRechargeFee;
            }
            
            if (!empty($updateData)) {
                $transaction->update($updateData);
                
                $activationFeeText = $newActivationFee > 0 ? "activation_fee: €{$newActivationFee}" : "";
                $rechargeFeeText = $newRechargeFee > 0 ? "recharge_fee: €{$newRechargeFee}" : "";
                $feesText = implode(", ", array_filter([$activationFeeText, $rechargeFeeText]));
                $this->info("✓ Transaction #{$transaction->id} fixed: {$feesText} (from {$source})");
            }

        } catch (\Exception $e) {
            $this->error("✗ Error fixing fees for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }
}
