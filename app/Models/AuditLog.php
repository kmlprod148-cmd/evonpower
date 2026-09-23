<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'auditable_type',
        'auditable_id',
        'metadata',
        'severity',
        'session_id',
        'method',
        'status_code',
        'execution_time_ms',
        'ip_address',
        'user_agent',
        'url',
    ];

    protected $casts = [
        'metadata' => 'array',
        'severity' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphe avec le modèle audité
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Scope pour les logs d'un utilisateur spécifique
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les logs d'une action spécifique
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour les logs d'un modèle spécifique
     */
    public function scopeForModel($query, $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }

    /**
     * Scope pour les logs d'une période spécifique
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Accessor pour formater les anciennes valeurs
     */
    public function getFormattedOldValuesAttribute()
    {
        return $this->old_values ? json_encode($this->old_values, JSON_PRETTY_PRINT) : null;
    }

    /**
     * Accessor pour formater les nouvelles valeurs
     */
    public function getFormattedNewValuesAttribute()
    {
        return $this->new_values ? json_encode($this->new_values, JSON_PRETTY_PRINT) : null;
    }

    /**
     * Scope pour les logs par niveau de sévérité
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope pour les logs de sécurité
     */
    public function scopeSecurityEvents($query)
    {
        return $query->where('action', 'like', 'security.%');
    }

    /**
     * Scope pour les logs d'authentification
     */
    public function scopeAuthEvents($query)
    {
        return $query->where('action', 'like', 'auth.%');
    }

    /**
     * Scope pour les logs financiers
     */
    public function scopeFinancialEvents($query)
    {
        return $query->where('action', 'like', 'financial.%');
    }

    /**
     * Accessor pour formater les métadonnées
     */
    public function getFormattedMetadataAttribute()
    {
        return $this->metadata ? json_encode($this->metadata, JSON_PRETTY_PRINT) : null;
    }

    /**
     * Accessor pour obtenir l'adresse IP formatée
     */
    public function getFormattedIpAttribute()
    {
        return $this->ip_address ? long2ip($this->ip_address) : $this->ip_address;
    }

    /**
     * Accessor pour obtenir le temps d'exécution formaté
     */
    public function getFormattedExecutionTimeAttribute()
    {
        if (!$this->execution_time_ms) {
            return null;
        }
        
        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms . 'ms';
        }
        
        return round($this->execution_time_ms / 1000, 2) . 's';
    }

    /**
     * Méthode pour créer un log d'audit
     */
    public static function createLog($userId, $action, $auditable, $oldValues = null, $newValues = null)
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'metadata' => [
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Méthode pour créer un log de sécurité
     */
    public static function createSecurityLog($action, $description, $severity = 'medium', $userId = null)
    {
        return self::create([
            'user_id' => $userId,
            'action' => 'security.' . $action,
            'description' => $description,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'session_id' => session()->getId(),
        ]);
    }
}
