<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ApiMonitoring;
use App\Models\User;
use App\Services\ApiMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->monitoringService = app(ApiMonitoringService::class);
    }

    /** @test */
    public function it_can_log_api_request()
    {
        $this->monitoringService->logApiRequest(
            'evon',
            '/api/test',
            'GET',
            200,
            '{"success": true}',
            150,
            true,
            null,
            ['Content-Type' => 'application/json'],
            ['Content-Type' => 'application/json'],
            'Test Agent',
            '127.0.0.1',
            $this->user->id
        );

        $this->assertDatabaseHas('api_monitoring', [
            'api_name' => 'evon',
            'endpoint' => '/api/test',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 150,
            'success' => true,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_log_failed_api_request()
    {
        $this->monitoringService->logApiRequest(
            'steve',
            '/api/connect',
            'POST',
            500,
            null,
            5000,
            false,
            'Connection timeout',
            ['Content-Type' => 'application/json'],
            null,
            'Test Agent',
            '127.0.0.1',
            $this->user->id
        );

        $this->assertDatabaseHas('api_monitoring', [
            'api_name' => 'steve',
            'endpoint' => '/api/connect',
            'method' => 'POST',
            'status_code' => 500,
            'response_time_ms' => 5000,
            'success' => false,
            'error_message' => 'Connection timeout'
        ]);
    }

    /** @test */
    public function it_can_get_monitoring_stats()
    {
        // Créer des données de test
        ApiMonitoring::factory()->create([
            'api_name' => 'evon',
            'success' => true,
            'response_time_ms' => 100,
            'requested_at' => now()->subMinutes(30)
        ]);

        ApiMonitoring::factory()->create([
            'api_name' => 'evon',
            'success' => false,
            'response_time_ms' => 5000,
            'requested_at' => now()->subMinutes(30)
        ]);

        $stats = $this->monitoringService->getMonitoringStats('evon', 60);

        $this->assertEquals(2, $stats['total_requests']);
        $this->assertEquals(1, $stats['successful_requests']);
        $this->assertEquals(1, $stats['failed_requests']);
        $this->assertEquals(50.0, $stats['success_rate']);
        $this->assertEquals(2550, $stats['average_response_time']);
    }

    /** @test */
    public function it_can_get_dashboard_data()
    {
        // Créer des données de test
        ApiMonitoring::factory()->count(10)->create([
            'api_name' => 'evon',
            'success' => true,
            'requested_at' => now()->subMinutes(30)
        ]);

        $dashboardData = $this->monitoringService->getDashboardData('evon', 60);

        $this->assertArrayHasKey('stats', $dashboardData);
        $this->assertArrayHasKey('top_endpoints', $dashboardData);
        $this->assertArrayHasKey('status_codes', $dashboardData);
        $this->assertArrayHasKey('performance_data', $dashboardData);
        $this->assertArrayHasKey('alerts', $dashboardData);
    }

    /** @test */
    public function it_can_get_recent_requests()
    {
        // Créer des données de test
        ApiMonitoring::factory()->count(5)->create([
            'api_name' => 'evon',
            'requested_at' => now()->subMinutes(10)
        ]);

        $requests = $this->monitoringService->getRecentRequests('evon', 10);

        $this->assertCount(5, $requests);
    }

    /** @test */
    public function it_can_get_api_health_status()
    {
        // Créer des données de test
        ApiMonitoring::factory()->create([
            'api_name' => 'evon',
            'success' => true,
            'requested_at' => now()->subMinutes(5)
        ]);

        $healthStatus = $this->monitoringService->getApiHealthStatus();

        $this->assertArrayHasKey('evon', $healthStatus);
        $this->assertArrayHasKey('steve', $healthStatus);
        $this->assertArrayHasKey('status', $healthStatus['evon']);
        $this->assertArrayHasKey('stats', $healthStatus['evon']);
    }

    /** @test */
    public function it_can_export_data()
    {
        // Créer des données de test
        ApiMonitoring::factory()->count(5)->create([
            'api_name' => 'evon',
            'requested_at' => now()->subMinutes(30)
        ]);

        $data = $this->monitoringService->exportData('evon', null, null, 'json');

        $this->assertIsArray($data);
        $this->assertCount(5, $data);
    }

    /** @test */
    public function it_can_export_data_as_csv()
    {
        // Créer des données de test
        ApiMonitoring::factory()->create([
            'api_name' => 'evon',
            'endpoint' => '/api/test',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 100,
            'success' => true,
            'requested_at' => now()
        ]);

        $csv = $this->monitoringService->exportData('evon', null, null, 'csv');

        $this->assertStringContainsString('API Name,Endpoint,Method,Status Code', $csv);
        $this->assertStringContainsString('evon,/api/test,GET,200', $csv);
    }

    /** @test */
    public function it_can_cleanup_old_data()
    {
        // Créer des données anciennes
        ApiMonitoring::factory()->create([
            'requested_at' => now()->subDays(35)
        ]);

        // Créer des données récentes
        ApiMonitoring::factory()->create([
            'requested_at' => now()->subDays(5)
        ]);

        $deleted = $this->monitoringService->cleanupOldData();

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseCount('api_monitoring', 1);
    }

    /** @test */
    public function it_can_get_real_time_metrics()
    {
        // Créer des données récentes
        ApiMonitoring::factory()->create([
            'api_name' => 'evon',
            'success' => true,
            'response_time_ms' => 100,
            'requested_at' => now()->subSeconds(30)
        ]);

        $metrics = $this->monitoringService->getRealTimeMetrics('evon');

        $this->assertArrayHasKey('requests_per_minute', $metrics);
        $this->assertArrayHasKey('success_rate', $metrics);
        $this->assertArrayHasKey('average_response_time', $metrics);
        $this->assertArrayHasKey('error_count', $metrics);
    }

    /** @test */
    public function it_can_determine_health_status()
    {
        // Test avec un taux de succès élevé
        ApiMonitoring::factory()->count(10)->create([
            'api_name' => 'evon',
            'success' => true,
            'requested_at' => now()->subMinutes(5)
        ]);

        $healthStatus = $this->monitoringService->getApiHealthStatus();

        $this->assertContains($healthStatus['evon']['status'], ['excellent', 'good']);
    }

    /** @test */
    public function it_can_get_performance_alerts()
    {
        // Créer des données avec un taux de succès faible
        ApiMonitoring::factory()->count(10)->create([
            'api_name' => 'evon',
            'success' => false,
            'response_time_ms' => 10000,
            'requested_at' => now()->subMinutes(5)
        ]);

        $dashboardData = $this->monitoringService->getDashboardData('evon', 60);

        $this->assertArrayHasKey('alerts', $dashboardData);
        $this->assertNotEmpty($dashboardData['alerts']);
    }

    /** @test */
    public function it_can_log_from_request_object()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $this->monitoringService->logFromRequest(
            $request,
            'evon',
            '/api/test',
            200,
            '{"success": true}',
            150,
            true
        );

        $this->assertDatabaseHas('api_monitoring', [
            'api_name' => 'evon',
            'endpoint' => '/api/test',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_log_http_request()
    {
        Http::fake([
            'http://example.com/api/test' => Http::response(['success' => true], 200)
        ]);

        $this->monitoringService->logHttpRequest(
            'evon',
            'http://example.com/api/test',
            'GET',
            [],
            $this->user->id
        );

        $this->assertDatabaseHas('api_monitoring', [
            'api_name' => 'evon',
            'endpoint' => 'http://example.com/api/test',
            'method' => 'GET',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_handle_http_request_failure()
    {
        Http::fake([
            'http://example.com/api/test' => Http::response(['error' => 'Not found'], 404)
        ]);

        $this->monitoringService->logHttpRequest(
            'evon',
            'http://example.com/api/test',
            'GET',
            [],
            $this->user->id
        );

        $this->assertDatabaseHas('api_monitoring', [
            'api_name' => 'evon',
            'endpoint' => 'http://example.com/api/test',
            'method' => 'GET',
            'success' => false,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_get_top_endpoints()
    {
        // Créer des données avec différents endpoints
        ApiMonitoring::factory()->count(5)->create([
            'api_name' => 'evon',
            'endpoint' => '/api/users',
            'requested_at' => now()->subMinutes(30)
        ]);

        ApiMonitoring::factory()->count(3)->create([
            'api_name' => 'evon',
            'endpoint' => '/api/posts',
            'requested_at' => now()->subMinutes(30)
        ]);

        $topEndpoints = ApiMonitoring::getTopEndpoints('evon', 60, 10);

        $this->assertCount(2, $topEndpoints);
        $this->assertEquals('/api/users', $topEndpoints->first()->endpoint);
        $this->assertEquals(5, $topEndpoints->first()->request_count);
    }

    /** @test */
    public function it_can_get_status_codes_stats()
    {
        // Créer des données avec différents codes de statut
        ApiMonitoring::factory()->count(3)->create([
            'api_name' => 'evon',
            'status_code' => 200,
            'requested_at' => now()->subMinutes(30)
        ]);

        ApiMonitoring::factory()->count(2)->create([
            'api_name' => 'evon',
            'status_code' => 404,
            'requested_at' => now()->subMinutes(30)
        ]);

        $statusCodes = ApiMonitoring::getStatusCodesStats('evon', 60);

        $this->assertCount(2, $statusCodes);
        $this->assertEquals(200, $statusCodes->first()->status_code);
        $this->assertEquals(3, $statusCodes->first()->count);
    }

    /** @test */
    public function it_can_get_performance_chart_data()
    {
        // Créer des données sur différentes périodes
        ApiMonitoring::factory()->create([
            'api_name' => 'evon',
            'success' => true,
            'response_time_ms' => 100,
            'requested_at' => now()->subMinutes(10)
        ]);

        $chartData = ApiMonitoring::getPerformanceChartData('evon', 60, 5);

        $this->assertIsArray($chartData);
        $this->assertNotEmpty($chartData);
    }

    /** @test */
    public function it_can_truncate_response_body()
    {
        $longResponse = str_repeat('a', 15000);
        
        $this->monitoringService->logApiRequest(
            'evon',
            '/api/test',
            'GET',
            200,
            $longResponse,
            100,
            true
        );

        $monitoring = ApiMonitoring::latest()->first();
        
        $this->assertLessThan(15000, strlen($monitoring->response_body));
        $this->assertStringContainsString('[TRUNCATED]', $monitoring->response_body);
    }

    /** @test */
    public function it_can_disable_monitoring()
    {
        $this->monitoringService->setEnabled(false);
        
        $this->monitoringService->logApiRequest(
            'evon',
            '/api/test',
            'GET',
            200,
            '{"success": true}',
            100,
            true
        );

        $this->assertDatabaseCount('api_monitoring', 0);
    }
}
