<?php

namespace App\Modules\ChargingPoints\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\PricingPlan;
use App\Models\BusinessProfile;
use App\Models\Connector;

class ChargingPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'serial_number',
        'manufacturer',
        'model',
        'status',
        'installation_date',
        'user_id',
        'integrator_id',
        'partner_id',
        'operator_id',
        'group_id',
        'station_id',
        'business_profile_id',
        'pricing_plan_id',
        'location',
        'description',
        'address',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'max_power',
        'power_output',
        'connector_type',
        'connection_type',
        'is_active',
        'public_access',
        'firmware_version',
        'communication_protocol',
        'ip_address',
        'mac_address',
        'last_maintenance_date',
        'next_maintenance_date',
        'access_type',
        'access_code',
        'qr_code',
        'notes',
        'last_activity',
        'last_connected_at',
        'last_disconnected_at',
        'steve_charging_point_id',
        'steve_sync_status',
        'charge_box_id',
        'websocket_url',
        'steve_server_url',
        'last_connection_attempt',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'public_access' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'max_power' => 'float',
        'power_output' => 'float',
        'last_connected_at' => 'datetime',
        'last_disconnected_at' => 'datetime',
        'last_activity' => 'datetime',
    ];

    protected $touches = ['group'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class, 'business_profile_id');
    }

    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function operator()
    {
        return $this->belongsTo(\App\Models\User::class, 'operator_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function pricingPlan()
    {
        return $this->belongsTo(PricingPlan::class);
    }

    public function connectors()
    {
        return $this->hasMany(Connector::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activeTransactions()
    {
        return $this->transactions()->where('status', 'in_progress');
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    public function scopePublic($query)
    {
        return $query->where('public_access', true);
    }

    public function isAvailable()
    {
        return $this->status === 'online';
    }

    public function getFullAddressAttribute()
    {
        $parts = [];
        
        if ($this->address) {
            $parts[] = $this->address;
        }
        
        if ($this->postal_code && $this->city) {
            $parts[] = "{$this->postal_code} {$this->city}";
        } elseif ($this->city) {
            $parts[] = $this->city;
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }
        
        return implode(', ', $parts);
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            'online' => 'En ligne',
            'offline' => 'Hors ligne',
            'maintenance' => 'En maintenance',
            'error' => 'En erreur'
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    public function getAccessTypeLabelAttribute()
    {
        $types = [
            'public' => 'Public',
            'private' => 'Privé',
            'restricted' => 'Restreint'
        ];

        return $types[$this->access_type] ?? $this->access_type;
    }

    public function scopeVisibleToUser($query, \App\Models\User $user)
    {
        $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $isAdmin       = in_array('admin', $userRolesLower) || in_array('super_admin', $userRolesLower);
        $isIntegrator  = in_array('integrator', $userRolesLower);
        $isPartner     = in_array('partner', $userRolesLower);
        $isOperator    = in_array('operator', $userRolesLower);

        if ($isAdmin) {
            return $query;
        }

        $integratorId = $user->integrator_id ?? \App\Models\Integrator::where('user_id', $user->id)->value('id');
        if ($isIntegrator && $integratorId) {
            return $query->where(function ($q) use ($integratorId) {
                $allUserIds = \App\Models\User::where('integrator_id', $integratorId)->pluck('id')->toArray();
                $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)->pluck('id')->toArray();

                $q->where('integrator_id', $integratorId)
                  ->orWhereIn('user_id', $allUserIds)
                  ->orWhereIn('partner_id', $partnerIds)
                  ->orWhereHas('group', function ($g) use ($partnerIds, $integratorId) {
                      $g->whereIn('partner_id', $partnerIds)
                        ->orWhere('integrator_id', $integratorId);
                  })
                  ->orWhereIn('created_by', $allUserIds);
            });
        }

        if ($isPartner && $user->partner_id) {
            return $query->where(function ($q) use ($user) {
                $q->where('partner_id', $user->partner_id)
                  ->orWhereHas('group', function ($groupQuery) use ($user) {
                      $groupQuery->where('partner_id', $user->partner_id);
                  });
            });
        }

        if ($isOperator) {
            $groupIds = \App\Models\Group::where('user_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $groupIds) {
                $q->where('user_id', $user->id);
                if (!empty($groupIds)) {
                    $q->orWhereIn('group_id', $groupIds);
                }
                if ($user->integrator_id) {
                    $q->orWhere('integrator_id', $user->integrator_id);
                }
            });
        }

        if ($user->can('view_public_charging_points')) {
            return $query;
        }

        return $query->whereNull('id');
    }
}