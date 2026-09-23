<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessProfile;

class SetupBusinessProfileFees extends Command
{
    protected $signature = 'business-profiles:setup-fees 
                            {--dry-run : Show what would be done without making changes}
                            {--sample-data : Set up sample fee data}';

    protected $description = 'Set up fee data in business profiles';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $sampleData = $this->option('sample-data');

        $this->info('=== Business Profile Fee Setup ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        $businessProfiles = BusinessProfile::all();

        if ($businessProfiles->isEmpty()) {
            $this->error('No business profiles found in the database.');
            return;
        }

        $this->info("Found {$businessProfiles->count()} business profiles.");

        if ($sampleData) {
            $this->setupSampleFees($businessProfiles, $dryRun);
        } else {
            $this->showCurrentFees($businessProfiles);
        }

        $this->newLine();
        $this->info('Fee setup completed!');
    }

    protected function setupSampleFees($businessProfiles, $dryRun)
    {
        $this->info('Setting up sample fee data...');

        $sampleFees = [
            'base_fee_amount' => 2.50, // Activation fee
            'charge_fee_config' => [
                'fixed_amount' => 1.00,
                'percentage' => 3.5
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
            $this->updateBusinessProfileFees($bp, $sampleFees, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function updateBusinessProfileFees(BusinessProfile $bp, array $fees, $dryRun)
    {
        try {
            if ($dryRun) {
                $this->line("Would update business profile '{$bp->name}' with sample fees");
                return;
            }

            $bp->update($fees);
            $this->info("✓ Updated business profile '{$bp->name}' with sample fees");

        } catch (\Exception $e) {
            $this->error("✗ Error updating business profile '{$bp->name}': " . $e->getMessage());
        }
    }

    protected function showCurrentFees($businessProfiles)
    {
        $this->info('Current fee configuration:');
        $this->newLine();

        $headers = ['ID', 'Name', 'Base Fee', 'Charge Fee Config', 'Admin Fees', 'Integrator Fees', 'Partner Fees'];
        $rows = [];

        foreach ($businessProfiles as $bp) {
            $chargeFeeConfig = $bp->charge_fee_config ? json_encode($bp->charge_fee_config) : 'NULL';
            $adminFees = "€{$bp->admin_fee_fixed} + {$bp->admin_fee_percentage}%";
            $integratorFees = "€{$bp->integrator_fee_fixed} + {$bp->integrator_fee_percentage}%";
            $partnerFees = "€{$bp->partner_fee_fixed} + {$bp->partner_fee_percentage}%";

            $rows[] = [
                $bp->id,
                $bp->name,
                "€{$bp->base_fee_amount}",
                $chargeFeeConfig,
                $adminFees,
                $integratorFees,
                $partnerFees
            ];
        }

        $this->table($headers, $rows);
    }
}
