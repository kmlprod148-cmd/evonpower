<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Station;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\SubscriptionUsageLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionPlanService
{
    /**
     * Get all subscription plans with filters and pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SubscriptionPlan::query();

        // Filter by active status
        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by featured
        if (isset($filters['featured'])) {
            $query->where('is_featured', $filters['featured']);
        }

        // Search by name or description
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'sort_order';
        $orderDir = $filters['order_dir'] ?? 'asc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get a single subscription plan by ID
     */
    public function getById(int $id): ?SubscriptionPlan
    {
        return SubscriptionPlan::with([
            'vatRate',
            'groups',
            'chargingPoints',
            'stations',
        ])->find($id);
    }

    /**
     * Create a new subscription plan
     */
    public function create(array $data): SubscriptionPlan
    {
        // Calculate VAT amount
        $vatRate = $data['vat_rate'] ?? 0;
        $price = $data['price'];
        $vatAmount = $price * ($vatRate / 100);

        // Set duration months based on type if not provided
        if (!isset($data['duration_months']) || $data['duration_months'] === 0) {
            $data['duration_months'] = $this->getDurationFromType($data['type']);
        }

        $plan = SubscriptionPlan::create($data);

        // Assign groups if provided
        if (!empty($data['group_ids'])) {
            $plan->groups()->attach($data['group_ids'], ['is_active' => true]);
        }

        // Assign charging points if provided
        if (!empty($data['charging_point_ids'])) {
            $plan->chargingPoints()->attach($data['charging_point_ids'], ['is_active' => true]);
        }

        // Assign stations if provided
        if (!empty($data['station_ids'])) {
            $plan->stations()->attach($data['station_ids'], ['is_active' => true]);
        }

        return $plan->fresh(['vatRate', 'groups', 'chargingPoints', 'stations']);
    }

    /**
     * Update an existing subscription plan
     */
    public function update(int $id, array $data): ?SubscriptionPlan
    {
        $plan = SubscriptionPlan::find($id);
        if (!$plan) {
            return null;
        }

        // Update duration months based on type if not provided
        if (!isset($data['duration_months']) || $data['duration_months'] === 0) {
            $data['duration_months'] = $this->getDurationFromType($data['type'] ?? $plan->type);
        }

        $plan->update($data);

        // Sync groups if provided
        if (isset($data['group_ids'])) {
            $plan->groups()->sync(array_fill_keys($data['group_ids'], ['is_active' => true]));
        }

        // Sync charging points if provided
        if (isset($data['charging_point_ids'])) {
            $plan->chargingPoints()->sync(array_fill_keys($data['charging_point_ids'], ['is_active' => true]));
        }

        // Sync stations if provided
        if (isset($data['station_ids'])) {
            $plan->stations()->sync(array_fill_keys($data['station_ids'], ['is_active' => true]));
        }

        return $plan->fresh(['vatRate', 'groups', 'chargingPoints', 'stations']);
    }

    /**
     * Delete a subscription plan
     */
    public function delete(int $id): bool
    {
        $plan = SubscriptionPlan::find($id);
        if (!$plan) {
            return false;
        }

        // Check if there are active subscriptions
        if ($plan->userSubscriptions()->active()->count() > 0) {
            // Soft delete instead
            $plan->delete();
            return true;
        }

        $plan->groups()->detach();
        $plan->chargingPoints()->detach();
        $plan->stations()->detach();
        $plan->forceDelete();

        return true;
    }

    /**
     * Assign a plan to groups
     */
    public function assignToGroups(int $planId, array $groupIds): bool
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan) {
            return false;
        }

        $plan->groups()->sync(array_fill_keys($groupIds, ['is_active' => true]));
        return true;
    }

    /**
     * Assign a plan to charging points
     */
    public function assignToChargingPoints(int $planId, array $chargingPointIds): bool
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan) {
            return false;
        }

        $plan->chargingPoints()->sync(array_fill_keys($chargingPointIds, ['is_active' => true]));
        return true;
    }

    /**
     * Assign a plan to stations
     */
    public function assignToStations(int $planId, array $stationIds): bool
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan) {
            return false;
        }

        $plan->stations()->sync(array_fill_keys($stationIds, ['is_active' => true]));
        return true;
    }

    /**
     * Check if a plan is accessible for a user at a specific location
     */
    public function isAccessibleForUser(
        SubscriptionPlan $plan,
        User $user,
        ?ChargingPoint $chargingPoint = null,
        ?Station $station = null
    ): bool {
        // Check if plan is active
        if (!$plan->is_active) {
            return false;
        }

        // If user has a group, check group access
        if ($user->group_id) {
            $group = Group::find($user->group_id);
            if ($group && !$plan->isAccessibleByGroup($group)) {
                return false;
            }
        }

        // Check charging point access
        if ($chargingPoint) {
            if (!$plan->isAccessibleAtChargingPoint($chargingPoint)) {
                return false;
            }
        }

        // Check station access
        if ($station) {
            if (!$plan->isAccessibleAtStation($station)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get plans accessible for a user
     */
    public function getAccessiblePlansForUser(User $user): Collection
    {
        $query = SubscriptionPlan::active()
            ->ordered()
            ->with(['vatRate']);

        // If user has a group, filter by group access
        if ($user->group_id) {
            $query->whereHas('groups', function ($q) use ($user) {
                $q->where('groups.id', $user->group_id)
                    ->wherePivot('is_active', true);
            });
        }

        return $query->get();
    }

    /**
     * Get duration in months from plan type
     */
    private function getDurationFromType(string $type): int
    {
        return match ($type) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 0,
        };
    }

    /**
     * Subscribe a user to a plan
     */
    public function subscribeUser(int $planId, User $user, array $paymentData = []): ?UserSubscription
    {
        $plan = SubscriptionPlan::find($planId);
        if (!$plan || !$plan->is_active) {
            return null;
        }

        // Calculate dates
        $startDate = now()->toDateString();
        $duration = $plan->getDurationInMonths();
        $endDate = $duration > 0 
            ? now()->addMonths($duration)->toDateString() 
            : now()->addYear()->toDateString(); // Default to 1 year for quota-based plans

        // Calculate amounts
        $vatRate = $plan->vat_rate ?? 0;
        $price = $plan->price;
        $vatAmount = $price * ($vatRate / 100);
        $totalAmount = $price + $vatAmount;

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'sessions_used' => 0,
            'kwh_used' => 0,
            'duration_minutes_used' => 0,
            'amount_paid' => $totalAmount,
            'vat_amount' => $vatAmount,
            'currency' => $paymentData['currency'] ?? 'EUR',
            'payment_method' => $paymentData['method'] ?? null,
            'payment_reference' => $paymentData['reference'] ?? null,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'auto_renew' => $paymentData['auto_renew'] ?? $plan->allow_renewal,
        ]);

        return $subscription;
    }

    /**
     * Decrement quota usage for a subscription
     */
    public function decrementQuota(
        UserSubscription $subscription,
        array $usage,
        ?int $transactionId = null,
        ?int $reservationId = null,
        ?int $chargingSessionId = null
    ): SubscriptionUsageLog {
        $plan = $subscription->subscriptionPlan;
        
        // Store values before update
        $sessionsBefore = $subscription->sessions_used;
        $kwhBefore = (float) $subscription->kwh_used;
        $durationBefore = $subscription->duration_minutes_used;

        // Update usage based on plan type
        if ($plan->max_sessions !== null && isset($usage['sessions'])) {
            $subscription->increment('sessions_used', $usage['sessions']);
        }

        if ($plan->max_kwh !== null && isset($usage['kwh'])) {
            $subscription->increment('kwh_used', $usage['kwh']);
        }

        if ($plan->max_duration_minutes !== null && isset($usage['duration'])) {
            $subscription->increment('duration_minutes_used', $usage['duration']);
        }

        // Reload to get new values
        $subscription->refresh();

        // Create usage log
        return SubscriptionUsageLog::create([
            'user_subscription_id' => $subscription->id,
            'transaction_id' => $transactionId,
            'reservation_id' => $reservationId,
            'charging_session_id' => $chargingSessionId,
            'usage_type' => $this->getUsageType($plan),
            'sessions_consumed' => $usage['sessions'] ?? 0,
            'kwh_consumed' => $usage['kwh'] ?? 0,
            'duration_minutes_consumed' => $usage['duration'] ?? 0,
            'sessions_before' => $sessionsBefore,
            'sessions_after' => $subscription->sessions_used,
            'kwh_before' => $kwhBefore,
            'kwh_after' => (float) $subscription->kwh_used,
            'duration_before' => $durationBefore,
            'duration_after' => $subscription->duration_minutes_used,
        ]);
    }

    /**
     * Get usage type based on plan type
     */
    private function getUsageType(SubscriptionPlan $plan): string
    {
        return match ($plan->type) {
            'per_charge' => 'session',
            'per_kwh' => 'kwh',
            'per_time', 'per_session' => 'duration',
            default => 'session',
        };
    }
}
