<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle pour les tags OCPP (idTag dans OCPP)
 * 
 * Un tag OCPP est utilisé pour authentifier l'utilisateur auprès de la borne
 * via le protocole OCPP. C'est l'équivalent d'une carte RFID ou d'un identifiant mobile.
 */
class OcppTag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ocpp_tag',
        'user_id',
        'blocked',
        'is_default',
        'parent_id_tag',
        'expiry_date',
        'note',
        'total_sessions',
        'last_used_at',
    ];

    protected $casts = [
        'blocked' => 'boolean',
        'is_default' => 'boolean',
        'expiry_date' => 'datetime',
        'last_used_at' => 'datetime',
        'total_sessions' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur (User system)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le client (ClientUser)
     */
    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(ClientUser::class, 'user_id', 'user_id');
    }

    /**
     * Relation avec l'historique des associations
     */
    public function history(): HasMany
    {
        return $this->hasMany(OcppTagHistory::class);
    }

    /**
     * Relation avec les réservations
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'ocpp_tag_id');
    }

    /**
     * Relation avec les sessions de charge
     */
    public function chargingSessions(): HasMany
    {
        return $this->hasMany(ChargingSession::class, 'ocpp_tag', 'ocpp_tag');
    }

    /**
     * Vérifier si le tag est actif (non bloqué et non expiré)
     */
    public function isActive(): bool
    {
        if ($this->blocked) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Bloquer le tag
     */
    public function block(string $reason = null): void
    {
        $this->update([
            'blocked' => true,
            'note' => $reason ? "Bloqué: {$reason}" : $this->note,
        ]);
    }

    /**
     * Débloquer le tag
     */
    public function unblock(): void
    {
        $this->update(['blocked' => false]);
    }

    /**
     * Incrémenter le compteur de sessions
     */
    public function incrementSessions(): void
    {
        $this->increment('total_sessions');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Définir comme tag par défaut pour l'utilisateur
     */
    public function setAsDefault(): void
    {
        // Retirer le flag default des autres tags de cet utilisateur
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Scope pour les tags actifs (non bloqués et non expirés)
     */
    public function scopeActive($query)
    {
        return $query->where('blocked', false)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope pour les tags bloqués
     */
    public function scopeBlocked($query)
    {
        return $query->where('blocked', true);
    }

    /**
     * Scope pour les tags par défaut
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

