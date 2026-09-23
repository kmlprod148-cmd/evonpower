<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCreator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Integrator extends Model
{
    use HasFactory, HasCreator;

    /**
     * Boot method to add model events and validation
     */
    protected static function boot()
    {
        parent::boot();

        // Skip validation in testing environment
        if (app()->environment('testing')) {
            return;
        }

        // Ensure the integrator is created by an admin (on creation)
        static::creating(function ($integrator) {
            $creator = $integrator->created_by
                ? User::find($integrator->created_by)
                : (Auth::check() ? Auth::user() : null);

            if (!$creator || !$creator->hasRole(['admin', 'super_admin'])) {
                throw new \Exception('Integrator must be created by an admin user.');
            }
        });

        // Validate that integrator belongs to an admin (on save/update)
        static::saving(function ($integrator) {
            if ($integrator->created_by) {
                $admin = User::where('id', $integrator->created_by)
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('name', ['admin', 'super_admin']);
                    })->first();

                if (!$admin) {
                    throw new \Exception('Integrator must be created by an admin user.');
                }
            }
        });

        // Assigner automatiquement le rôle 'integrator' à l'utilisateur associé lors de la création
        static::created(function ($integrator) {
            if ($integrator->user_id) {
                $user = User::find($integrator->user_id);
                if ($user) {
                    // Assigner le rôle si nécessaire
                    if (!$user->hasRole('integrator')) {
                        $user->assignRole('integrator');
                    }
                    
                    // Assigner toutes les permissions nécessaires au rôle integrator
                    \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
                    
                    // Mettre à jour l'integrator_id de l'utilisateur s'il n'est pas déjà défini
                    if (!$user->integrator_id) {
                        $user->update(['integrator_id' => $integrator->id]);
                    }
                    
                    \Illuminate\Support\Facades\Log::info("Rôle 'integrator' et permissions assignées automatiquement à l'utilisateur {$user->id} lors de la création de l'intégrateur {$integrator->id}");
                }
            }
        });

        // Assigner automatiquement le rôle 'integrator' si user_id est modifié après la création
        static::saved(function ($integrator) {
            // Ne traiter que si user_id vient d'être assigné ou modifié
            if ($integrator->wasChanged('user_id') && $integrator->user_id) {
                $user = User::find($integrator->user_id);
                if ($user) {
                    // Assigner le rôle si nécessaire
                    if (!$user->hasRole('integrator')) {
                        $user->assignRole('integrator');
                    }
                    
                    // Assigner toutes les permissions nécessaires au rôle integrator
                    \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
                    
                    // Mettre à jour l'integrator_id de l'utilisateur s'il n'est pas déjà défini
                    if (!$user->integrator_id) {
                        $user->update(['integrator_id' => $integrator->id]);
                    }
                    
                    \Illuminate\Support\Facades\Log::info("Rôle 'integrator' et permissions assignées automatiquement à l'utilisateur {$user->id} lors de la mise à jour de l'intégrateur {$integrator->id}");
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'city',
        'address',
        'postal_code',
        'country',
        'contact_name',
        'website',
        'logo',
        'description',
        'is_active',
        'business_profile_id',
        'created_by',
        'created_by_role',
        'created_by_type',
        'created_by_id',
        'collection_mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get all business profiles associated with this integrator
     */
    public function businessProfiles()
    {
        return $this->hasMany(BusinessProfile::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function chargingPoints()
    {
        return $this->hasMany(ChargingPoint::class);
    }

    /**
     * Get the admin that created this integrator
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            });
    }

    /**
     * Get all operators belonging to this integrator
     */
    public function operators()
    {
        return $this->hasMany(User::class, 'integrator_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'operator');
            });
    }

    /**
     * Get the integrator's wallet
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Get or create the integrator's wallet
     */
    public function getOrCreateWallet(array $attributes = [])
    {
        return $this->wallet ?: \App\Services\WalletService::createWallet($this, $attributes);
    }

    /**
     * Credit amount to integrator's wallet
     */
    public function creditWallet(float $amount, string $description = null, array $metadata = [])
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->credit($amount, $description, $metadata);
    }

    /**
     * Debit amount from integrator's wallet
     */
    public function debitWallet(float $amount, string $description = null, array $metadata = [])
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->debit($amount, $description, $metadata);
    }

    /**
     * Get integrator's wallet balance
     */
    public function getWalletBalance(): float
    {
        return $this->wallet ? $this->wallet->balance : 0;
    }

    /**
     * Check if integrator has sufficient wallet balance
     */
    public function hasSufficientWalletBalance(float $amount): bool
    {
        return $this->wallet ? $this->wallet->hasSufficientBalance($amount) : false;
    }

    /**
     * Get formatted wallet balance
     */
    public function getFormattedWalletBalance(): string
    {
        return $this->wallet ? $this->wallet->getFormattedBalance() : '0.00 EUR';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    // Accessors
    public function getActivePartnersCountAttribute()
    {
        return $this->partners()->where('is_active', true)->count();
    }

    public function getActiveChargingPointsCountAttribute()
    {
        return $this->chargingPoints()->where('status', 'online')->count();
    }

    /**
     * Get the integrator's account.
     */
    public function account(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Account::class, 'accountable');
    }
}