<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\StatusLog;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\Group;
use App\Services\HealthMonitoringService;
use App\Jobs\MonitorApiStatusJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;

class HealthMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $group;
    protected $chargingPoint;
    protected $healthMonitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
        $this->chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $this->group->id,
            'charge_box_id' => 'BORNE_123'
        ]);
        $this->healthMonitoringService = app(HealthMonitoringService::class);
    }

    /** @test */
    public function it_can_log_status_for_api()
    {
        StatusLog::markOnline(
            'api',
            null,
            'Evon API',
            150,
            ['url' => 'http://localhost/api/health']
        );

        $this->assertDatabaseHas('status_logs', [
            'entity_type' => 'api',
            'entity_name' => 'Evon API',
            'status' => 'online',
            'health_status' => 'healthy',
            'response_time_ms' => 150
        ]);
    }

    /** @test */
    public function it_can_log_status_for_charger()
    {
        StatusLog::markOnline(
            'charger',
            $this->chargingPoint->id,
            'BORNE_123',
            200,
            ['charging_point_id' => $this->chargingPoint->id]
        );

        $this->assertDatabaseHas('status_logs', [
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'online',
            'health_status' => 'healthy',
            'response_time_ms' => 200
        ]);
    }

    /** @test */
    public function it_can_log_offline_status()
    {
        StatusLog::markOffline(
            'charger',
            $this->chargingPoint->id,
            'BORNE_123',
            'Connection timeout',
            ['charging_point_id' => $this->chargingPoint->id]
        );

        $this->assertDatabaseHas('status_logs', [
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'offline',
            'health_status' => 'critical',
            'error_message' => 'Connection timeout'
        ]);
    }

    /** @test */
    public function it_can_log_degraded_status()
    {
        StatusLog::markDegraded(
            'charger',
            $this->chargingPoint->id,
            'BORNE_123',
            5000,
            'Slow response',
            ['charging_point_id' => $this->chargingPoint->id]
        );

        $this->assertDatabaseHas('status_logs', [
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'degraded',
            'health_status' => 'warning',
            'response_time_ms' => 5000,
            'error_message' => 'Slow response'
        ]);
    }

    /** @test */
    public function it_can_get_entity_stats()
    {
        // Créer des logs de test
        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'status' => 'online',
            'response_time_ms' => 100,
            'checked_at' => now()->subMinutes(30)
        ]);

        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'status' => 'offline',
            'response_time_ms' => 0,
            'checked_at' => now()->subMinutes(30)
        ]);

        $stats = StatusLog::getEntityStats('charger', $this->chargingPoint->id, 60);

        $this->assertEquals(2, $stats['total_checks']);
        $this->assertEquals(1, $stats['online_count']);
        $this->assertEquals(1, $stats['offline_count']);
        $this->assertEquals(50.0, $stats['uptime_percentage']);
        $this->assertEquals(100, $stats['average_response_time']);
    }

    /** @test */
    public function it_can_get_current_entity_status()
    {
        // Créer des logs de test
        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'online',
            'checked_at' => now()->subMinutes(5)
        ]);

        $currentStatus = StatusLog::getCurrentEntityStatus('charger');

        $this->assertCount(1, $currentStatus);
        $this->assertEquals('online', $currentStatus->first()->status);
    }

    /** @test */
    public function it_can_get_entity_history()
    {
        // Créer des logs d'historique
        StatusLog::factory()->count(5)->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'checked_at' => now()->subHours(2)
        ]);

        $history = StatusLog::getEntityHistory('charger', $this->chargingPoint->id, 24);

        $this->assertCount(5, $history);
    }

    /** @test */
    public function it_can_get_status_alerts()
    {
        // Créer des logs avec des problèmes
        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'offline',
            'checked_at' => now()->subMinutes(30)
        ]);

        $alerts = StatusLog::getStatusAlerts('charger', 60);

        $this->assertCount(1, $alerts);
        $this->assertEquals('critical', $alerts[0]['type']);
        $this->assertEquals('BORNE_123', $alerts[0]['entity_name']);
    }

    /** @test */
    public function it_can_get_performance_metrics()
    {
        // Créer des logs de test
        StatusLog::factory()->count(10)->create([
            'entity_type' => 'charger',
            'status' => 'online',
            'response_time_ms' => 100,
            'checked_at' => now()->subMinutes(30)
        ]);

        $metrics = StatusLog::getPerformanceMetrics('charger', 60);

        $this->assertEquals(1, $metrics['total_entities']);
        $this->assertEquals(1, $metrics['online_entities']);
        $this->assertEquals(0, $metrics['offline_entities']);
        $this->assertEquals(100.0, $metrics['overall_uptime']);
        $this->assertEquals(100, $metrics['average_response_time']);
    }

    /** @test */
    public function it_can_cleanup_old_logs()
    {
        // Créer des logs anciens
        StatusLog::factory()->create([
            'checked_at' => now()->subDays(35)
        ]);

        // Créer des logs récents
        StatusLog::factory()->create([
            'checked_at' => now()->subDays(5)
        ]);

        $deleted = StatusLog::cleanupOldLogs(30);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseCount('status_logs', 1);
    }

    /** @test */
    public function it_can_get_overall_health_status()
    {
        // Créer des données de test
        StatusLog::factory()->create([
            'entity_type' => 'api',
            'status' => 'online',
            'checked_at' => now()->subMinutes(30)
        ]);

        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'status' => 'online',
            'checked_at' => now()->subMinutes(30)
        ]);

        $overallHealth = $this->healthMonitoringService->getOverallHealthStatus();

        $this->assertArrayHasKey('apis', $overallHealth);
        $this->assertArrayHasKey('chargers', $overallHealth);
        $this->assertArrayHasKey('overall_status', $overallHealth);
    }

    /** @test */
    public function it_can_get_charger_status()
    {
        // Créer des logs de chargeurs
        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'online',
            'checked_at' => now()->subMinutes(5)
        ]);

        $chargerStatus = $this->healthMonitoringService->getChargerStatus();

        $this->assertArrayHasKey('chargers', $chargerStatus);
        $this->assertArrayHasKey('metrics', $chargerStatus);
        $this->assertArrayHasKey('total_chargers', $chargerStatus);
        $this->assertArrayHasKey('uptime_percentage', $chargerStatus);
    }

    /** @test */
    public function it_can_get_api_status()
    {
        // Créer des logs d'API
        StatusLog::factory()->create([
            'entity_type' => 'api',
            'entity_name' => 'Evon API',
            'status' => 'online',
            'checked_at' => now()->subMinutes(5)
        ]);

        $apiStatus = $this->healthMonitoringService->getApiStatus();

        $this->assertArrayHasKey('apis', $apiStatus);
        $this->assertArrayHasKey('metrics', $apiStatus);
        $this->assertArrayHasKey('total_apis', $apiStatus);
        $this->assertArrayHasKey('uptime_percentage', $apiStatus);
    }

    /** @test */
    public function it_can_get_status_alerts_duplicate()
    {
        // Créer des logs avec des problèmes
        StatusLog::factory()->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'entity_name' => 'BORNE_123',
            'status' => 'offline',
            'checked_at' => now()->subMinutes(30)
        ]);

        $alerts = $this->healthMonitoringService->getStatusAlerts();

        $this->assertArrayHasKey('alerts', $alerts);
        $this->assertArrayHasKey('grouped_alerts', $alerts);
        $this->assertArrayHasKey('total_alerts', $alerts);
    }

    /** @test */
    public function it_can_check_charger_health_manually()
    {
        // Mock successful Steve API response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123/status' => Http::response([
                'success' => true,
                'status' => 'Available'
            ], 200)
        ]);

        $result = $this->healthMonitoringService->checkChargerHealth($this->chargingPoint);

        $this->assertTrue($result['success']);
        $this->assertEquals('online', $result['status']);
        $this->assertEquals('healthy', $result['health_status']);
        $this->assertArrayHasKey('response_time_ms', $result);
    }

    /** @test */
    public function it_can_check_api_health_manually()
    {
        // Mock successful API response
        Http::fake([
            config('app.url') . '/api/health' => Http::response([
                'status' => 'ok'
            ], 200)
        ]);

        $result = $this->healthMonitoringService->checkApiHealth('evon');

        $this->assertTrue($result['success']);
        $this->assertEquals('online', $result['status']);
        $this->assertEquals('healthy', $result['health_status']);
        $this->assertArrayHasKey('response_time_ms', $result);
    }

    /** @test */
    public function it_can_get_entity_history_from_service()
    {
        // Créer des logs d'historique
        StatusLog::factory()->count(3)->create([
            'entity_type' => 'charger',
            'entity_id' => $this->chargingPoint->id,
            'checked_at' => now()->subHours(2)
        ]);

        $history = $this->healthMonitoringService->getEntityHistory('charger', $this->chargingPoint->id, 24);

        $this->assertArrayHasKey('entity_type', $history);
        $this->assertArrayHasKey('entity_id', $history);
        $this->assertArrayHasKey('history', $history);
        $this->assertArrayHasKey('total_checks', $history);
        $this->assertArrayHasKey('uptime_percentage', $history);
    }

    /** @test */
    public function it_can_get_performance_metrics_from_service()
    {
        // Créer des logs de test
        StatusLog::factory()->count(5)->create([
            'entity_type' => 'charger',
            'status' => 'online',
            'response_time_ms' => 150,
            'checked_at' => now()->subMinutes(30)
        ]);

        $metrics = $this->healthMonitoringService->getPerformanceMetrics('charger', 60);

        $this->assertArrayHasKey('metrics', $metrics);
        $this->assertArrayHasKey('alerts', $metrics);
        $this->assertArrayHasKey('entity_type', $metrics);
        $this->assertArrayHasKey('time_range', $metrics);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        // Mettre des données en cache
        Cache::put('health_monitoring_test', 'test_data', 300);
        
        $this->healthMonitoringService->clearCache();
        
        $this->assertFalse(Cache::has('health_monitoring_test'));
    }

    /** @test */
    public function it_can_dispatch_monitor_job()
    {
        Queue::fake();

        MonitorApiStatusJob::dispatch();

        Queue::assertPushed(MonitorApiStatusJob::class);
    }

    /** @test */
    public function it_can_handle_job_failure()
    {
        $job = new MonitorApiStatusJob();
        $exception = new \Exception('Test exception');

        $job->failed($exception);

        $this->assertDatabaseHas('status_logs', [
            'entity_type' => 'service',
            'entity_name' => 'MonitorApiStatusJob',
            'status' => 'offline',
            'health_status' => 'critical'
        ]);
    }

    /** @test */
    public function it_can_get_detailed_stats()
    {
        // Créer des logs de test
        StatusLog::factory()->count(10)->create([
            'entity_type' => 'charger',
            'status' => 'online',
            'response_time_ms' => 100,
            'checked_at' => now()->subMinutes(30)
        ]);

        $stats = $this->healthMonitoringService->getDetailedStats('charger', 60);

        $this->assertArrayHasKey('entity_stats', $stats);
        $this->assertArrayHasKey('performance_metrics', $stats);
        $this->assertArrayHasKey('alerts', $stats);
        $this->assertArrayHasKey('entity_type', $stats);
        $this->assertArrayHasKey('time_range', $stats);
    }

    /** @test */
    public function it_can_handle_charger_health_check_failure()
    {
        // Mock failed Steve API response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123/status' => Http::response([
                'success' => false,
                'error' => 'Charger not found'
            ], 404)
        ]);

        $result = $this->healthMonitoringService->checkChargerHealth($this->chargingPoint);

        $this->assertFalse($result['success']);
        $this->assertEquals('offline', $result['status']);
        $this->assertEquals('critical', $result['health_status']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_handle_api_health_check_failure()
    {
        // Mock failed API response
        Http::fake([
            config('app.url') . '/api/health' => Http::response([
                'error' => 'Service unavailable'
            ], 503)
        ]);

        $result = $this->healthMonitoringService->checkApiHealth('evon');

        $this->assertFalse($result['success']);
        $this->assertEquals('degraded', $result['status']);
        $this->assertEquals('warning', $result['health_status']);
        $this->assertArrayHasKey('error', $result);
    }
}
