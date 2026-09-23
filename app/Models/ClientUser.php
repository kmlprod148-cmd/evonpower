<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'client_users';

    protected $fillable = [
        'name',
        'first_name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'user_id',
        'is_active',
        'email_verified_at',
        'activated_at',
        'language',
        'currency',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activated_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Generate temporary password on creation
        static::creating(function ($clientUser) {
            if (empty($clientUser->password)) {
                $clientUser->password = Hash::make(Str::random(12));
            }
            if (empty($clientUser->language)) {
                $clientUser->language = 'fr';
            }
            if (empty($clientUser->currency)) {
                $clientUser->currency = 'EUR';
            }
        });
    }

    /**
     * Relation avec l'utilisateur système (compte administrateur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les véhicules
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'client_user_id');
    }

    /**
     * Relation avec les tags OCPP
     */
    public function ocppTags(): HasMany
    {
        return $this->hasMany(OcppTag::class, 'user_id');
    }

    /**
     * Relation avec les réservations
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    /**
     * Relation avec les transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    /**
     * Get the user's wallet
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Get or create the user's wallet
     */
    public function getOrCreateWallet(array $attributes = [])
    {
        if (!$this->relationLoaded('wallet')) {
            $this->load('wallet');
        }

        if ($this->wallet) {
            return $this->wallet;
        }

        $wallet = Wallet::create(array_merge([
            'owner_type' => self::class,
            'owner_id' => $this->id,
            'balance' => 0,
            'currency' => $this->currency ?? 'EUR',
            'is_active' => true,
            'name' => 'Portefeuille - ' . ($this->name ?? $this->email),
        ], $attributes));

        return $wallet;
    }

    /**
     * Get the primary vehicle
     */
    public function getPrimaryVehicle()
    {
        return $this->vehicles()->where('is_primary', true)->first();
    }

    /**
     * Get all active vehicles
     */
    public function getActiveVehicles()
    {
        return $this->vehicles()->where('is_active', true)->get();
    }

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): bool
    {
        return $this->update([
            'email_verified_at' => now(),
            'activated_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Generate temporary password
     */
    public function generateTemporaryPassword(): string
    {
        $password = Str::random(10);
        $this->password = Hash::make($password);
        $this->save();
        return $password;
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->name ?? ''));
    }

    /**
     * Check whether this client has made at least one reservation.
     * Used by CreditRechargeController to decide which credit packs to show.
     */
    public function hasMadeReservations(): bool
    {
        return $this->reservations()->exists();
    }

    /**
     * Scope for active clients
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for verified clients
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Get the default connector type based on primary vehicle
     */
    public function getPreferredConnectorType(): ?string
    {
        $vehicle = $this->getPrimaryVehicle();
        return $vehicle?->connector_type;
    }

    /**
     * Record last login
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Spatie Permission Compatibility methods
     * Prevent BadMethodCallException when UI components check client roles
     */
    public function getRoleNames()
    {
        return collect(['client']);
    }

    public function hasRole($roles, $guard = null): bool
    {
        if (is_string($roles) && $roles === 'client') return true;
        if (is_array($roles) && in_array('client', $roles)) return true;
        return false;
    }

    public function hasAnyRole(...$roles): bool
    {
        $roles = is_array($roles[0]) ? $roles[0] : $roles;
        return $this->hasRole($roles);
    }

    public function getAllPermissions()
    {
        return collect([]);
    }

    /**
     * Check if user has a specific permission
     * For clients, grant view_own_transactions by default
     */
    public function can($abilities, $arguments = [])
    {
        if (is_array($abilities)) {
            foreach ($abilities as $ability) {
                if (!$this->can($ability, $arguments)) {
                    return false;
                }
            }
            return true;
        }

        // Client users can view their own transactions
        $clientPermissions = ['view_own_transactions', 'view_own_history'];
        if (in_array($abilities, $clientPermissions)) {
            return true;
        }

        return false;
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        return false;
    }
}
