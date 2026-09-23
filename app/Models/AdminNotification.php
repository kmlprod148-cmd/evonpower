<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminNotification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'message',
        'type', // 'info', 'warning', 'success', 'error', 'system', 'announcement'
        'action_url',
        'icon',
        'is_read',
        'is_public',
        'created_by', // Utilisateur qui a généré la notification (optionnel)
        'target_roles', // Rôles auxquels la notification est destinée (JSON)
        'priority', // 'low', 'normal', 'high', 'urgent'
        'action_text',
        'color',
        'expires_at', // Date d'expiration (facultatif)
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'is_read' => 'boolean',
        'is_public' => 'boolean',
        'expires_at' => 'datetime',
        'target_roles' => 'array',
        'read_by' => 'array',
    ];

    /**
     * Obtenir l'utilisateur qui a généré la notification.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Marquer la notification comme lue.
     *
     * @return $this
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
        
        return $this;
    }

    /**
     * Vérifier si la notification a été lue.
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->is_read;
    }

    /**
     * Vérifier si la notification a expiré.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at !== null && now()->isAfter($this->expires_at);
    }

    /**
     * Scope pour les notifications non lues.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope pour les notifications non expirées.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope pour les notifications publiques.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope pour les notifications destinées à un rôle spécifique.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRole($query, $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereJsonContains('target_roles', $role)
              ->orWhereNull('target_roles')
              ->orWhere('target_roles', '[]');
        });
    }
}