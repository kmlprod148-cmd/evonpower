<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;

class LinkChargingPointsToBusinessProfiles extends Command
{
    protected $signature = 'charging-points:link-to-business-profiles 
                            {--dry-run : Show what would be done without making changes}
                            {--auto-assign : Automatically assign charging points to business profiles}';

    protected $description = 'Link charging points to business profiles';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $autoAssign = $this->option('auto-assign');

        $this->info('=== Charging Points to Business Profiles Linker ===');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        $chargingPoints = ChargingPoint::all();
        $businessProfiles = BusinessProfile::all();

        if ($chargingPoints->isEmpty()) {
            $this->error('No charging points found in the database.');
            return;
        }

        if ($businessProfiles->isEmpty()) {
            $this->error('No business profiles found in the database.');
            return;
        }

        $this->info("Found {$chargingPoints->count()} charging points and {$businessProfiles->count()} business profiles.");

        if ($autoAssign) {
            $this->autoAssignChargingPoints($chargingPoints, $businessProfiles, $dryRun);
        } else {
            $this->showCurrentStatus($chargingPoints, $businessProfiles);
        }

        $this->newLine();
        $this->info('Linking completed!');
    }

    protected function autoAssignChargingPoints($chargingPoints, $businessProfiles, $dryRun)
    {
        $this->info('Auto-assigning charging points to business profiles...');

        $bar = $this->output->createProgressBar($chargingPoints->count());
        $bar->start();

        $businessProfileIndex = 0;
        $totalBusinessProfiles = $businessProfiles->count();

        foreach ($chargingPoints as $cp) {
            // Skip if already has a business profile
            if ($cp->business_profile_id) {
                $bar->advance();
                continue;
            }

            // Assign to next business profile in rotation
            $businessProfile = $businessProfiles[$businessProfileIndex % $totalBusinessProfiles];
            
            $this->assignChargingPointToBusinessProfile($cp, $businessProfile, $dryRun);
            
            $businessProfileIndex++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function assignChargingPointToBusinessProfile(ChargingPoint $cp, BusinessProfile $bp, $dryRun)
    {
        try {
            if ($dryRun) {
                $this->line("Would assign charging point '{$cp->name}' to business profile '{$bp->name}'");
                return;
            }

            $cp->update(['business_profile_id' => $bp->id]);
            $this->info("✓ Assigned charging point '{$cp->name}' to business profile '{$bp->name}'");

        } catch (\Exception $e) {
            $this->error("✗ Error assigning charging point '{$cp->name}': " . $e->getMessage());
        }
    }

    protected function showCurrentStatus($chargingPoints, $businessProfiles)
    {
        $this->info('Current linking status:');
        $this->newLine();

        $headers = ['Charging Point ID', 'Name', 'Business Profile ID', 'Business Profile Name', 'Has Fees'];
        $rows = [];

        foreach ($chargingPoints as $cp) {
            $bpName = 'NOT ASSIGNED';
            $hasFees = 'NO';

            if ($cp->business_profile_id) {
                $bp = $businessProfiles->find($cp->business_profile_id);
                if ($bp) {
                    $bpName = $bp->name;
                    $hasFees = ($bp->base_fee_amount > 0 || $bp->charge_fee_config) ? 'YES' : 'NO';
                }
            }

            $rows[] = [
                $cp->id,
                $cp->name,
                $cp->business_profile_id ?? 'NULL',
                $bpName,
                $hasFees
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Available business profiles:');
        $this->newLine();

        $bpHeaders = ['ID', 'Name', 'Base Fee', 'Charge Fee Config'];
        $bpRows = [];

        foreach ($businessProfiles as $bp) {
            $chargeFeeConfig = $bp->charge_fee_config ? json_encode($bp->charge_fee_config) : 'NULL';
            
            $bpRows[] = [
                $bp->id,
                $bp->name,
                "€{$bp->base_fee_amount}",
                $chargeFeeConfig
            ];
        }

        $this->table($bpHeaders, $bpRows);
    }
}
