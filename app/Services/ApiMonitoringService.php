<?php

namespace App\Services;

use App\Models\ApiMonitoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ApiMonitoringService
{
    protected $enabled = true;
    protected $retentionDays = 30;

    public function __construct()
    {
        $this->enabled = config('monitoring.enabled', true);
        $this->retentionDays = config('monitoring.retention_days', 30);
    }

    /**
     * Enregistrer une requête API
     */
    public function logApiRequest(
        string $apiName,
        string $endpoint,
        string $method = 'GET',
        int $statusCode = 200,
        ?string $responseBody = null,
        int $responseTimeMs = 0,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $requestHeaders = null,
        ?array $responseHeaders = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
        ?string $userId = null
    ): ApiMonitoring {
        if (!$this->enabled) {
            return new ApiMonitoring();
        }

        try {
            $monitoring = ApiMonitoring::create([
                'api_name' => $apiName,
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
                'response_body' => $this->truncateResponseBody($responseBody),
                'response_time_ms' => $responseTimeMs,
                'success' => $success,
                'error_message' => $errorMessage,
                'request_headers' => $requestHeaders,
                'response_headers' => $responseHeaders,
                'user_agent' => $userAgent,
                'ip_address' => $ipAddress,
                'user_id' => $userId,
                'requested_at' => now(),
                'responded_at' => now()
            ]);

            Log::info('API Monitoring: Request logged', [
                'api_name' => $apiName,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'response_time_ms' => $responseTimeMs,
                'success' => $success
            ]);

            return $monitoring;

        } catch (\Exception $e) {
            Log::error('API Monitoring: Failed to log request', [
                'api_name' => $apiName,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return new ApiMonitoring();
        }
    }

    /**
     * Enregistrer une requête depuis un objet Request
     */
    public function logFromRequest(
        Request $request,
        string $apiName,
        string $endpoint,
        int $statusCode = 200,
        ?string $responseBody = null,
        int $responseTimeMs = 0,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $responseHeaders = null
    ): ApiMonitoring {
        return $this->logApiRequest(
            $apiName,
            $endpoint,
            $request->method(),
            $statusCode,
            $responseBody,
            $responseTimeMs,
            $success,
            $errorMessage,
            $request->headers->all(),
            $responseHeaders,
            $request->userAgent(),
            $request->ip(),
            $request->user() ? $request->user()->id : null
        );
    }

    /**
     * Enregistrer une requête HTTP externe
     */
    public function logHttpRequest(
        string $apiName,
        string $endpoint,
        string $method = 'GET',
        array $options = [],
        ?string $userId = null
    ): ApiMonitoring {
        $startTime = microtime(true);
        
        try {
            $response = Http::timeout(30)->send($method, $endpoint, $options);
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000);

            return $this->logApiRequest(
                $apiName,
                $endpoint,
                $method,
                $response->status(),
                $response->body(),
                $responseTimeMs,
                $response->successful(),
                $response->successful() ? null : $response->body(),
                $options['headers'] ?? null,
                $response->headers(),
                null,
                null,
                $userId
            );

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000);

            return $this->logApiRequest(
                $apiName,
                $endpoint,
                $method,
                0,
                null,
                $responseTimeMs,
                false,
                $e->getMessage(),
                $options['headers'] ?? null,
                null,
                null,
                null,
                $userId
            );
        }
    }

    /**
     * Obtenir les statistiques de monitoring
     */
    public function getMonitoringStats($apiName = null, $minutes = 60)
    {
        return ApiMonitoring::getApiStats($apiName, $minutes);
    }

    /**
     * Obtenir les données du tableau de bord
     */
    public function getDashboardData($apiName = null, $minutes = 60)
    {
        $stats = $this->getMonitoringStats($apiName, $minutes);
        $topEndpoints = ApiMonitoring::getTopEndpoints($apiName, $minutes, 10);
        $statusCodes = ApiMonitoring::getStatusCodesStats($apiName, $minutes);
        $performanceData = ApiMonitoring::getPerformanceChartData($apiName, $minutes);
        $alerts = ApiMonitoring::getPerformanceAlerts($apiName, $minutes);

        return [
            'stats' => $stats,
            'top_endpoints' => $topEndpoints,
            'status_codes' => $statusCodes,
            'performance_data' => $performanceData,
            'alerts' => $alerts,
            'api_name' => $apiName,
            'time_range' => $minutes
        ];
    }

    /**
     * Obtenir les requêtes récentes
     */
    public function getRecentRequests($apiName = null, $limit = 50)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('api_monitoring')) {
            return collect([]);
        }

        try {
            $query = ApiMonitoring::recent(60);
            
            if ($apiName) {
                $query->apiName($apiName);
            }

            return $query->orderBy('requested_at', 'desc')
                        ->limit($limit)
                        ->get();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ApiMonitoringService: Error getting recent requests', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Obtenir les données de santé des API
     */
    public function getApiHealthStatus()
    {
        $apis = ['evon', 'steve'];
        $healthStatus = [];

        foreach ($apis as $api) {
            $stats = $this->getMonitoringStats($api, 5); // Dernières 5 minutes
            
            $healthStatus[$api] = [
                'name' => strtoupper($api),
                'status' => $this->determineHealthStatus($stats),
                'stats' => $stats,
                'last_check' => now()
            ];
        }

        return $healthStatus;
    }

    /**
     * Déterminer le statut de santé
     */
    protected function determineHealthStatus($stats)
    {
        if ($stats['total_requests'] == 0) {
            return 'unknown';
        }

        if ($stats['success_rate'] >= 99) {
            return 'excellent';
        } elseif ($stats['success_rate'] >= 95) {
            return 'good';
        } elseif ($stats['success_rate'] >= 90) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Obtenir la couleur du statut de santé
     */
    public function getHealthColor($status)
    {
        return match($status) {
            'excellent' => 'green',
            'good' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            'unknown' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Obtenir l'icône du statut de santé
     */
    public function getHealthIcon($status)
    {
        return match($status) {
            'excellent' => 'check-circle',
            'good' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'critical' => 'times-circle',
            'unknown' => 'question-circle',
            default => 'question-circle'
        };
    }

    /**
     * Nettoyer les anciennes données
     */
    public function cleanupOldData()
    {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $deleted = ApiMonitoring::cleanupOldData($this->retentionDays);
            
            Log::info('API Monitoring: Cleaned up old data', [
                'deleted_records' => $deleted,
                'retention_days' => $this->retentionDays
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('API Monitoring: Failed to cleanup old data', [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Tronquer le corps de la réponse si trop long
     */
    protected function truncateResponseBody(?string $responseBody, int $maxLength = 10000)
    {
        if (!$responseBody) {
            return null;
        }

        if (strlen($responseBody) <= $maxLength) {
            return $responseBody;
        }

        return substr($responseBody, 0, $maxLength) . '... [TRUNCATED]';
    }

    /**
     * Activer/désactiver le monitoring
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Vérifier si le monitoring est activé
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Obtenir les métriques en temps réel
     */
    public function getRealTimeMetrics($apiName = null)
    {
        $query = ApiMonitoring::recent(1); // Dernière minute
        
        if ($apiName) {
            $query->apiName($apiName);
        }

        $requests = $query->get();

        return [
            'requests_per_minute' => $requests->count(),
            'success_rate' => $requests->count() > 0 ? round(($requests->where('success', true)->count() / $requests->count()) * 100, 2) : 0,
            'average_response_time' => $requests->avg('response_time_ms') ?? 0,
            'error_count' => $requests->where('success', false)->count(),
            'last_request' => $requests->sortByDesc('requested_at')->first()
        ];
    }

    /**
     * Exporter les données de monitoring
     */
    public function exportData($apiName = null, $startDate = null, $endDate = null, $format = 'json')
    {
        $query = ApiMonitoring::query();
        
        if ($apiName) {
            $query->apiName($apiName);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        } else {
            $query->recent(24 * 60); // Dernières 24 heures
        }

        $data = $query->orderBy('requested_at', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportToCsv($data);
        }

        return $data->toArray();
    }

    /**
     * Exporter en CSV
     */
    protected function exportToCsv($data)
    {
        $csv = "API Name,Endpoint,Method,Status Code,Response Time (ms),Success,Error Message,Requested At\n";
        
        foreach ($data as $record) {
            $csv .= sprintf(
                "%s,%s,%s,%d,%d,%s,%s,%s\n",
                $record->api_name,
                $record->endpoint,
                $record->method,
                $record->status_code,
                $record->response_time_ms,
                $record->success ? 'Yes' : 'No',
                $record->error_message ?? '',
                $record->requested_at->format('Y-m-d H:i:s')
            );
        }

        return $csv;
    }
}
