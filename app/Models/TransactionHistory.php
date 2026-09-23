<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'role',
        'type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec la transaction principale
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour les crédits
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope pour les débits
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope pour un rôle spécifique
     */
    public function scopeForRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Obtenir la classe CSS pour la couleur
     */
    public function getColorClassAttribute(): string
    {
        return $this->type === 'credit' ? 'text-success' : 'text-danger';
    }

    /**
     * Obtenir l'icône pour le type
     */
    public function getIconAttribute(): string
    {
        return $this->type === 'credit' ? 'arrow-up' : 'arrow-down';
    }

    /**
     * Obtenir le label du type
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'credit' ? 'Crédit' : 'Débit';
    }

    /**
     * Obtenir le label du rôle
     */
    public function getRoleLabelAttribute(): string
    {
        $labels = [
            'admin' => 'Administrateur',
            'integrator' => 'Intégrateur',
            'operator' => 'Opérateur',
        ];

        return $labels[$this->role] ?? $this->role;
    }

    /**
     * Obtenir le montant formaté
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' EUR';
    }

    /**
     * Vérifier si c'est un crédit
     */
    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    /**
     * Vérifier si c'est un débit
     */
    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }
}
