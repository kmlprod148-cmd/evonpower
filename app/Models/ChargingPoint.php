<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable; // Add Auditable trait
use App\Traits\HasCreator;
use App\Models\Group;
use App\Services\BusinessProfileAutoLinkService;

class ChargingPoint extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use HasFactory, SoftDeletes, Auditable, HasCreator; // Use Auditable and HasCreator

    protected $fillable = [
        'name', 'serial_number', 'manufacturer', 'model', 'status', 'user_id',
        'integrator_id', 'partner_id', 'group_id', 'pricing_plan_id', 'station_id', 'business_profile_id',
        'max_duration', // Ajouté
        'charge_box_id', 'steve_server_url', 'websocket_url', 'last_connection_attempt', 'steve_connection_status',
        'location', 'connection_type', 'connector_type', 'power_output', 'authentication_required',
        'steve_charging_point_id', 'steve_charge_box_pk', 'steve_provisioned_at', 'status_updated_at', // Ajoutés pour l'intégration Steve API
        'address', 'city', 'postal_code', 'country', 'latitude', 'longitude',
        'firmware_version', 'communication_protocol', 'ip_address', 'mac_address',
        'last_maintenance_date', 'next_maintenance_date',
        'access_type', 'public_access', 'access_code', 'qr_code', 'description',
        'external_id', 'timezone', 'installation_notes', 'commission_rate',
        'contract_reference', 'evse_id', 'total_energy_delivered',
        'total_charging_sessions', 'last_connection', 'last_status_update',
        'last_used_at', 'notes', 'is_active',
        'created_by', 'created_by_type', 'created_by_id', // Champs de traçabilité
    ];

    protected $auditInclude = [
        'name', 'serial_number', 'manufacturer', 'model', 'status',
        'integrator_id', 'partner_id', 'group_id', 'pricing_plan_id', 'station_id',
        'address', 'city', 'postal_code', 'country', 'latitude', 'longitude',
        'firmware_version', 'communication_protocol', 'ip_address', 'mac_address',
        'power_output', 'last_maintenance_date', 'next_maintenance_date',
        'access_type', 'public_access', 'access_code', 'qr_code', 'description',
        'external_id', 'timezone', 'installation_notes', 'commission_rate',
        'contract_reference', 'evse_id', 'total_energy_delivered',
        'total_charging_sessions', 'last_connection', 'last_status_update',
        'last_used_at', 'notes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'public_access' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'max_duration' => 'integer', // Ajouté
        'last_connection_attempt' => 'datetime',
        'steve_connection_status' => 'array',
        'steve_charge_box_pk' => 'integer',
        'steve_provisioned_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the operator for this charging point
     * This relation points to the user of the charging point as the primary operator.
     * For more complex logic (fallback to group->operator or group->user),
     * use the getOperator() method instead.
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class, 'business_profile_id');
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function transactions()
    {
        // Assuming Transaction model exists and has charging_point_id
        return $this->hasMany(Transaction::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function chargingSessions()
    {
        // Assuming ChargingSession model exists and has charging_point_id
        return $this->hasMany(ChargingSession::class);
    }

    /**
     * Get the connectors for the charging point.
     */
    public function connectors()
    {
        return $this->hasMany(Connector::class);
    }

    public function businessProfileApplications()
    {
        return $this->morphMany(BusinessProfileApplication::class, 'applied_to');
    }

    /**
     * Relation avec les actions distantes
     */
    public function remoteActions()
    {
        return $this->hasMany(RemoteAction::class);
    }

    // Scopes
    // Removed scopeActive as is_active column does not exist
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopePublicAccess($query)
    {
        return $query->where('public_access', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByAccessType($query, $accessType)
    {
        return $query->where('access_type', $accessType);
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        $haversine = "(6371 * acos(cos(radians($latitude))
                    * cos(radians(latitude))
                    * cos(radians(longitude)
                    - radians($longitude))
                    + sin(radians($latitude))
                    * sin(radians(latitude))))";

        return $query->select('*')
                    ->selectRaw("$haversine AS distance")
                    ->whereRaw("$haversine < ?", [$radius])
                    ->orderBy('distance');
    }

    public function scopeVisibleToUser($query, User $user)
    {
        // Vérifier les rôles de manière insensible à la casse
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isAdmin = in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower);
        $isIntegrator = in_array('integrator', $userRolesLower);
        $isPartner = in_array('partner', $userRolesLower);
        $isOperator = in_array('operator', $userRolesLower);
        
        if ($isAdmin) {
            return $query; // Admin sees all
        }

        $integratorId = $user->integrator_id ?? \App\Models\Integrator::where('user_id', $user->id)->value('id');
        if ($isIntegrator && $integratorId) {
            // Integrators can view everything within their organizational subtree
            return $query->where(function ($q) use ($integratorId) {
                // Fetch all users associated with this integrator to cover depth
                $allUserIds = \App\Models\User::where('integrator_id', $integratorId)->pluck('id')->toArray();
                
                // Fetch all partners associated with this integrator
                $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)->pluck('id')->toArray();
                
                // 1. Direct integrator ownership
                $q->where('integrator_id', $integratorId)
                
                // 2. User ownership (including nested operators)
                  ->orWhereIn('user_id', $allUserIds)
                  
                // 3. Partner ownership
                  ->orWhereIn('partner_id', $partnerIds)
                  
                // 4. Group ownership via partner or integrator
                  ->orWhereHas('group', function ($g) use ($partnerIds, $integratorId) {
                        $g->whereIn('partner_id', $partnerIds)
                          ->orWhere('integrator_id', $integratorId);
                  })
                  
                // 5. Creation chain (regardless of depth)
                  ->orWhereIn('created_by', $allUserIds)
                  ->orWhereIn('created_by_id', $allUserIds);
            });
        }

        if ($isPartner && $user->partner_id) {
            // Partners see charging points: direct partner_id OR via group.partner_id
            return $query->where(function ($q) use ($user) {
                $q->where('partner_id', $user->partner_id)
                  ->orWhereHas('group', function ($groupQuery) use ($user) {
                      $groupQuery->where('partner_id', $user->partner_id);
                  });
            });
        }

        if ($isOperator) {
            // Operators see: their charging points OR groups they manage OR all charging points of their integrator
            $groupIds = \App\Models\Group::where('user_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $groupIds) {
                $q->where('created_by', $user->id)
                  ->orWhere('created_by_id', $user->id)
                  ->orWhere('user_id', $user->id);
                if (!empty($groupIds)) {
                    $q->orWhereIn('group_id', $groupIds);
                }
                // Opérateurs rattachés à un intégrateur voient TOUTES les bornes de cet intégrateur
                if ($user->integrator_id) {
                    $q->orWhere('integrator_id', $user->integrator_id);
                }
            });
        }

        // Clients avec view_public_charging_points : voient toutes les bornes disponibles pour réserver
        if ($user->can('view_public_charging_points')) {
            return $query;
        }

        return $query->whereNull('id'); // No access
    }

    // Accessors & Mutators
    public function getFormattedAddressAttribute()
    {
        return trim("{$this->address}, {$this->city} {$this->postal_code}");
    }

    public function getIsAvailableAttribute()
    {
        return $this->status === 'online';
    }

    /**
     * Check if the charging point is online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Check if the charging point has a Steve ID configured
     */
    public function hasSteveId(): bool
    {
        return !empty($this->steve_charging_point_id);
    }

    /**
     * True only when this CP has been confirmed provisioned by SteVe — i.e. SteVe
     * returned a chargeBoxPk on the create call. Distinct from hasSteveId(), which
     * is true even when only a local-format id ("CP-NNNNNN-serial") has been
     * assigned. Use this for any logic that should not touch SteVe unless the row
     * is known to exist server-side.
     */
    public function isProvisionedOnSteve(): bool
    {
        $steveStatus = (array) ($this->steve_connection_status ?? []);

        return $this->steve_charge_box_pk !== null
            && (
                $this->steve_provisioned_at !== null
                || ($this->steve_synced_at ?? null) !== null
                || ($steveStatus['provisioned'] ?? false)
            );
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'maintenance' => 'yellow',
            'error' => 'red',
            'charging' => 'blue', // Added from existing migration status enum
            'reserved' => 'orange', // Added from existing migration status enum
            default => 'gray'
        };
    }

    public function getMaxPowerAttribute()
    {
        return $this->power_output;
    }

    /**
     * Alias pour total_energy_delivered (utilisé par la vue show)
     */
    public function getEnergyDeliveredAttribute()
    {
        return $this->total_energy_delivered ?? 0;
    }

    // Methods
    public function canBeAccessedBy($user = null)
    {
        if ($this->public_access) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Assuming user has a hasRole method
        if ($user->hasRole('admin')) {
            return true;
        }

        // Assuming user has a canManage method
        return $user->canManage($this);
    }

    public function needsMaintenance()
    {
        if (!$this->next_maintenance_date) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->next_maintenance_date);
    }

    public function getActivePricingPlan()
    {
        return $this->pricingPlan()->with('vatRate')->first() ?: PricingPlan::where('is_active', true)
                                                 ->where('is_public', true)
                                                 ->with('vatRate')
                                                 ->orderBy('priority')
                                                 ->first();
    }

    public function generateQRCode()
    {
        // This will be implemented in the QR Code service
        // Using 'qr_code' column from migration
        return app('qr.service')->generateForChargingPoint($this);
    }

    public function hasQRCode()
    {
        // Using 'qr_code' column from migration
        return !empty($this->qr_code) && file_exists(storage_path('app/public/' . $this->qr_code));
    }

    public function getQRCodeUrl()
    {
        // Using 'qr_code' column from migration
        if ($this->hasQRCode()) {
            $url = asset('storage/' . $this->qr_code);
            // Force HTTPS scheme to avoid mixed-content errors
            if (Str::startsWith($url, 'http://')) {
                $url = preg_replace('/^http:/i', 'https:', $url);
            }
            return $url;
        }
        return null;
    }

    public function updateStatus($newStatus, $reason = null)
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        // Log status change - Assuming activity() helper exists
        activity()
            ->performedOn($this)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ])
            ->log('Status changed');

        return $this;
    }

    /**
     * Auto-link business profile for this charging point
     */
    public function autoLinkBusinessProfile(): BusinessProfile
    {
        return BusinessProfileAutoLinkService::autoLinkBusinessProfile($this);
    }

    /**
     * Get available business profiles for this charging point
     */
    public function getAvailableBusinessProfiles(): array
    {
        return BusinessProfileAutoLinkService::getAvailableBusinessProfiles($this);
    }

    /**
     * Get recommended business profile for this charging point
     */
    public function getRecommendedBusinessProfile(): ?array
    {
        return BusinessProfileAutoLinkService::getRecommendedBusinessProfile($this);
    }

    /**
     * Check if a business profile is compatible with this charging point
     */
    public function isBusinessProfileCompatible(int $businessProfileId): bool
    {
        return BusinessProfileAutoLinkService::isBusinessProfileCompatible($businessProfileId, $this);
    }

    /**
     * Get business profile statistics for this charging point
     */
    public function getBusinessProfileStats(): array
    {
        return BusinessProfileAutoLinkService::getBusinessProfileStats($this);
    }

    /**
     * Validate business profile assignment
     */
    public function validateBusinessProfile(int $businessProfileId): BusinessProfile
    {
        return BusinessProfileAutoLinkService::validateBusinessProfile($businessProfileId);
    }

    /**
     * Get the operator for this charging point
     * Tries to get operator from: group->operator, group->user, or charging point->user
     * Note: The operator() relation points directly to the charging point's user.
     * Use this method for complex fallback logic.
     */
    public function getOperator(): ?User
    {
        // First try to get operator from group's operator_id
        if ($this->relationLoaded('group') && $this->group && $this->group->operator_id) {
            if ($this->group->relationLoaded('operator')) {
                return $this->group->operator;
            }
        } elseif ($this->group && $this->group->operator_id) {
            return $this->group->operator;
        }
        
        // Then try group's user
        if ($this->relationLoaded('group') && $this->group && $this->group->user_id) {
            if ($this->group->relationLoaded('user')) {
                return $this->group->user;
            }
        } elseif ($this->group && $this->group->user_id) {
            return $this->group->user;
        }
        
        // Finally fallback to charging point's user
        return $this->user;
    }

    /**
     * Accessor for operator attribute - handles fallback logic
     * This allows $chargingPoint->operator to work correctly even when eager loading
     */
    public function getOperatorAttribute(): ?User
    {
        // Use the getOperator() method which handles the complete fallback logic
        return $this->getOperator();
    }

    /**
     * Get the integrator for this charging point
     */
    public function getIntegrator(): ?Integrator
    {
        if ($this->integrator) {
            return $this->integrator;
        }
        
        if ($this->group && $this->group->partner && $this->group->partner->integrator) {
            return $this->group->partner->integrator;
        }
        
        return null;
    }

    /**
     * Get the partner for this charging point
     */
    public function getPartner(): ?Partner
    {
        if ($this->partner) {
            return $this->partner;
        }
        
        if ($this->group && $this->group->partner) {
            return $this->group->partner;
        }
        
        return null;
    }


    // Boot method for automatic integrator/partner assignment and validation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chargingPoint) {
            // Auto-assign partner_id from group if not set or null
            if (empty($chargingPoint->partner_id) && $chargingPoint->group_id) {
                $group = Group::find($chargingPoint->group_id);
                if ($group && $group->partner_id) {
                    $chargingPoint->partner_id = $group->partner_id;

                    // Auto-assign integrator_id from partner if not already set
                    if (empty($chargingPoint->integrator_id)) {
                        $partner = Partner::find($group->partner_id);
                        if ($partner && $partner->integrator_id) {
                            $chargingPoint->integrator_id = $partner->integrator_id;
                        }
                    }
                }
                
                // Auto-assign integrator_id from group if not already set and group has integrator_id
                if (empty($chargingPoint->integrator_id) && $group && $group->integrator_id) {
                    $chargingPoint->integrator_id = $group->integrator_id;
                }
            }
            
            // Auto-assign integrator_id from operator (user_id) if not already set
            if (empty($chargingPoint->integrator_id) && $chargingPoint->user_id) {
                $operator = User::find($chargingPoint->user_id);
                if ($operator && $operator->integrator_id) {
                    $chargingPoint->integrator_id = $operator->integrator_id;
                }
            }
        });

        // Auto-link business profile after creation
        static::created(function ($chargingPoint) {
            try {
                // Only auto-link if no business profile is already assigned
                if (!$chargingPoint->business_profile_id) {
                    BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
                }
            } catch (\Exception $e) {
                // Log the error but don't prevent charging point creation
                \Log::warning('Failed to auto-link business profile for charging point', [
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        // Validate that charging point belongs to a group
        static::saving(function ($chargingPoint) {
            // Skip validation in testing environment
            if (app()->environment('testing')) {
                return;
            }
            
            if (empty($chargingPoint->group_id)) {
                throw new \Exception('ChargingPoint must belong to a group.');
            }
            
            $group = Group::find($chargingPoint->group_id);
            if (!$group) {
                throw new \Exception('ChargingPoint must belong to a valid group.');
            }
        });
    }
}
