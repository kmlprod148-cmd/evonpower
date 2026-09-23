<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\RevenueDistributionService;
use Illuminate\Support\Facades\Log;

class RecalculateTransactionRevenueDistribution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:recalculate-revenue-distribution 
                            {--transaction-id= : Specific transaction ID to recalculate}
                            {--all : Recalculate all transactions}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate revenue distribution for transactions that have missing or zero breakdown';

    protected RevenueDistributionService $revenueDistributionService;

    public function __construct(RevenueDistributionService $revenueDistributionService)
    {
        parent::__construct();
        $this->revenueDistributionService = $revenueDistributionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting revenue distribution recalculation...');

        $transactionId = $this->option('transaction-id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            if ($transactionId) {
                $this->recalculateSpecificTransaction($transactionId, $dryRun);
            } elseif ($all) {
                $this->recalculateAllTransactions($dryRun);
            } else {
                $this->recalculateProblematicTransactions($dryRun);
            }

            $this->info('Revenue distribution recalculation completed successfully!');
        } catch (\Exception $e) {
            $this->error('Error during recalculation: ' . $e->getMessage());
            Log::error('Revenue distribution recalculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    protected function recalculateSpecificTransaction($transactionId, $dryRun)
    {
        $transaction = Transaction::with([
            'chargingPoint.station.owner.businessProfile.integrator',
            'chargingPoint.station.owner.businessProfile.partner',
            'businessProfile.creator'
        ])->find($transactionId);

        if (!$transaction) {
            $this->error("Transaction with ID {$transactionId} not found.");
            return;
        }

        $this->info("Processing transaction #{$transaction->id}...");
        $this->processTransaction($transaction, $dryRun);
    }

    protected function recalculateAllTransactions($dryRun)
    {
        $this->info('Recalculating revenue distribution for all transactions...');

        $transactions = Transaction::with([
            'chargingPoint.station.owner.businessProfile.integrator',
            'chargingPoint.station.owner.businessProfile.partner',
            'businessProfile.creator'
        ])->where('transaction_type', 'client')->get();

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        foreach ($transactions as $transaction) {
            $this->processTransaction($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function recalculateProblematicTransactions($dryRun)
    {
        $this->info('Finding transactions with missing or problematic revenue distribution...');

        // Find transactions that need recalculation
        $problematicTransactions = Transaction::with([
            'chargingPoint.station.owner.businessProfile.integrator',
            'chargingPoint.station.owner.businessProfile.partner',
            'businessProfile.creator'
        ])->where('transaction_type', 'client')
        ->where(function ($query) {
            $query->whereNull('repartition_breakdown')
                  ->orWhere('repartition_breakdown', '[]')
                  ->orWhere('repartition_breakdown', '{}')
                  ->orWhere('price_total', '>', 0);
        })->get();

        if ($problematicTransactions->isEmpty()) {
            $this->info('No problematic transactions found.');
            return;
        }

        $this->info("Found {$problematicTransactions->count()} transactions to recalculate.");

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
            // Check if transaction has valid data for revenue distribution
            if (!$this->canDistributeRevenue($transaction)) {
                $this->warn("Transaction #{$transaction->id} cannot have revenue distributed: " . $this->getDistributionIssue($transaction));
                return;
            }

            if ($dryRun) {
                $this->line("Would recalculate revenue distribution for transaction #{$transaction->id} (€{$transaction->price_total})");
                return;
            }

            // Recalculate revenue distribution
            $this->revenueDistributionService->distributeRevenue($transaction);

            // Reload the transaction to get updated data
            $transaction->refresh();

            if ($transaction->repartition_breakdown && is_array($transaction->repartition_breakdown)) {
                $this->info("✓ Transaction #{$transaction->id} revenue distribution recalculated successfully");
            } else {
                $this->warn("⚠ Transaction #{$transaction->id} revenue distribution calculation failed");
            }

        } catch (\Exception $e) {
            $this->error("✗ Error processing transaction #{$transaction->id}: " . $e->getMessage());
            Log::error("Failed to recalculate revenue distribution for transaction #{$transaction->id}", [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function canDistributeRevenue(Transaction $transaction): bool
    {
        // Check if transaction has a positive amount
        if ($transaction->price_total <= 0) {
            return false;
        }

        // Check if transaction has a charging point
        if (!$transaction->chargingPoint) {
            return false;
        }

        // Check if charging point has a station
        if (!$transaction->chargingPoint->station) {
            return false;
        }

        // Check if station has an owner
        if (!$transaction->chargingPoint->station->owner) {
            return false;
        }

        // Check if owner has a business profile
        if (!$transaction->chargingPoint->station->owner->businessProfile) {
            return false;
        }

        return true;
    }

    protected function getDistributionIssue(Transaction $transaction): string
    {
        if ($transaction->price_total <= 0) {
            return "Transaction amount is zero or negative (€{$transaction->price_total})";
        }

        if (!$transaction->chargingPoint) {
            return "Missing charging point";
        }

        if (!$transaction->chargingPoint->station) {
            return "Missing station";
        }

        if (!$transaction->chargingPoint->station->owner) {
            return "Missing station owner";
        }

        if (!$transaction->chargingPoint->station->owner->businessProfile) {
            return "Missing business profile";
        }

        return "Unknown issue";
    }
}
