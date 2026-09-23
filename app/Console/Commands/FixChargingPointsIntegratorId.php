<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixChargingPointsIntegratorId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charging-points:fix-integrator-id {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix charging points with null integrator_id by assigning the integrator_id of their creator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->info('🔧 FIXING CHARGING POINTS - This will update the database');
        }
        
        // Find all charging points with null integrator_id
        $chargingPoints = ChargingPoint::whereNull('integrator_id')
            ->with(['user', 'group', 'partner'])
            ->get();
        
        $this->info("Found {$chargingPoints->count()} charging points with null integrator_id");
        
        $fixed = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($chargingPoints as $chargingPoint) {
            $integratorId = null;
            $source = null;
            
            // Try to find integrator_id from multiple sources
            // 1. From user (if user is integrator or operator with integrator_id)
            if ($chargingPoint->user_id && $chargingPoint->user) {
                $user = $chargingPoint->user;
                if ($user->integrator_id) {
                    $integratorId = $user->integrator_id;
                    $source = 'user (operator/integrator)';
                }
            }
            
            // 2. From group
            if (!$integratorId && $chargingPoint->group_id && $chargingPoint->group) {
                $group = $chargingPoint->group;
                if ($group->integrator_id) {
                    $integratorId = $group->integrator_id;
                    $source = 'group';
                } elseif ($group->partner_id && $group->partner && $group->partner->integrator_id) {
                    $integratorId = $group->partner->integrator_id;
                    $source = 'group->partner';
                }
            }
            
            // 3. From partner
            if (!$integratorId && $chargingPoint->partner_id && $chargingPoint->partner) {
                $partner = $chargingPoint->partner;
                if ($partner->integrator_id) {
                    $integratorId = $partner->integrator_id;
                    $source = 'partner';
                }
            }
            
            if ($integratorId) {
                if ($dryRun) {
                    $this->line("  ✅ Would fix: Charging point #{$chargingPoint->id} '{$chargingPoint->name}' -> integrator_id = {$integratorId} (from {$source})");
                    $fixed++;
                } else {
                    try {
                        $chargingPoint->integrator_id = $integratorId;
                        $chargingPoint->save();
                        
                        $this->line("  ✅ Fixed: Charging point #{$chargingPoint->id} '{$chargingPoint->name}' -> integrator_id = {$integratorId} (from {$source})");
                        $fixed++;
                        
                        Log::info('Fixed charging point integrator_id', [
                            'charging_point_id' => $chargingPoint->id,
                            'charging_point_name' => $chargingPoint->name,
                            'integrator_id' => $integratorId,
                            'source' => $source
                        ]);
                    } catch (\Exception $e) {
                        $this->error("  ❌ Error fixing charging point #{$chargingPoint->id}: {$e->getMessage()}");
                        $errors++;
                    }
                }
            } else {
                $this->line("  ⏭️  Skipped: Charging point #{$chargingPoint->id} '{$chargingPoint->name}' - no integrator_id found in user/group/partner");
                $skipped++;
            }
        }
        
        $this->newLine();
        if ($dryRun) {
            $this->info("📊 Summary (DRY RUN):");
            $this->info("  Would fix: {$fixed}");
            $this->info("  Would skip: {$skipped}");
            $this->info("  Total checked: {$chargingPoints->count()}");
            $this->newLine();
            $this->info("Run without --dry-run to apply changes");
        } else {
            $this->info("📊 Summary:");
            $this->info("  ✅ Fixed: {$fixed}");
            $this->info("  ⏭️  Skipped: {$skipped}");
            if ($errors > 0) {
                $this->error("  ❌ Errors: {$errors}");
            }
            $this->info("  Total checked: {$chargingPoints->count()}");
        }
        
        return Command::SUCCESS;
    }
}
