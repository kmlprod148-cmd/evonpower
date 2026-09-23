<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Services\TransactionFeeCalculationService;
use App\Enums\TransactionStatus;

class DiagnoseAndFixBusinessProfileFees extends Command
{
    protected $signature = 'business-profiles:diagnose-and-fix-fees 
                            {--dry-run : Show what would be done without making changes}
                            {--fix-all : Apply all fixes}
                            {--link-charging-points : Link charging points to business profiles}
                            {--setup-sample-fees : Set up sample fee data}
                            {--apply-transaction-fees : Apply fees to transactions}';

    protected $description = 'Diagnose and fix all business profile fee issues comprehensively';

    protected $feeService;

    public function __construct(TransactionFeeCalculationService $feeService)
    {
        parent::__construct();
        $this->feeService = $feeService;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $fixAll = $this->option('fix-all');
        $linkChargingPoints = $this->option('link-charging-points') || $fixAll;
        $setupSampleFees = $this->option('setup-sample-fees') || $fixAll;
        $applyTransactionFees = $this->option('apply-transaction-fees') || $fixAll;

        $this->info('=== Business Profile Fee Diagnosis and Fix Tool ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // 1. Diagnose current state
        $this->diagnoseCurrentState();

        // 2. Link charging points to business profiles
        if ($linkChargingPoints) {
            $this->linkChargingPointsToBusinessProfiles($dryRun);
        }

        // 3. Setup sample fees in business profiles
        if ($setupSampleFees) {
            $this->setupSampleFeesInBusinessProfiles($dryRun);
        }

        // 4. Apply fees to transactions
        if ($applyTransactionFees) {
            $this->applyFeesToTransactions($dryRun);
        }

        $this->newLine();
        $this->info('=== Final Diagnosis ===');
        $this->diagnoseCurrentState();
        
        if (!$dryRun) {
            $this->info('All fixes applied successfully!');
        } else {
            $this->info('Dry run completed. Use --dry-run=false to apply changes.');
        }
    }

    protected function diagnoseCurrentState()
    {
        $this->info('1. Diagnosing Current State...');

        // Check business profiles
        $businessProfiles = BusinessProfile::all();
        $this->info("Total business profiles: {$businessProfiles->count()}");

        foreach ($businessProfiles as $bp) {
            $this->line("  - Business Profile #{$bp->id} '{$bp->name}':");
            $this->line("    * Base Fee Amount: €" . ($bp->base_fee_amount ?? 'NULL'));
            $this->line("    * Charge Fee Config: " . ($bp->charge_fee_config ?? 'NULL'));
            $this->line("    * Admin Fee Fixed: €" . ($bp->admin_fee_fixed ?? 'NULL'));
            $this->line("    * Admin Fee Percentage: " . ($bp->admin_fee_percentage ?? 'NULL') . "%");
        }

        // Check charging points
        $chargingPoints = ChargingPoint::all();
        $linkedChargingPoints = $chargingPoints->whereNotNull('business_profile_id');
        $unlinkedChargingPoints = $chargingPoints->whereNull('business_profile_id');

        $this->info("Total charging points: {$chargingPoints->count()}");
        $this->info("Linked to business profiles: {$linkedChargingPoints->count()}");
        $this->info("Not linked to business profiles: {$unlinkedChargingPoints->count()}");

        // Check transactions
        $transactions = Transaction::all();
        $transactionsWithFees = $transactions->where('activation_fee', '>', 0);
        $transactionsWithoutFees = $transactions->where('activation_fee', '<=', 0);

        $this->info("Total transactions: {$transactions->count()}");
        $this->info("Transactions with activation fees: {$transactionsWithFees->count()}");
        $this->info("Transactions without activation fees: {$transactionsWithoutFees->count()}");

        $this->newLine();
    }

    protected function linkChargingPointsToBusinessProfiles($dryRun)
    {
        $this->info('2. Linking Charging Points to Business Profiles...');

        $chargingPoints = ChargingPoint::whereNull('business_profile_id')->get();
        $businessProfiles = BusinessProfile::all();

        if ($chargingPoints->isEmpty()) {
            $this->info('All charging points are already linked to business profiles.');
            return;
        }

        if ($businessProfiles->isEmpty()) {
            $this->error('No business profiles found. Cannot link charging points.');
            return;
        }

        $this->info("Found {$chargingPoints->count()} charging points to link.");
        $this->info("Available business profiles: {$businessProfiles->count()}");

        $bar = $this->output->createProgressBar($chargingPoints->count());
        $bar->start();

        $businessProfileIndex = 0;
        $totalBusinessProfiles = $businessProfiles->count();

        foreach ($chargingPoints as $cp) {
            $businessProfile = $businessProfiles[$businessProfileIndex % $totalBusinessProfiles];
            
            if ($dryRun) {
                $this->line("Would link charging point '{$cp->name}' to business profile '{$businessProfile->name}'");
            } else {
                $cp->update(['business_profile_id' => $businessProfile->id]);
                $this->info("✓ Linked charging point '{$cp->name}' to business profile '{$businessProfile->name}'");
            }
            
            $businessProfileIndex++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function setupSampleFeesInBusinessProfiles($dryRun)
    {
        $this->info('3. Setting up Sample Fees in Business Profiles...');

        $businessProfiles = BusinessProfile::all();

        if ($businessProfiles->isEmpty()) {
            $this->error('No business profiles found.');
            return;
        }

        $sampleFees = [
            'base_fee_amount' => 11.00, // Frais d'activation
            'charge_fee_config' => [
                'fixed_amount' => 2.00,
                'percentage' => 3.50
            ],
            'admin_fee_fixed' => 0.50,
            'admin_fee_percentage' => 1.0,
            'integrator_fee_fixed' => 1.00,
            'integrator_fee_percentage' => 2.0,
            'partner_fee_fixed' => 0.75,
            'partner_fee_percentage' => 1.5,
        ];

        $bar = $this->output->createProgressBar($businessProfiles->count());
        $bar->start();

        foreach ($businessProfiles as $bp) {
            $updateData = [];

            // Only update if fields are empty or null
            if (empty($bp->base_fee_amount)) {
                $updateData['base_fee_amount'] = $sampleFees['base_fee_amount'];
            }
            if (empty($bp->charge_fee_config)) {
                $updateData['charge_fee_config'] = json_encode($sampleFees['charge_fee_config']);
            }
            if (empty($bp->admin_fee_fixed)) {
                $updateData['admin_fee_fixed'] = $sampleFees['admin_fee_fixed'];
            }
            if (empty($bp->admin_fee_percentage)) {
                $updateData['admin_fee_percentage'] = $sampleFees['admin_fee_percentage'];
            }
            if (empty($bp->integrator_fee_fixed)) {
                $updateData['integrator_fee_fixed'] = $sampleFees['integrator_fee_fixed'];
            }
            if (empty($bp->integrator_fee_percentage)) {
                $updateData['integrator_fee_percentage'] = $sampleFees['integrator_fee_percentage'];
            }
            if (empty($bp->partner_fee_fixed)) {
                $updateData['partner_fee_fixed'] = $sampleFees['partner_fee_fixed'];
            }
            if (empty($bp->partner_fee_percentage)) {
                $updateData['partner_fee_percentage'] = $sampleFees['partner_fee_percentage'];
            }

            if (!empty($updateData)) {
                if ($dryRun) {
                    $this->line("Would update business profile '{$bp->name}' with sample fees");
                } else {
                    $bp->update($updateData);
                    $this->info("✓ Updated business profile '{$bp->name}' with sample fees");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function applyFeesToTransactions($dryRun)
    {
        $this->info('4. Applying Fees to Transactions...');

        $transactions = Transaction::where(function ($query) {
            $query->whereNull('activation_fee')
                  ->orWhere('activation_fee', 0)
                  ->orWhere('activation_fee', '');
        })->get();

        if ($transactions->isEmpty()) {
            $this->info('No transactions need fee updates.');
            return;
        }

        $this->info("Found {$transactions->count()} transactions to update.");

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        foreach ($transactions as $transaction) {
            try {
                $fees = $this->feeService->calculateAllFees($transaction);
                
                $newActivationFee = $fees['activation_fee'];
                $newRechargeFee = $fees['recharge_fee'];
                $source = $fees['source'];

                if ($newActivationFee <= 0 && $newRechargeFee <= 0) {
                    $this->warn("Transaction #{$transaction->id}: No fees found (source: {$source})");
                    $bar->advance();
                    continue;
                }

                if ($dryRun) {
                    $activationFeeText = $newActivationFee > 0 ? "activation_fee: €{$newActivationFee}" : "";
                    $rechargeFeeText = $newRechargeFee > 0 ? "recharge_fee: €{$newRechargeFee}" : "";
                    $feesText = implode(", ", array_filter([$activationFeeText, $rechargeFeeText]));
                    $this->line("Would update transaction #{$transaction->id}: {$feesText} (from {$source})");
                } else {
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
                        $this->info("✓ Updated transaction #{$transaction->id}: {$feesText} (from {$source})");
                    }
                }

            } catch (\Exception $e) {
                $this->error("✗ Error updating transaction #{$transaction->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
