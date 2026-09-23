<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixBusinessProfileAssignment extends Command
{
    protected $signature = 'business-profile:fix-assignment';
    protected $description = 'Fix business profile assignment to operators by their creator integrator';

    public function handle()
    {
        $this->info('🔧 Fixing business profile assignment to operators...');

        try {
            DB::beginTransaction();

            // 1. Get all operators
            $operators = User::role('operator')->with(['partner', 'integrator'])->get();
            $this->info("Found {$operators->count()} operators to process");

            $fixedCount = 0;
            $errors = [];

            foreach ($operators as $operator) {
                $this->line("Processing operator: {$operator->name} (ID: {$operator->id})");

                try {
                    // Determine the correct business profile based on creator
                    $businessProfile = $this->getCorrectBusinessProfile($operator);
                    
                    if (!$businessProfile) {
                        $this->warn("  ⚠️  No suitable business profile found for operator {$operator->name}");
                        continue;
                    }

                    // Apply the business profile to the operator's partner
                    if ($operator->partner_id) {
                        $partner = Partner::find($operator->partner_id);
                        if ($partner) {
                            $oldProfileId = $partner->business_profile_id;
                            $partner->business_profile_id = $businessProfile->id;
                            $partner->save();

                            $this->line("  ✅ Applied business profile: {$businessProfile->name} (ID: {$businessProfile->id})");
                            if ($oldProfileId && $oldProfileId != $businessProfile->id) {
                                $this->line("  📝 Changed from profile ID: {$oldProfileId}");
                            }
                            
                            $fixedCount++;
                        } else {
                            $this->warn("  ⚠️  Partner not found for operator {$operator->name}");
                        }
                    } else {
                        $this->warn("  ⚠️  No partner assigned to operator {$operator->name}");
                    }

                } catch (\Exception $e) {
                    $error = "Error processing operator {$operator->name}: " . $e->getMessage();
                    $errors[] = $error;
                    $this->error("  ❌ {$error}");
                }
            }

            DB::commit();

            $this->info("🎉 Business profile assignment fix completed!");
            $this->info("✅ Fixed {$fixedCount} operators");
            
            if (!empty($errors)) {
                $this->warn("⚠️  {$errors} errors occurred:");
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
            }

            // Show summary
            $this->showSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Transaction failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Get the correct business profile for an operator based on their creator
     */
    private function getCorrectBusinessProfile(User $operator): ?BusinessProfile
    {
        // If operator has an integrator_id, use integrator's business profile or integrator-created profiles
        if ($operator->integrator_id) {
            $integrator = Integrator::find($operator->integrator_id);
            if ($integrator) {
                // First, try to get the integrator's own business profile
                if ($integrator->business_profile_id) {
                    $integratorProfile = BusinessProfile::find($integrator->business_profile_id);
                    if ($integratorProfile && $integratorProfile->is_active) {
                        return $integratorProfile;
                    }
                }

                // If no integrator profile, get business profiles created by the integrator
                $integratorUser = User::find($integrator->user_id);
                if ($integratorUser) {
                    $integratorCreatedProfiles = BusinessProfile::where('created_by_type', get_class($integratorUser))
                        ->where('created_by_id', $integratorUser->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($integratorCreatedProfiles) {
                        return $integratorCreatedProfiles;
                    }
                }

                // Fallback: get admin-created profiles that integrators can use
                $adminProfiles = BusinessProfile::whereHas('creator', function($query) {
                    $query->whereHas('roles', function($roleQuery) {
                        $roleQuery->where('name', 'admin');
                    });
                })
                ->where('is_active', true)
                ->where('is_public', true)
                ->first();

                if ($adminProfiles) {
                    return $adminProfiles;
                }
            }
        }

        // If no integrator or integrator has no profile, use admin-created profiles
        $adminProfiles = BusinessProfile::whereHas('creator', function($query) {
            $query->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'admin');
            });
        })
        ->where('is_active', true)
        ->first();

        return $adminProfiles;
    }

    /**
     * Show summary of business profile assignments
     */
    private function showSummary()
    {
        $this->info("\n📊 Summary of Business Profile Assignments:");
        
        $operators = User::role('operator')->with(['partner.businessProfile'])->get();
        
        $profileCounts = [];
        $integratorCounts = [];
        
        foreach ($operators as $operator) {
            if ($operator->partner && $operator->partner->businessProfile) {
                $profileName = $operator->partner->businessProfile->name;
                $profileCounts[$profileName] = ($profileCounts[$profileName] ?? 0) + 1;
                
                if ($operator->integrator_id) {
                    $integrator = Integrator::find($operator->integrator_id);
                    if ($integrator) {
                        $integratorName = $integrator->name;
                        $integratorCounts[$integratorName] = ($integratorCounts[$integratorName] ?? 0) + 1;
                    }
                }
            }
        }
        
        $this->line("\n📋 Business Profiles in use:");
        foreach ($profileCounts as $profileName => $count) {
            $this->line("  - {$profileName}: {$count} operators");
        }
        
        $this->line("\n👥 Integrators with operators:");
        foreach ($integratorCounts as $integratorName => $count) {
            $this->line("  - {$integratorName}: {$count} operators");
        }
    }
}
