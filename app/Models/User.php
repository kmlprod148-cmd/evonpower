<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Integrator;
use App\Models\Wallet;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Boot method to add model events and validation
     */
    protected static function boot()
    {
        parent::boot();
        
        // Validate operator-integrator relationship
        static::saving(function ($user) {
            if ($user->hasRole('operator') && $user->integrator_id) {
                // Check if the integrator exists and is valid
                $integrator = Integrator::find($user->integrator_id);
                if (!$integrator) {
                    throw new \Exception('Operator must belong to a valid integrator.');
                }
                
                // Check if the creator is an integrator
                if ($user->created_by) {
                    $creator = User::find($user->created_by);
                    if (!$creator || !$creator->hasRole('integrator')) {
                        throw new \Exception('Operator must be created by an integrator.');
                    }
                }
            }
        });

        // Assign integrator role and permissions automatically when integrator_id is set
        static::created(function ($user) {
            if ($user->integrator_id) {
                try {
                    \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
                    \Illuminate\Support\Facades\Log::info("Integrator role and permissions auto-assigned to user {$user->id} on creation");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to auto-assign integrator role to user {$user->id}: " . $e->getMessage());
                }
            }
        });

        static::updated(function ($user) {
            // Si integrator_id a été modifié ou ajouté
            if ($user->wasChanged('integrator_id') && $user->integrator_id) {
                try {
                    \App\Services\IntegratorPermissionService::assignIntegratorPermissions($user);
                    \Illuminate\Support\Facades\Log::info("Integrator role and permissions auto-assigned to user {$user->id} on update");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to auto-assign integrator role to user {$user->id}: " . $e->getMessage());
                }
            }
        });
    }

    protected $fillable = [
        'name', 'email', 'password', 'integrator_id', 'partner_id', 'business_profile_id',
        'phone', 'phone_verified_at', 'address', 'city', 'postal_code', 'country',
        'company', 'position', 'notes', 'last_login',
        'language', 'timezone', 'theme', 'is_active', 'email_verified_at',
        'email_security_alerts', 'email_account_updates', 'email_transactions',
        'email_stations', 'email_marketing', 'app_security_alerts',
        'app_account_updates', 'app_transactions', 'app_stations', 'email_frequency',
        'balance', 'currency', 'default_payment_method', 'created_by',
        'created_by_type', 'created_by_id'
    ];

    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
        'currency' => 'string',
    ];

    /**
     * Vérifie si le mot de passe fourni correspond au hash stocké
     * Gère automatiquement les mots de passe non hachés
     */
    public function validatePassword($password)
    {
        $storedPassword = $this->getAttribute('password');
        
        // Si le mot de passe stocké n'est pas un hash Bcrypt, comparer directement
        if (!$this->isBcryptHash($storedPassword)) {
            return $password === $storedPassword;
        }
        
        // Sinon, utiliser la méthode standard de Laravel
        return \Illuminate\Support\Facades\Hash::check($password, $storedPassword);
    }

    /**
     * Vérifie si une chaîne est un hash Bcrypt valide
     */
    private function isBcryptHash($hash)
    {
        return preg_match('/^\$2y\$[0-9]{2}\$[.\/A-Za-z0-9]{53}$/', $hash);
    }

    // Relations
    public function integrator()
    {
        return $this->belongsTo(Integrator::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Direct relationship to Business Profile (for admins, operators with direct BP)
     */
    public function directBusinessProfile()
    {
        return $this->belongsTo(BusinessProfile::class, 'business_profile_id');
    }

    /**
     * Associate a business profile with this user
     * This method handles the association based on user role
     */
    public function associateBusinessProfile(BusinessProfile $businessProfile)
    {
        if ($this->hasRole('integrator')) {
            $integrator = Integrator::where('user_id', $this->id)->first();
            if ($integrator) {
                $integrator->business_profile_id = $businessProfile->id;
                $integrator->save();
            }
        } elseif ($this->hasRole('partner') && $this->partner_id) {
            $partner = $this->partner;
            if ($partner) {
                $partner->business_profile_id = $businessProfile->id;
                $partner->save();
            }
        }
        
        return $this;
    }

    /**
     * Mock method to support test syntax: $user->businessProfile()->save($bp)
     * This creates a mock object that supports the save() method
     */
    public function businessProfile()
    {
        $profile = $this->getBusinessProfile();
        
        // Return a mock object that supports save() method
        return new class($this, $profile) {
            private $user;
            private $profile;
            
            public function __construct($user, $profile) {
                $this->user = $user;
                $this->profile = $profile;
            }
            
            public function save($businessProfile) {
                return $this->user->associateBusinessProfile($businessProfile);
            }
            
            public function __call($method, $args) {
                if ($this->profile) {
                    return call_user_func_array([$this->profile, $method], $args);
                }
                return null;
            }
        };
    }

    /**
     * Get the actual business profile object
     * 
     * Priorité:
     * 1. Business Profile direct de l'utilisateur (si défini)
     * 2. Business Profile via Integrator (pour operators/integrators)
     * 3. Business Profile via Partner (pour partners)
     */
    private function getBusinessProfile()
    {
        // Priorité 1: Business Profile direct de l'utilisateur (pour admins, operators avec BP direct)
        if ($this->business_profile_id) {
            return BusinessProfile::find($this->business_profile_id);
        }
        
        // Priorité 2: Pour operators, chercher via leur intégrateur
        if ($this->hasRole('operator') && $this->integrator_id) {
            $integrator = $this->integrator;
            if ($integrator && $integrator->businessProfile) {
                return $integrator->businessProfile;
            }
        }
        
        // Priorité 3: Pour integrators, chercher via le modèle Integrator
        if ($this->hasRole('integrator')) {
            $integrator = Integrator::where('user_id', $this->id)->first();
            if ($integrator && $integrator->businessProfile) {
                return $integrator->businessProfile;
            }
        }
        
        // Priorité 4: Pour partners, chercher via le modèle Partner
        if ($this->hasRole('partner') && $this->partner_id) {
            $partner = $this->partner;
            if ($partner && $partner->businessProfile) {
                return $partner->businessProfile;
            }
        }
        
        return null;
    }

    /**
     * Get the integrator that created this operator
     */
    public function operatorIntegrator()
    {
        return $this->belongsTo(Integrator::class, 'integrator_id');
    }

    /**
     * Get all operators created by this integrator
     */
    public function createdOperators()
    {
        return $this->hasMany(User::class, 'created_by')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'operator');
            });
    }

    public function groups()
    {
        return $this->hasMany(Group::class, 'user_id');
    }

    public function chargingPoints()
    {
        return $this->hasMany(ChargingPoint::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'operator_id');
    }

    public function adminTransactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'admin_creator_id');
    }

    public function integratorTransactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'integrator_creator_id');
    }

    public function paidTransactions()
    {
        return $this->hasMany(TransactionHierarchy::class, 'payer_id');
    }

    public function receivedTransactions()
    {
        return $this->hasMany(TransactionHierarchy::class, 'payee_id');
    }

    /**
     * Get the user's notifications
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIntegrators($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('name', 'integrator');
        });
    }

    /**
     * Scope to filter users by integrator
     */
    public function scopeForIntegrator($query, $integratorId)
    {
        return $query->where('integrator_id', $integratorId);
    }

    // Ajouter ces scopes dans la classe User

    public function scopeVisibleToUser($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('integrator')) {
            return $query->where('integrator_id', $user->integrator_id);
        }

        if ($user->hasRole('partner')) {
            return $query->where('partner_id', $user->partner_id);
        }

        return $query->where('id', $user->id);
    }

    public function canManage($resource)
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if ($this->hasRole('integrator')) {
            if (method_exists($resource, 'integrator_id')) {
                return $resource->integrator_id === $this->integrator_id;
            }
            if (method_exists($resource, 'partner') && $resource->partner) {
                return $resource->partner->integrator_id === $this->integrator_id;
            }
        }

        if ($this->hasRole('partner')) {
            if (method_exists($resource, 'partner_id')) {
                return $resource->partner_id === $this->partner_id;
            }
            if (method_exists($resource, 'user_id')) {
                return $resource->user_id === $this->id;
            }
        }

        return false;
    }

    /**
     * Get the badge color for the user's role.
     *
     * @return string
     */
    public function getRoleBadgeColor(): string
    {
        if ($this->hasRole('admin')) {
            return 'red';
        } elseif ($this->hasRole('integrator')) {
            return 'blue';
        } elseif ($this->hasRole('partner')) {
            return 'purple';
        } else {
            return 'gray';
        }
    }

    /**
     * Ajouter de l'argent au solde
     */
    public function addBalance(float $amount): bool
    {
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * Retirer de l'argent du solde
     */
    public function subtractBalance(float $amount): bool
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            return $this->save();
        }
        return false;
    }

    /**
     * Vérifier si le solde est suffisant
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Obtenir le solde formaté
     */
    public function getFormattedBalance(): string
    {
        return number_format($this->balance, 2) . ' ' . $this->currency;
    }

    /**
     * Obtenir la hiérarchie des créateurs
     */
    public function getCreatorHierarchy(): array
    {
        $hierarchy = [];
        $current = $this;
        
        while ($current->creator) {
            $hierarchy[] = [
                'user' => $current,
                'creator' => $current->creator,
                'role' => $current->creator->getRoleNames()->first(),
            ];
            $current = $current->creator;
        }
        
        return $hierarchy;
    }

    /**
     * Obtenir l'admin racine de la hiérarchie
     */
    public function getRootAdmin(): ?User
    {
        $current = $this;
        
        while ($current->creator) {
            if ($current->creator->hasRole('admin')) {
                return $current->creator;
            }
            $current = $current->creator;
        }
        
        return $current->hasRole('admin') ? $current : null;
    }

    /**
     * Obtenir l'intégrateur direct
     */
    public function getDirectIntegrator(): ?User
    {
        if ($this->creator && $this->creator->hasRole('integrator')) {
            return $this->creator;
        }
        
        return null;
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
        // Charger la relation wallet si elle n'est pas déjà chargée
        if (!$this->relationLoaded('wallet')) {
            $this->load('wallet');
        }
        
        // Si le wallet existe, le retourner
        if ($this->wallet) {
            return $this->wallet;
        }
        
        // Sinon, créer un nouveau wallet
        return \App\Services\WalletService::createWallet($this, $attributes);
    }

    /**
     * Credit amount to user's wallet
     */
    public function creditWallet(float $amount, string $description = null, array $metadata = [])
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->credit($amount, $description, $metadata);
    }

    /**
     * Debit amount from user's wallet
     */
    public function debitWallet(float $amount, string $description = null, array $metadata = [])
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->debit($amount, $description, $metadata);
    }

    /**
     * Get user's wallet balance (always fresh from DB for consistency)
     */
    public function getWalletBalance(): float
    {
        $wallet = $this->getOrCreateWallet();
        return (float) ($wallet->fresh()->balance ?? 0);
    }

    /**
     * Check if user has sufficient wallet balance (always fresh from DB)
     */
    public function hasSufficientWalletBalance(float $amount): bool
    {
        $wallet = $this->getOrCreateWallet();
        return $wallet->fresh()->hasSufficientBalance($amount);
    }

    /**
     * Get formatted wallet balance
     */
    public function getFormattedWalletBalance(): string
    {
        return $this->wallet ? $this->wallet->getFormattedBalance() : '0.00 EUR';
    }

    /**
     * Relation avec les réservations
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Parts de réservation en tant que participant
     */
    public function reservationParticipants()
    {
        return $this->hasMany(ReservationParticipant::class);
    }

    /**
     * Vérifie si l'utilisateur a fait des réservations (en tant que client)
     */
    public function hasMadeReservations(): bool
    {
        return $this->reservations()->exists();
    }
}
