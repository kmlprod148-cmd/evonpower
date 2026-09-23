<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class EnhancedUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'business_profile_id',
        'balance',
        'currency',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec le BusinessProfile
     */
    public function businessProfile()
    {
        return $this->belongsTo(EnhancedBusinessProfile::class);
    }

    /**
     * Transactions où cet utilisateur est la source
     */
    public function sourceTransactions()
    {
        return $this->hasMany(EnhancedTransaction::class, 'source_user_id');
    }

    /**
     * Transactions où cet utilisateur est la cible
     */
    public function targetTransactions()
    {
        return $this->hasMany(EnhancedTransaction::class, 'target_user_id');
    }

    /**
     * Toutes les transactions de cet utilisateur (source et cible)
     */
    public function allTransactions()
    {
        return EnhancedTransaction::where('source_user_id', $this->id)
            ->orWhere('target_user_id', $this->id);
    }

    /**
     * Business profiles créés par cet utilisateur
     */
    public function ownedBusinessProfiles()
    {
        return $this->hasMany(EnhancedBusinessProfile::class, 'owner_id');
    }

    /**
     * Vérifier si l'utilisateur est un admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est un intégrateur
     */
    public function isIntegrator(): bool
    {
        return $this->role === 'integrator';
    }

    /**
     * Vérifier si l'utilisateur est un opérateur
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Ajouter du solde
     */
    public function addBalance(float $amount): bool
    {
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * Soustraire du solde
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
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeIntegrators($query)
    {
        return $query->where('role', 'integrator');
    }

    public function scopeOperators($query)
    {
        return $query->where('role', 'operator');
    }

    /**
     * Obtenir les statistiques de transaction
     */
    public function getTransactionStats(): array
    {
        $sourceCount = $this->sourceTransactions()->count();
        $targetCount = $this->targetTransactions()->count();
        $totalSent = $this->sourceTransactions()->sum('amount');
        $totalReceived = $this->targetTransactions()->sum('amount');

        return [
            'transactions_sent' => $sourceCount,
            'transactions_received' => $targetCount,
            'total_sent' => $totalSent,
            'total_received' => $totalReceived,
            'net_amount' => $totalReceived - $totalSent,
        ];
    }
}
