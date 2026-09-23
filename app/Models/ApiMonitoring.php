<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApiMonitoring extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_monitoring';

    protected $fillable = [
        'api_name',
        'endpoint',
        'method',
        'status_code',
        'response_body',
        'response_time_ms',
        'success',
        'error_message',
        'request_headers',
        'response_headers',
        'user_agent',
        'ip_address',
        'user_id',
        'requested_at',
        'responded_at'
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'success' => 'boolean',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime'
    ];

    /**
     * Scope pour filtrer par API
     */
    public function scopeApiName($query, $apiName)
    {
        return $query->where('api_name', $apiName);
    }

    /**
     * Scope pour filtrer par succès
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope pour filtrer par échec
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope pour les requêtes récentes
     */
    public function scopeRecent($query, $minutes = 60)
    {
        if (!self::tableExists()) {
            // Retourner une requête vide qui ne retournera rien
            return $query->whereRaw('1 = 0');
        }
        return $query->where('requested_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope pour une période donnée
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('requested_at', [$startDate, $endDate]);
    }

    /**
     * Obtenir le statut de santé basé sur le code de statut
     */
    public function getHealthStatusAttribute()
    {
        if ($this->status_code >= 200 && $this->status_code < 300) {
            return 'healthy';
        } elseif ($this->status_code >= 300 && $this->status_code < 400) {
            return 'warning';
        } elseif ($this->status_code >= 400 && $this->status_code < 500) {
            return 'error';
        } else {
            return 'critical';
        }
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatusColorAttribute()
    {
        return match($this->health_status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            'critical' => 'red',
            default => 'gray'
        };
    }

    /**
     * Obtenir le temps de réponse formaté
     */
    public function getFormattedResponseTimeAttribute()
    {
        if ($this->response_time_ms < 1000) {
            return $this->response_time_ms . 'ms';
        } else {
            return round($this->response_time_ms / 1000, 2) . 's';
        }
    }

    /**
     * Obtenir la taille de la réponse formatée
     */
    public function getFormattedResponseSizeAttribute()
    {
        if (!$this->response_body) {
            return '0 B';
        }

        $size = strlen($this->response_body);
        
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / (1024 * 1024), 2) . ' MB';
        }
    }

    /**
     * Obtenir les statistiques par API
     */
    public static function getApiStats($apiName = null, $minutes = 60)
    {
        // Vérifier si la table existe avant de faire des requêtes
        if (!\Illuminate\Support\Facades\Schema::hasTable('api_monitoring')) {
            return [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'success_rate' => 0,
                'average_response_time' => 0,
                'min_response_time' => 0,
                'max_response_time' => 0
            ];
        }

        try {
            $query = self::recent($minutes);
            
            if ($apiName) {
                $query->apiName($apiName);
            }

            return [
                'total_requests' => $query->count(),
                'successful_requests' => $query->successful()->count(),
                'failed_requests' => $query->failed()->count(),
                'success_rate' => $query->count() > 0 ? round(($query->successful()->count() / $query->count()) * 100, 2) : 0,
                'average_response_time' => $query->avg('response_time_ms'),
                'min_response_time' => $query->min('response_time_ms'),
                'max_response_time' => $query->max('response_time_ms')
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoring: Error getting stats - table may not exist', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'success_rate' => 0,
                'average_response_time' => 0,
                'min_response_time' => 0,
                'max_response_time' => 0
            ];
        }
    }

    /**
     * Vérifier si la table existe
     */
    protected static function tableExists(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('api_monitoring');
    }

    /**
     * Obtenir les endpoints les plus utilisés
     */
    public static function getTopEndpoints($apiName = null, $minutes = 60, $limit = 10)
    {
        if (!self::tableExists()) {
            return collect([]);
        }

        try {
            $query = self::recent($minutes);
            
            if ($apiName) {
                $query->apiName($apiName);
            }

            return $query->selectRaw('endpoint, COUNT(*) as request_count, AVG(response_time_ms) as avg_response_time, SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count')
                        ->groupBy('endpoint')
                        ->orderBy('request_count', 'desc')
                        ->limit($limit)
                        ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoring: Error getting top endpoints', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Obtenir les codes de statut les plus fréquents
     */
    public static function getStatusCodesStats($apiName = null, $minutes = 60)
    {
        if (!self::tableExists()) {
            return collect([]);
        }

        try {
            $query = self::recent($minutes);
            
            if ($apiName) {
                $query->apiName($apiName);
            }

            return $query->selectRaw('status_code, COUNT(*) as count')
                        ->groupBy('status_code')
                        ->orderBy('count', 'desc')
                        ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoring: Error getting status codes stats', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Obtenir les données pour le graphique de performance
     */
    public static function getPerformanceChartData($apiName = null, $minutes = 60, $interval = 5)
    {
        if (!self::tableExists()) {
            return [];
        }

        try {
            $query = self::recent($minutes);
            
            if ($apiName) {
                $query->apiName($apiName);
            }

            $data = [];
            $startTime = now()->subMinutes($minutes);
            
            for ($i = 0; $i < $minutes; $i += $interval) {
                $periodStart = $startTime->copy()->addMinutes($i);
                $periodEnd = $periodStart->copy()->addMinutes($interval);
                
                $periodData = $query->whereBetween('requested_at', [$periodStart, $periodEnd])->get();
                
                $data[] = [
                    'time' => $periodStart->format('H:i'),
                    'requests' => $periodData->count(),
                    'successful' => $periodData->where('success', true)->count(),
                    'failed' => $periodData->where('success', false)->count(),
                    'avg_response_time' => $periodData->avg('response_time_ms') ?? 0
                ];
            }

            return $data;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoring: Error getting performance chart data', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Nettoyer les anciennes données
     */
    public static function cleanupOldData($days = 30)
    {
        if (!self::tableExists()) {
            return 0;
        }

        try {
            return self::where('requested_at', '<', now()->subDays($days))->delete();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoring: Error cleaning up old data', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Obtenir les alertes de performance
     */
    public static function getPerformanceAlerts($apiName = null, $minutes = 60)
    {
        if (!self::tableExists()) {
            return [];
        }

        try {
            $stats = self::getApiStats($apiName, $minutes);
            $alerts = [];

            // Alerte si le taux de succès est faible
            if ($stats['success_rate'] < 95) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Taux de succès faible: {$stats['success_rate']}%",
                    'value' => $stats['success_rate']
                ];
            }

            // Alerte si le temps de réponse moyen est élevé
            if ($stats['average_response_time'] > 5000) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Temps de réponse élevé: {$stats['average_response_time']}ms",
                    'value' => $stats['average_response_time']
                ];
            }

            // Alerte si trop de requêtes échouent
            if ($stats['failed_requests'] > 10) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "Trop de requêtes échouées: {$stats['failed_requests']}",
                    'value' => $stats['failed_requests']
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoring: Error getting performance alerts', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
