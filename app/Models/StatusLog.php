<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'entity_name',
        'status',
        'health_status',
        'response_time_ms',
        'status_code',
        'response_body',
        'error_message',
        'metadata',
        'checked_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'checked_at' => 'datetime'
    ];

    /**
     * Scope pour filtrer par type d'entité
     */
    public function scopeEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer par statut de santé
     */
    public function scopeHealthStatus($query, $healthStatus)
    {
        return $query->where('health_status', $healthStatus);
    }

    /**
     * Scope pour les logs récents
     */
    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('checked_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope pour une période donnée
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('checked_at', [$startDate, $endDate]);
    }

    /**
     * Scope pour une entité spécifique
     */
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
                    ->where('entity_id', $entityId);
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'degraded' => 'yellow',
            'unknown' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Obtenir la couleur du statut de santé
     */
    public function getHealthColorAttribute()
    {
        return match($this->health_status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            'unknown' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Obtenir l'icône du statut
     */
    public function getStatusIconAttribute()
    {
        return match($this->status) {
            'online' => 'check-circle',
            'offline' => 'times-circle',
            'degraded' => 'exclamation-triangle',
            'unknown' => 'question-circle',
            default => 'question-circle'
        };
    }

    /**
     * Obtenir l'icône du statut de santé
     */
    public function getHealthIconAttribute()
    {
        return match($this->health_status) {
            'healthy' => 'heartbeat',
            'warning' => 'exclamation-triangle',
            'critical' => 'heart-broken',
            'unknown' => 'question-circle',
            default => 'question-circle'
        };
    }

    /**
     * Obtenir le temps de réponse formaté
     */
    public function getFormattedResponseTimeAttribute()
    {
        if (!$this->response_time_ms) {
            return 'N/A';
        }

        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms . 'ms';
        } else {
            return round($this->response_time_ms / 1000, 2) . 's';
        }
    }

    /**
     * Obtenir les statistiques par entité
     */
    public static function getEntityStats($entityType, $entityId = null, $minutes = 60)
    {
        $query = self::entityType($entityType)->recent($minutes);
        
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        $logs = $query->get();

        return [
            'total_checks' => $logs->count(),
            'online_count' => $logs->where('status', 'online')->count(),
            'offline_count' => $logs->where('status', 'offline')->count(),
            'degraded_count' => $logs->where('status', 'degraded')->count(),
            'unknown_count' => $logs->where('status', 'unknown')->count(),
            'uptime_percentage' => $logs->count() > 0 ? round(($logs->where('status', 'online')->count() / $logs->count()) * 100, 2) : 0,
            'average_response_time' => $logs->where('response_time_ms', '>', 0)->avg('response_time_ms'),
            'last_check' => $logs->sortByDesc('checked_at')->first(),
            'health_distribution' => $logs->groupBy('health_status')->map->count()
        ];
    }

    /**
     * Obtenir les entités avec leur statut actuel
     */
    public static function getCurrentEntityStatus($entityType)
    {
        return self::entityType($entityType)
            ->selectRaw('entity_id, entity_name, status, health_status, response_time_ms, checked_at, MAX(checked_at) as latest_check')
            ->groupBy('entity_id', 'entity_name', 'status', 'health_status', 'response_time_ms', 'checked_at')
            ->havingRaw('checked_at = MAX(checked_at)')
            ->orderBy('entity_name')
            ->get();
    }

    /**
     * Obtenir l'historique d'une entité
     */
    public static function getEntityHistory($entityType, $entityId, $hours = 24)
    {
        return self::forEntity($entityType, $entityId)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->orderBy('checked_at', 'desc')
            ->get();
    }

    /**
     * Obtenir les alertes de statut
     */
    public static function getStatusAlerts($entityType = null, $minutes = 60)
    {
        $query = self::recent($minutes);
        
        if ($entityType) {
            $query->entityType($entityType);
        }

        $alerts = [];

        // Entités offline
        $offlineEntities = $query->status('offline')
            ->selectRaw('entity_type, entity_id, entity_name, MAX(checked_at) as last_offline')
            ->groupBy('entity_type', 'entity_id', 'entity_name')
            ->get();

        foreach ($offlineEntities as $entity) {
            $alerts[] = [
                'type' => 'critical',
                'entity_type' => $entity->entity_type,
                'entity_id' => $entity->entity_id,
                'entity_name' => $entity->entity_name,
                'message' => "{$entity->entity_name} est hors ligne",
                'last_offline' => $entity->last_offline
            ];
        }

        // Entités avec statut dégradé
        $degradedEntities = $query->status('degraded')
            ->selectRaw('entity_type, entity_id, entity_name, MAX(checked_at) as last_degraded')
            ->groupBy('entity_type', 'entity_id', 'entity_name')
            ->get();

        foreach ($degradedEntities as $entity) {
            $alerts[] = [
                'type' => 'warning',
                'entity_type' => $entity->entity_type,
                'entity_id' => $entity->entity_id,
                'entity_name' => $entity->entity_name,
                'message' => "{$entity->entity_name} a des performances dégradées",
                'last_degraded' => $entity->last_degraded
            ];
        }

        return $alerts;
    }

    /**
     * Obtenir les métriques de performance
     */
    public static function getPerformanceMetrics($entityType = null, $minutes = 60)
    {
        $query = self::recent($minutes);
        
        if ($entityType) {
            $query->entityType($entityType);
        }

        $logs = $query->get();

        return [
            'total_entities' => $logs->groupBy('entity_id')->count(),
            'online_entities' => $logs->where('status', 'online')->groupBy('entity_id')->count(),
            'offline_entities' => $logs->where('status', 'offline')->groupBy('entity_id')->count(),
            'degraded_entities' => $logs->where('status', 'degraded')->groupBy('entity_id')->count(),
            'overall_uptime' => $logs->count() > 0 ? round(($logs->where('status', 'online')->count() / $logs->count()) * 100, 2) : 0,
            'average_response_time' => $logs->where('response_time_ms', '>', 0)->avg('response_time_ms'),
            'max_response_time' => $logs->max('response_time_ms'),
            'min_response_time' => $logs->where('response_time_ms', '>', 0)->min('response_time_ms')
        ];
    }

    /**
     * Nettoyer les anciens logs
     */
    public static function cleanupOldLogs($days = 30)
    {
        return self::where('checked_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Obtenir le statut actuel d'une entité
     */
    public static function getCurrentStatus($entityType, $entityId)
    {
        return self::forEntity($entityType, $entityId)
            ->orderBy('checked_at', 'desc')
            ->first();
    }

    /**
     * Marquer une entité comme online
     */
    public static function markOnline($entityType, $entityId, $entityName, $responseTime = null, $metadata = null)
    {
        return self::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'status' => 'online',
            'health_status' => 'healthy',
            'response_time_ms' => $responseTime,
            'status_code' => 200,
            'metadata' => $metadata,
            'checked_at' => now()
        ]);
    }

    /**
     * Marquer une entité comme offline
     */
    public static function markOffline($entityType, $entityId, $entityName, $errorMessage = null, $metadata = null)
    {
        return self::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'status' => 'offline',
            'health_status' => 'critical',
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'checked_at' => now()
        ]);
    }

    /**
     * Marquer une entité comme dégradée
     */
    public static function markDegraded($entityType, $entityId, $entityName, $responseTime = null, $errorMessage = null, $metadata = null)
    {
        return self::create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'status' => 'degraded',
            'health_status' => 'warning',
            'response_time_ms' => $responseTime,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'checked_at' => now()
        ]);
    }
}
