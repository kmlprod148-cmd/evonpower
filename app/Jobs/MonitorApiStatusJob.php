<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ChargingPoint;
use App\Models\StatusLog;
use App\Services\SteVeApiService;
use App\Services\HealthMonitoringService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class MonitorApiStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $healthMonitoringService;
    protected $steveApiService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->healthMonitoringService = app(HealthMonitoringService::class);
        $this->steveApiService = app(SteVeApiService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('MonitorApiStatusJob: Starting health monitoring check');

        try {
            // Monitorer les APIs principales
            $this->monitorApis();
            
            // Monitorer les chargeurs Steve
            $this->monitorChargers();
            
            // Nettoyer les anciens logs
            $this->cleanupOldLogs();

            Log::info('MonitorApiStatusJob: Health monitoring check completed successfully');

        } catch (Exception $e) {
            Log::error('MonitorApiStatusJob: Failed to complete health monitoring', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Monitorer les APIs principales
     */
    protected function monitorApis(): void
    {
        $apis = [
            'evon' => [
                'name' => 'Evon API',
                'url' => config('app.url') . '/api/health',
                'timeout' => 10
            ],
            'steve' => [
                'name' => 'Steve API',
                'url' => config('steve.api_url', 'http://158.69.27.239:8080') . '/api/v1/health',
                'timeout' => 15
            ]
        ];

        foreach ($apis as $apiKey => $api) {
            $this->checkApiHealth($apiKey, $api);
        }
    }

    /**
     * Vérifier la santé d'une API
     */
    protected function checkApiHealth(string $apiKey, array $api): void
    {
        $startTime = microtime(true);
        
        try {
            Log::debug('MonitorApiStatusJob: Checking API health', [
                'api' => $apiKey,
                'url' => $api['url']
            ]);

            $response = Http::timeout($api['timeout'])
                ->get($api['url']);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            if ($response->successful()) {
                StatusLog::markOnline(
                    'api',
                    null,
                    $api['name'],
                    $responseTime,
                    [
                        'url' => $api['url'],
                        'status_code' => $response->status(),
                        'response_size' => strlen($response->body())
                    ]
                );

                Log::debug('MonitorApiStatusJob: API health check successful', [
                    'api' => $apiKey,
                    'response_time' => $responseTime,
                    'status_code' => $response->status()
                ]);
            } else {
                StatusLog::markDegraded(
                    'api',
                    null,
                    $api['name'],
                    $responseTime,
                    "HTTP {$response->status()}: {$response->body()}",
                    [
                        'url' => $api['url'],
                        'status_code' => $response->status()
                    ]
                );

                Log::warning('MonitorApiStatusJob: API health check failed', [
                    'api' => $apiKey,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
            }

        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            StatusLog::markOffline(
                'api',
                null,
                $api['name'],
                $e->getMessage(),
                [
                    'url' => $api['url'],
                    'error_type' => get_class($e)
                ]
            );

            Log::error('MonitorApiStatusJob: API health check exception', [
                'api' => $apiKey,
                'error' => $e->getMessage(),
                'response_time' => $responseTime
            ]);
        }
    }

    /**
     * Monitorer les chargeurs
     */
    protected function monitorChargers(): void
    {
        $chargingPoints = ChargingPoint::with(['group', 'group.partner'])
            ->whereNotNull('charge_box_id')
            ->get();

        Log::info('MonitorApiStatusJob: Monitoring chargers', [
            'total_chargers' => $chargingPoints->count()
        ]);

        foreach ($chargingPoints as $chargingPoint) {
            $this->checkChargerHealth($chargingPoint);
        }
    }

    /**
     * Vérifier la santé d'un chargeur
     */
    protected function checkChargerHealth(ChargingPoint $chargingPoint): void
    {
        $startTime = microtime(true);
        $chargerId = $chargingPoint->charge_box_id ?: "BORNE_{$chargingPoint->id}";
        
        try {
            Log::debug('MonitorApiStatusJob: Checking charger health', [
                'charger_id' => $chargerId,
                'charging_point_id' => $chargingPoint->id
            ]);

            // Vérifier le statut du chargeur via l'API Steve
            $statusResult = $this->steveApiService->getChargerStatus($chargerId);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            if ($statusResult['success']) {
                $isOnline = $this->determineChargerOnlineStatus($statusResult['status'] ?? []);
                
                if ($isOnline) {
                    StatusLog::markOnline(
                        'charger',
                        $chargingPoint->id,
                        $chargerId,
                        $responseTime,
                        [
                            'charging_point_id' => $chargingPoint->id,
                            'group_id' => $chargingPoint->group_id,
                            'partner_id' => $chargingPoint->group?->partner_id,
                            'status_data' => $statusResult['status']
                        ]
                    );

                    Log::debug('MonitorApiStatusJob: Charger is online', [
                        'charger_id' => $chargerId,
                        'response_time' => $responseTime
                    ]);
                } else {
                    StatusLog::markDegraded(
                        'charger',
                        $chargingPoint->id,
                        $chargerId,
                        $responseTime,
                        'Charger not available',
                        [
                            'charging_point_id' => $chargingPoint->id,
                            'status_data' => $statusResult['status']
                        ]
                    );

                    Log::warning('MonitorApiStatusJob: Charger is degraded', [
                        'charger_id' => $chargerId,
                        'status' => $statusResult['status']
                    ]);
                }
            } else {
                StatusLog::markOffline(
                    'charger',
                    $chargingPoint->id,
                    $chargerId,
                    $statusResult['error'] ?? 'Unknown error',
                    [
                        'charging_point_id' => $chargingPoint->id,
                        'error_code' => $statusResult['error_code'] ?? null
                    ]
                );

                Log::warning('MonitorApiStatusJob: Charger is offline', [
                    'charger_id' => $chargerId,
                    'error' => $statusResult['error']
                ]);
            }

        } catch (Exception $e) {
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);

            StatusLog::markOffline(
                'charger',
                $chargingPoint->id,
                $chargerId,
                $e->getMessage(),
                [
                    'charging_point_id' => $chargingPoint->id,
                    'error_type' => get_class($e)
                ]
            );

            Log::error('MonitorApiStatusJob: Charger health check exception', [
                'charger_id' => $chargerId,
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'response_time' => $responseTime
            ]);
        }
    }

    /**
     * Déterminer si un chargeur est en ligne
     */
    protected function determineChargerOnlineStatus($statusData): bool
    {
        if (empty($statusData)) {
            return false;
        }

        $statusString = is_array($statusData) ? json_encode($statusData) : (string) $statusData;
        $statusString = strtolower($statusString);

        $onlineStatuses = [
            'available',
            'connected',
            'online',
            'ready',
            'idle',
            'charging',
            'occupied'
        ];

        foreach ($onlineStatuses as $status) {
            if (strpos($statusString, $status) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nettoyer les anciens logs
     */
    protected function cleanupOldLogs(): void
    {
        try {
            $retentionDays = config('monitoring.retention_days', 30);
            $deleted = StatusLog::cleanupOldLogs($retentionDays);

            if ($deleted > 0) {
                Log::info('MonitorApiStatusJob: Cleaned up old status logs', [
                    'deleted_count' => $deleted,
                    'retention_days' => $retentionDays
                ]);
            }

        } catch (Exception $e) {
            Log::error('MonitorApiStatusJob: Failed to cleanup old logs', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('MonitorApiStatusJob: Job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Enregistrer l'échec du job
        StatusLog::markOffline(
            'service',
            null,
            'MonitorApiStatusJob',
            $exception->getMessage(),
            [
                'job_class' => self::class,
                'failed_at' => now()->toISOString()
            ]
        );
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['monitoring', 'health-check', 'api-status'];
    }

    /**
     * Get the number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Get the number of seconds the job can run before timing out.
     */
    public function timeout(): int
    {
        return 300; // 5 minutes
    }
}
