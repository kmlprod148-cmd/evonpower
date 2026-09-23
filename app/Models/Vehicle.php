<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'make',
        'model',
        'registration',
        'battery_capacity',
        'connector_type',
        'year',
        'color',
        'vin',
        'is_active',
        'is_primary',
        'client_user_id',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'year' => 'integer',
    ];

    /**
     * Common connector types
     */
    public static function connectorTypes(): array
    {
        return [
            'Type 2' => 'Type 2',
            'CCS' => 'CCS (Combined Charging System)',
            'CHAdeMO' => 'CHAdeMO',
            'Type 1' => 'Type 1 (J1772)',
            'Type 1 CCS' => 'Type 1 CCS',
            'Tesla' => 'Tesla (NACS)',
            'Tesla Supercharger' => 'Tesla Supercharger',
            'Wall' => 'Wall (prise domestique)',
        ];
    }

    /**
     * Relation avec le client
     */
    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'client_user_id');
    }

    /**
     * Get the full name of the vehicle
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->make} {$this->model}";
    }

    /**
     * Get formatted registration
     */
    public function getFormattedRegistrationAttribute(): string
    {
        return strtoupper($this->registration);
    }

    /**
     * Check if vehicle can use a specific connector
     */
    public function canUseConnector(string $connectorType): bool
    {
        // Type 2 is the European standard
        if ($this->connector_type === 'Type 2' || $this->connector_type === 'CCS') {
            return true;
        }

        return $this->connector_type === $connectorType;
    }

    /**
     * Set as primary vehicle
     */
    public function setAsPrimary(): void
    {
        // Remove primary flag from other vehicles
        static::where('client_user_id', $this->client_user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Scope for active vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary vehicle
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
