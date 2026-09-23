<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixTransactionPriceTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fix-price-total 
                            {--transaction-id= : Specific transaction ID to fix}
                            {--all : Fix all transactions}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix transactions that have NULL price_total but have amount values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting transaction price_total fix...');

        $transactionId = $this->option('transaction-id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if ($transactionId) {
                $this->fixSpecificTransaction($transactionId, $dryRun);
            } elseif ($all) {
                $this->fixAllTransactions($dryRun);
            } else {
                $this->fixProblematicTransactions($dryRun);
            }

            $this->info('Transaction price_total fix completed successfully!');
        } catch (\Exception $e) {
            $this->error('Error during fix: ' . $e->getMessage());
            Log::error('Transaction price_total fix failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function fixSpecificTransaction($transactionId, $dryRun)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            $this->error("Transaction with ID {$transactionId} not found.");
            return;
        }

        $this->info("Processing transaction #{$transaction->id}...");
        $this->processTransaction($transaction, $dryRun);
    }

    protected function fixAllTransactions($dryRun)
    {
        $this->info('Fixing price_total for all transactions...');

        $transactions = Transaction::whereNull('price_total')
            ->orWhere('price_total', 0)
            ->orWhere('price_total', '')
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No transactions need fixing.');
            return;
        }

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        foreach ($transactions as $transaction) {
            $this->processTransaction($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function fixProblematicTransactions($dryRun)
    {
        $this->info('Finding transactions with NULL price_total but with amount values...');

        // Find transactions that need fixing
        $problematicTransactions = Transaction::where(function ($query) {
            $query->whereNull('price_total')
                  ->orWhere('price_total', 0)
                  ->orWhere('price_total', '');
        })->where('amount', '>', 0)->get();

        if ($problematicTransactions->isEmpty()) {
            $this->info('No problematic transactions found.');
            return;
        }

        $this->info("Found {$problematicTransactions->count()} transactions to fix.");

        $bar = $this->output->createProgressBar($problematicTransactions->count());
        $bar->start();

        foreach ($problematicTransactions as $transaction) {
            $this->processTransaction($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function processTransaction(Transaction $transaction, $dryRun)
    {
        try {
            // Calculate the correct price_total
            $newPriceTotal = $this->calculatePriceTotal($transaction);

            if ($newPriceTotal <= 0) {
                $this->warn("Transaction #{$transaction->id} cannot be fixed: calculated price_total is {$newPriceTotal}");
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
            $this->error("✗ Error processing transaction #{$transaction->id}: " . $e->getMessage());
            Log::error("Failed to fix price_total for transaction #{$transaction->id}", [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function calculatePriceTotal(Transaction $transaction): float
    {
        // Method 1: Use amount field if available
        if ($transaction->amount && $transaction->amount > 0) {
            return (float) $transaction->amount;
        }

        // Method 2: Calculate from individual price components
        $priceEnergy = (float) ($transaction->price_energy ?? 0);
        $priceTime = (float) ($transaction->price_time ?? 0);
        $priceService = (float) ($transaction->price_service ?? 0);
        $priceTax = (float) ($transaction->price_tax ?? 0);

        $calculatedTotal = $priceEnergy + $priceTime + $priceService + $priceTax;

        if ($calculatedTotal > 0) {
            return $calculatedTotal;
        }

        // Method 3: If we have energy delivered and pricing plan, calculate
        if ($transaction->energy_delivered && $transaction->energy_delivered > 0) {
            $pricingPlan = $transaction->tariffPlan;
            if ($pricingPlan && $pricingPlan->price_per_kwh) {
                $energyCost = $transaction->energy_delivered * $pricingPlan->price_per_kwh;
                $activationFee = $pricingPlan->activation_fee ?? 0;
                return $energyCost + $activationFee;
            }
        }

        return 0;
    }
}
