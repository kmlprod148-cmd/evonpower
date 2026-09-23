<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Partner;
use App\Traits\HasCreator;

class Group extends Model
{
    use HasFactory, HasRoles, HasCreator;

    /**
     * Boot method to add model events and validation
     */
    protected static function boot()
    {
        parent::boot();
        
        // Validate that group belongs to a partner
        static::saving(function ($group) {
            if ($group->partner_id) {
                $partner = Partner::find($group->partner_id);
                if (!$partner) {
                    throw new \Exception('Group must belong to a valid partner.');
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'description',
        'type',
        'consumption_mode',
        'city',
        'user_id',
        'integrator_id',
        'partner_id',
        'created_by', 'created_by_type', 'created_by_id', // Champs de traçabilité
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the group
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the integrator that owns the group
     */
    public function integrator(): BelongsTo
    {
        return $this->belongsTo(Integrator::class);
    }

    /**
     * Get the partner that owns the group
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Get the operator for this group
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Get the user that created this group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the charging stations for the group
     */
    public function chargingStations(): HasMany
    {
        return $this->hasMany(ChargingStation::class, 'group_id');
    }

    /**
     * Get the charging points for the group (alias)
     */
    public function chargingPoints(): HasMany
    {
        return $this->hasMany(ChargingPoint::class, 'group_id');
    }

    /**
     * Obtenir les stations du groupe.
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'group_id');
    }

    /**
     * Scope: filter groups visible to the given user based on their role.
     * Admins see all. Partners see only their partner's groups.
     * Integrators see only their integrator's groups.
     */
    public function scopeForUser(Builder $query, \App\Models\User $user): Builder
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return $query;
        }

        if ($user->hasRole('partner')) {
            $partnerId = $user->partner_id
                ?? optional($user->partner)->id
                ?? 0;
            return $query->where('partner_id', $partnerId);
        }

        if ($user->hasRole('integrator') && $user->integrator_id) {
            return $query->where('integrator_id', $user->integrator_id);
        }

        // Operator: see groups belonging to their partner
        if ($user->hasRole('operator')) {
            $partnerId = $user->partner_id
                ?? optional($user->partner)->id
                ?? 0;
            return $query->where('partner_id', $partnerId);
        }

        // Fallback: only groups directly owned by this user
        return $query->where('user_id', $user->id);
    }

    /**
     * Obtenir les plans tarifaires du groupe.
     */
    public function pricingPlans()
    {
        return $this->belongsToMany(PricingPlan::class, 'group_plan', 'group_id', 'plan_id');
    }

    /**
     * Obtenir le plan tarifaire principal du groupe (le premier actif).
     */
    public function pricingPlan()
    {
        return $this->pricingPlans()->where('is_active', true)->first();
    }

}

