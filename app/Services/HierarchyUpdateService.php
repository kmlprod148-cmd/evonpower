<?php

namespace App\Services;

use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\BusinessProfile;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HierarchyUpdateService
{
    /**
     * Update all child elements when a creator changes
     */
    public static function updateCreatorCascade($oldCreatorId, $newCreatorId, $creatorType = null)
    {
        try {
            DB::beginTransaction();

            $updates = [];

            // Update integrators created by the old creator
            $integrators = Integrator::where('created_by', $oldCreatorId)->get();
            foreach ($integrators as $integrator) {
                $integrator->update([
                    'created_by' => $newCreatorId,
                    'created_by_type' => $creatorType,
                    'created_by_id' => $newCreatorId
                ]);
                $updates['integrators'] = ($updates['integrators'] ?? 0) + 1;
            }

            // Update users (operators, partners) created by the old creator
            $users = User::where('created_by', $oldCreatorId)->get();
            foreach ($users as $user) {
                $user->update(['created_by' => $newCreatorId]);
                $updates['users'] = ($updates['users'] ?? 0) + 1;
            }

            // Update partners created by the old creator
            $partners = Partner::where('created_by', $oldCreatorId)->get();
            foreach ($partners as $partner) {
                $partner->update(['created_by' => $newCreatorId]);
                $updates['partners'] = ($updates['partners'] ?? 0) + 1;
            }

            // Update business profiles created by the old creator
            $businessProfiles = BusinessProfile::where('created_by', $oldCreatorId)->get();
            foreach ($businessProfiles as $bp) {
                $bp->update(['created_by' => $newCreatorId]);
                $updates['business_profiles'] = ($updates['business_profiles'] ?? 0) + 1;
            }

            // Update pricing plans created by the old creator
            $pricingPlans = PricingPlan::where('created_by', $oldCreatorId)->get();
            foreach ($pricingPlans as $plan) {
                $plan->update([
                    'created_by' => $newCreatorId,
                    'created_by_type' => $creatorType,
                    'created_by_id' => $newCreatorId
                ]);
                $updates['pricing_plans'] = ($updates['pricing_plans'] ?? 0) + 1;
            }

            // Update groups created by the old creator
            $groups = Group::where('created_by', $oldCreatorId)->get();
            foreach ($groups as $group) {
                $group->update([
                    'created_by' => $newCreatorId,
                    'created_by_type' => $creatorType,
                    'created_by_id' => $newCreatorId
                ]);
                $updates['groups'] = ($updates['groups'] ?? 0) + 1;
            }

            // Update charging points created by the old creator
            $chargingPoints = ChargingPoint::where('created_by', $oldCreatorId)->get();
            foreach ($chargingPoints as $cp) {
                $cp->update([
                    'created_by' => $newCreatorId,
                    'created_by_type' => $creatorType,
                    'created_by_id' => $newCreatorId
                ]);
                $updates['charging_points'] = ($updates['charging_points'] ?? 0) + 1;
            }

            DB::commit();

            Log::info('Hierarchy cascade update completed', [
                'old_creator_id' => $oldCreatorId,
                'new_creator_id' => $newCreatorId,
                'updates' => $updates
            ]);

            return $updates;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Hierarchy cascade update failed', [
                'old_creator_id' => $oldCreatorId,
                'new_creator_id' => $newCreatorId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get hierarchy statistics for a user
     */
    public static function getHierarchyStats($userId)
    {
        return [
            'integrators' => Integrator::where('created_by', $userId)->count(),
            'users' => User::where('created_by', $userId)->count(),
            'partners' => Partner::where('created_by', $userId)->count(),
            'business_profiles' => BusinessProfile::where('created_by', $userId)->count(),
            'pricing_plans' => PricingPlan::where('created_by', $userId)->count(),
            'groups' => Group::where('created_by', $userId)->count(),
            'charging_points' => ChargingPoint::where('created_by', $userId)->count(),
        ];
    }

    /**
     * Validate hierarchy integrity
     */
    public static function validateHierarchyIntegrity()
    {
        $issues = [];

        // Check for orphaned records
        $orphanedIntegrators = Integrator::whereNotNull('created_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'integrators.created_by');
            })->count();

        if ($orphanedIntegrators > 0) {
            $issues[] = "Found {$orphanedIntegrators} orphaned integrators";
        }

        $orphanedUsers = User::whereNotNull('created_by')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('users as creators')
                    ->whereColumn('creators.id', 'users.created_by');
            })->count();

        if ($orphanedUsers > 0) {
            $issues[] = "Found {$orphanedUsers} orphaned users";
        }

        return $issues;
    }
}
