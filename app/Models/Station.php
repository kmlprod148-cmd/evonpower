<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCreator;

class Station extends Model
{
    use HasFactory, HasCreator;

    protected $fillable = [
        'name', 'address', 'city', 'postal_code', 'country', 'type',
        'latitude', 'longitude', 'opening_hours', 'description', 'group_id', 'status',
        'created_by', 'created_by_role', 'created_by_type', 'created_by_id'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'opening_hours' => 'array',
    ];

    // Relations
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function chargingPoints()
    {
        return $this->hasMany(ChargingPoint::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCity($query, $city)
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

    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->whereHas('group', function ($q) use ($user) {
            $q->visibleToUser($user);
        });
    }

    // Accessors
    public function getActiveChargingPointsCountAttribute()
    {
        return $this->chargingPoints()->where('status', 'online')->count();
    }

    public function getTotalPowerOutputAttribute()
    {
        return $this->chargingPoints()->sum('power_output');
    }

    public function getFormattedAddressAttribute()
    {
        return trim("{$this->address}, {$this->city} {$this->postal_code}");
    }

    // Methods
    public function isOpenNow()
    {
        if (!$this->opening_hours) {
            return true; // Assume 24/7 if no hours specified
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $todayHours = $this->opening_hours[$dayOfWeek] ?? null;

        if (!$todayHours || $todayHours === 'closed') {
            return false;
        }

        if ($todayHours === '24/7') {
            return true;
        }

        [$open, $close] = explode('-', $todayHours);
        return $currentTime >= $open && $currentTime <= $close;
    }

    /**
     * Check if station can accept more charging points
     *
     * @return bool
     */
    public function canAcceptChargingPoints($count = 1)
    {
        return ($this->chargingPoints()->count() + $count) <= 2;
    }

    /**
     * Get remaining charging point slots
     *
     * @return int
     */
    public function getRemainingChargingPointSlots()
    {
        return max(0, 2 - $this->chargingPoints()->count());
    }

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Add validation before saving
        static::saving(function ($station) {
            // This validation is handled at the controller level
            // but we can add additional model-level validation here if needed
        });
    }
}