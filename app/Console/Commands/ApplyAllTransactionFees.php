<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Services\TransactionFeeCalculationService;
use App\Services\RevenueDistributionService;
use App\Enums\TransactionStatus;
use App\Enums\ReservationStatus;

class ApplyAllTransactionFees extends Command
{
    protected $signature = 'transactions:apply-all-fees 
                            {--dry-run : Show what would be done without making changes}
                            {--fix-price-total : Fix price_total issues}
                            {--fix-activation-fees : Fix activation and recharge fees}
                            {--fix-status : Fix transaction status issues}
                            {--recalculate-revenue : Recalculate revenue distribution}
                            {--all : Apply all fixes}';

    protected $description = 'Apply all transaction fees and fix all issues comprehensively';

    protected $feeService;
    protected $revenueService;

    public function __construct(TransactionFeeCalculationService $feeService, RevenueDistributionService $revenueService)
    {
        parent::__construct();
        $this->feeService = $feeService;
        $this->revenueService = $revenueService;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $fixPriceTotal = $this->option('fix-price-total') || $this->option('all');
        $fixActivationFees = $this->option('fix-activation-fees') || $this->option('all');
        $fixStatus = $this->option('fix-status') || $this->option('all');
        $recalculateRevenue = $this->option('recalculate-revenue') || $this->option('all');

        $this->info('=== Transaction Fee Application and Fix Tool ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        $totalTransactions = Transaction::count();
        $this->info("Total transactions in database: {$totalTransactions}");

        // 1. Fix Price Total Issues
        if ($fixPriceTotal) {
            $this->fixPriceTotalIssues($dryRun);
        }

        // 2. Fix Activation and Recharge Fees
        if ($fixActivationFees) {
            $this->fixActivationFeesIssues($dryRun);
        }

        // 3. Fix Status Issues
        if ($fixStatus) {
            $this->fixStatusIssues($dryRun);
        }

        // 4. Recalculate Revenue Distribution
        if ($recalculateRevenue) {
            $this->recalculateRevenueDistribution($dryRun);
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->showSummary();
        
        if (!$dryRun) {
            $this->info('All fixes applied successfully!');
        } else {
            $this->info('Dry run completed. Use --dry-run=false to apply changes.');
        }
    }

    protected function fixPriceTotalIssues($dryRun)
    {
        $this->info('1. Fixing Price Total Issues...');

        // Find transactions with NULL or 0 price_total but positive amount
        $transactionsToFix = Transaction::where(function ($query) {
            $query->whereNull('price_total')
                  ->orWhere('price_total', 0)
                  ->orWhere('price_total', '');
        })->where('amount', '>', 0)->get();

        if ($transactionsToFix->isEmpty()) {
            $this->info('No transactions with price_total issues found.');
            return;
        }

        $this->info("Found {$transactionsToFix->count()} transactions with price_total issues.");

        $bar = $this->output->createProgressBar($transactionsToFix->count());
        $bar->start();

        foreach ($transactionsToFix as $transaction) {
            $this->fixTransactionPriceTotal($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function fixTransactionPriceTotal(Transaction $transaction, $dryRun)
    {
        try {
            $newPriceTotal = 0;
            $source = '';

            // Method 1: Use amount field
            if ($transaction->amount > 0) {
                $newPriceTotal = (float) $transaction->amount;
                $source = 'amount field';
            }
            // Method 2: Calculate from price components
            elseif ($transaction->price_energy > 0 || $transaction->price_time > 0 || $transaction->price_service > 0) {
                $newPriceTotal = (float) ($transaction->price_energy ?? 0) + 
                                (float) ($transaction->price_time ?? 0) + 
                                (float) ($transaction->price_service ?? 0) + 
                                (float) ($transaction->price_tax ?? 0);
                $source = 'price components';
            }
            // Method 3: Use estimated_cost from reservation
            elseif ($transaction->reservation && $transaction->reservation->estimated_cost > 0) {
                $newPriceTotal = (float) $transaction->reservation->estimated_cost;
                $source = 'reservation estimated_cost';
            }

            if ($newPriceTotal <= 0) {
                $this->warn("Transaction #{$transaction->id}: No valid price source found");
                return;
            }

            if ($dryRun) {
                $this->line("Would fix transaction #{$transaction->id}: price_total: €{$newPriceTotal} (from {$source})");
                return;
            }

            $transaction->update(['price_total' => $newPriceTotal]);
            $this->info("✓ Transaction #{$transaction->id} price_total fixed: €{$newPriceTotal} (from {$source})");

        } catch (\Exception $e) {
            $this->error("✗ Error fixing price_total for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }

    protected function fixActivationFeesIssues($dryRun)
    {
        $this->info('2. Fixing Activation and Recharge Fees...');

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
            $fees = $this->feeService->calculateAllFees($transaction);
            
            $newActivationFee = $fees['activation_fee'];
            $newRechargeFee = $fees['recharge_fee'];
            $source = $fees['source'];

            if ($newActivationFee <= 0 && $newRechargeFee <= 0) {
                $this->warn("Transaction #{$transaction->id}: No activation fee or recharge fee found");
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
                $this->info("✓ Transaction #{$transaction->id} fees fixed: {$feesText} (from {$source})");
            }

        } catch (\Exception $e) {
            $this->error("✗ Error fixing fees for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }

    protected function fixStatusIssues($dryRun)
    {
        $this->info('3. Fixing Transaction Status Issues...');

        // Find transactions with status mismatches
        $transactionsToFix = Transaction::whereHas('reservation', function ($query) {
            $query->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::COMPLETED]);
        })->whereIn('status', [TransactionStatus::PENDING])->get();

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

    protected function fixTransactionStatus(Transaction $transaction, $dryRun)
    {
        try {
            $reservation = $transaction->reservation;
            if (!$reservation) {
                return;
            }

            $newStatus = null;
            $reason = '';

            switch ($reservation->status->value) {
                case ReservationStatus::CONFIRMED->value:
                case ReservationStatus::COMPLETED->value:
                    $newStatus = TransactionStatus::COMPLETED->value;
                    $reason = "Reservation is {$reservation->status->value}";
                    break;
                case ReservationStatus::CANCELED->value:
                    $newStatus = TransactionStatus::FAILED->value;
                    $reason = "Reservation is {$reservation->status->value}";
                    break;
                default:
                    $this->warn("Transaction #{$transaction->id}: Reservation status '{$reservation->status->value}' not handled");
                    return;
            }

            if ($dryRun) {
                $this->line("Would fix transaction #{$transaction->id}: status {$transaction->status->value} → {$newStatus} ({$reason})");
                return;
            }

            $transaction->update(['status' => $newStatus]);
            $this->info("✓ Transaction #{$transaction->id} status fixed: {$transaction->status->value} → {$newStatus} ({$reason})");

        } catch (\Exception $e) {
            $this->error("✗ Error fixing status for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }

    protected function recalculateRevenueDistribution($dryRun)
    {
        $this->info('4. Recalculating Revenue Distribution...');

        // Get all transactions with positive price_total
        $transactionsToRecalculate = Transaction::where('price_total', '>', 0)->get();

        if ($transactionsToRecalculate->isEmpty()) {
            $this->info('No transactions with positive price_total found for revenue recalculation.');
            return;
        }

        $this->info("Found {$transactionsToRecalculate->count()} transactions to recalculate revenue distribution.");

        $bar = $this->output->createProgressBar($transactionsToRecalculate->count());
        $bar->start();

        foreach ($transactionsToRecalculate as $transaction) {
            $this->recalculateTransactionRevenue($transaction, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function recalculateTransactionRevenue(Transaction $transaction, $dryRun)
    {
        try {
            if ($dryRun) {
                $this->line("Would recalculate revenue distribution for transaction #{$transaction->id}");
                return;
            }

            // Use the fee calculation service to get the breakdown
            $fees = $this->feeService->calculateAllFees($transaction);
            
            // Create a simple breakdown without financial transactions
            $breakdown = [
                'activation_fee' => $fees['activation_fee'],
                'recharge_fee' => $fees['recharge_fee'],
                'admin_fee' => $fees['admin_fee'],
                'integrator_fee' => $fees['integrator_fee'],
                'partner_fee' => $fees['partner_fee'],
                'total_fees' => $fees['total_fees'],
                'revenue_to_distribute' => $fees['revenue_to_distribute'],
                'source' => $fees['source'],
                'calculated_at' => now()->toISOString()
            ];
            
            $transaction->update(['repartition_breakdown' => $breakdown]);
            $this->info("✓ Revenue distribution recalculated for transaction #{$transaction->id}");

        } catch (\Exception $e) {
            $this->error("✗ Error recalculating revenue for transaction #{$transaction->id}: " . $e->getMessage());
        }
    }

    protected function showSummary()
    {
        $totalTransactions = Transaction::count();
        $transactionsWithPriceTotal = Transaction::where('price_total', '>', 0)->count();
        $transactionsWithActivationFee = Transaction::where('activation_fee', '>', 0)->count();
        $transactionsWithRevenueDistribution = Transaction::whereNotNull('repartition_breakdown')->count();
        $completedTransactions = Transaction::where('status', TransactionStatus::COMPLETED->value)->count();

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Transactions', $totalTransactions, '100%'],
                ['With Price Total > 0', $transactionsWithPriceTotal, round(($transactionsWithPriceTotal / $totalTransactions) * 100, 1) . '%'],
                ['With Activation Fee > 0', $transactionsWithActivationFee, round(($transactionsWithActivationFee / $totalTransactions) * 100, 1) . '%'],
                ['With Revenue Distribution', $transactionsWithRevenueDistribution, round(($transactionsWithRevenueDistribution / $totalTransactions) * 100, 1) . '%'],
                ['Completed Status', $completedTransactions, round(($completedTransactions / $totalTransactions) * 100, 1) . '%'],
            ]
        );
    }
}
