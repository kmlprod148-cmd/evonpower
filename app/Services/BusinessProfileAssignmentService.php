<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BusinessProfileAssignmentService
{
    /**
     * Assign the correct business profile to an operator based on their creator
     */
    public function assignBusinessProfileToOperator(User $operator, ?User $creator = null): ?BusinessProfile
    {
        try {
            $businessProfile = $this->determineCorrectBusinessProfile($operator, $creator);
            
            if (!$businessProfile) {
                Log::warning("No suitable business profile found for operator", [
                    'operator_id' => $operator->id,
                    'operator_name' => $operator->name,
                    'creator_id' => $creator ? $creator->id : null
                ]);
                return null;
            }

            // Apply to operator's partner
            if ($operator->partner_id) {
                $partner = Partner::find($operator->partner_id);
                if ($partner) {
                    $oldProfileId = $partner->business_profile_id;
                    $partner->business_profile_id = $businessProfile->id;
                    $partner->save();

                    Log::info("Business profile assigned to operator", [
                        'operator_id' => $operator->id,
                        'operator_name' => $operator->name,
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name,
                        'old_profile_id' => $oldProfileId,
                        'creator_id' => $creator ? $creator->id : null
                    ]);

                    return $businessProfile;
                }
            }

            Log::warning("No partner found for operator", [
                'operator_id' => $operator->id,
                'operator_name' => $operator->name
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Error assigning business profile to operator", [
                'operator_id' => $operator->id,
                'operator_name' => $operator->name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Determine the correct business profile for an operator
     */
    private function determineCorrectBusinessProfile(User $operator, ?User $creator = null): ?BusinessProfile
    {
        // Priority 1: If operator has integrator_id, use integrator's business profile
        if ($operator->integrator_id) {
            $integrator = Integrator::find($operator->integrator_id);
            if ($integrator) {
                // Try integrator's own business profile first
                if ($integrator->business_profile_id) {
                    $integratorProfile = BusinessProfile::find($integrator->business_profile_id);
                    if ($integratorProfile && $integratorProfile->is_active) {
                        return $integratorProfile;
                    }
                }

                // Try business profiles created by the integrator
                $integratorUser = User::find($integrator->user_id);
                if ($integratorUser) {
                    $integratorCreatedProfile = BusinessProfile::where('created_by_type', get_class($integratorUser))
                        ->where('created_by_id', $integratorUser->id)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($integratorCreatedProfile) {
                        return $integratorCreatedProfile;
                    }
                }
            }
        }

        // Priority 2: If creator is an integrator, use their business profile
        if ($creator && $creator->hasRole('integrator')) {
            $integrator = $creator->integrator;
            if ($integrator && $integrator->business_profile_id) {
                $integratorProfile = BusinessProfile::find($integrator->business_profile_id);
                if ($integratorProfile && $integratorProfile->is_active) {
                    return $integratorProfile;
                }
            }

            // Try business profiles created by the integrator
            $integratorCreatedProfile = BusinessProfile::where('created_by_type', get_class($creator))
                ->where('created_by_id', $creator->id)
                ->where('is_active', true)
                ->first();
            
            if ($integratorCreatedProfile) {
                return $integratorCreatedProfile;
            }
        }

        // Priority 3: Use admin-created business profiles
        $adminProfile = BusinessProfile::whereHas('creator', function($query) {
            $query->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'admin');
            });
        })
        ->where('is_active', true)
        ->orderBy('created_at', 'desc')
        ->first();

        return $adminProfile;
    }

    /**
     * Fix all business profile assignments for existing operators
     */
    public function fixAllBusinessProfileAssignments(): array
    {
        $results = [
            'success' => 0,
            'errors' => [],
            'skipped' => 0
        ];

        try {
            DB::beginTransaction();

            $operators = User::role('operator')->with(['partner', 'integrator'])->get();
            
            foreach ($operators as $operator) {
                try {
                    // Get the creator of the operator
                    $creator = null;
                    if ($operator->created_by) {
                        $creator = User::find($operator->created_by);
                    }

                    $businessProfile = $this->assignBusinessProfileToOperator($operator, $creator);
                    
                    if ($businessProfile) {
                        $results['success']++;
                    } else {
                        $results['skipped']++;
                    }

                } catch (\Exception $e) {
                    $error = "Error processing operator {$operator->name}: " . $e->getMessage();
                    $results['errors'][] = $error;
                    Log::error($error);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Transaction failed: " . $e->getMessage();
            Log::error("Business profile assignment transaction failed", [
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Get business profiles available for an integrator to assign to operators
     */
    public function getAvailableBusinessProfilesForIntegrator(User $integrator): \Illuminate\Support\Collection
    {
        $profiles = collect();

        // 1. Business profiles created by the integrator
        $integratorProfiles = BusinessProfile::where('created_by_type', get_class($integrator))
            ->where('created_by_id', $integrator->id)
            ->where('is_active', true)
            ->get();
        
        $profiles = $profiles->merge($integratorProfiles);

        // 2. Business profiles created by admin (public or available to integrators)
        $adminProfiles = BusinessProfile::whereHas('creator', function($query) {
            $query->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'admin');
            });
        })
        ->where('is_active', true)
        ->get();
        
        $profiles = $profiles->merge($adminProfiles);

        // 3. Integrator's own business profile if it exists
        if ($integrator->integrator_id) {
            $integratorModel = Integrator::find($integrator->integrator_id);
            if ($integratorModel && $integratorModel->business_profile_id) {
                $integratorProfile = BusinessProfile::find($integratorModel->business_profile_id);
                if ($integratorProfile && $integratorProfile->is_active) {
                    $profiles->push($integratorProfile);
                }
            }
        }

        return $profiles->unique('id');
    }

    /**
     * Validate business profile assignment
     */
    public function validateBusinessProfileAssignment(User $operator, BusinessProfile $businessProfile, ?User $integrator = null): bool
    {
        // Check if operator has a partner
        if (!$operator->partner_id) {
            return false;
        }

        // Check if business profile is active
        if (!$businessProfile->is_active) {
            return false;
        }

        // If integrator is provided, check if they can use this business profile
        if ($integrator) {
            return $this->canIntegratorUseBusinessProfile($businessProfile, $integrator);
        }

        return true;
    }

    /**
     * Check if integrator can use a business profile
     */
    private function canIntegratorUseBusinessProfile(BusinessProfile $businessProfile, User $integrator): bool
    {
        // Integrator can use their own business profiles
        if ($businessProfile->created_by_type === get_class($integrator) && 
            $businessProfile->created_by_id === $integrator->id) {
            return true;
        }

        // Integrator can use public business profiles created by admin
        if ($businessProfile->is_public && 
            $businessProfile->created_by_type === 'App\Models\User' &&
            $businessProfile->creator && 
            $businessProfile->creator->hasRole('admin')) {
            return true;
        }

        // Integrator can use business profiles created by their admin creator
        $adminCreator = $this->getAdminCreator($integrator);
        if ($adminCreator && 
            $businessProfile->created_by_type === get_class($adminCreator) && 
            $businessProfile->created_by_id === $adminCreator->id) {
            return true;
        }

        // NOUVEAU: Integrator can use any public business profile
        if ($businessProfile->is_public) {
            return true;
        }

        // NOUVEAU: Integrator can use any business profile if they have the permission
        if ($integrator->can('assign_business_profiles')) {
            return true;
        }

        return false;
    }

    /**
     * Get the admin creator of an integrator
     */
    private function getAdminCreator(User $integrator): ?User
    {
        $currentUser = $integrator;
        $maxDepth = 10;
        $depth = 0;

        while ($currentUser && $depth < $maxDepth) {
            if ($currentUser->hasRole('admin')) {
                return $currentUser;
            }
            
            if ($currentUser->created_by) {
                $currentUser = User::find($currentUser->created_by);
                $depth++;
            } else {
                break;
            }
        }

        return null;
    }
}
