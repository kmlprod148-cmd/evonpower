<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\SteveApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SteveApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $steveApiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->steveApiService = new SteveApiService();
    }

    /** @test */
    public function it_can_connect_charger_successfully()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => 'BORNE_123',
                'connected' => true,
                'timestamp' => now()->toISOString()
            ], 200)
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertTrue($result['connected']);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_handles_charger_connection_failure()
    {
        // Mock failed HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => false,
                'error' => 'Charger not found'
            ], 404)
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertFalse($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertFalse($result['connected']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_start_charge_successfully()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/charges/start' => Http::response([
                'success' => true,
                'session_id' => 'SESSION_123',
                'started' => true,
                'timestamp' => now()->toISOString()
            ], 200)
        ]);

        $result = $this->steveApiService->startCharge('BORNE_123', 'SESSION_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertEquals('SESSION_123', $result['session_id']);
        $this->assertTrue($result['started']);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_handles_start_charge_failure()
    {
        // Mock failed HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/charges/start' => Http::response([
                'success' => false,
                'error' => 'Charger not available'
            ], 400)
        ]);

        $result = $this->steveApiService->startCharge('BORNE_123', 'SESSION_123');

        $this->assertFalse($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertEquals('SESSION_123', $result['session_id']);
        $this->assertFalse($result['started']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_stop_charge_successfully()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/charges/stop' => Http::response([
                'success' => true,
                'session_id' => 'SESSION_123',
                'stopped' => true,
                'timestamp' => now()->toISOString()
            ], 200)
        ]);

        $result = $this->steveApiService->stopCharge('BORNE_123', 'SESSION_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertEquals('SESSION_123', $result['session_id']);
        $this->assertTrue($result['stopped']);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_handles_stop_charge_failure()
    {
        // Mock failed HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/charges/stop' => Http::response([
                'success' => false,
                'error' => 'Session not found'
            ], 404)
        ]);

        $result = $this->steveApiService->stopCharge('BORNE_123', 'SESSION_123');

        $this->assertFalse($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertEquals('SESSION_123', $result['session_id']);
        $this->assertFalse($result['stopped']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_get_charger_status()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123/status' => Http::response([
                'success' => true,
                'status' => 'Available',
                'connector_status' => 'Available',
                'timestamp' => now()->toISOString()
            ], 200)
        ]);

        $result = $this->steveApiService->getChargerStatus('BORNE_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_can_get_session_status()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/charges/SESSION_123/status' => Http::response([
                'success' => true,
                'session_id' => 'SESSION_123',
                'status' => 'Charging',
                'energy_delivered' => 5.5,
                'timestamp' => now()->toISOString()
            ], 200)
        ]);

        $result = $this->steveApiService->getSessionStatus('BORNE_123', 'SESSION_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertEquals('SESSION_123', $result['session_id']);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_can_test_connection()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/health' => Http::response([
                'success' => true,
                'status' => 'healthy',
                'timestamp' => now()->toISOString()
            ], 200)
        ]);

        $result = $this->steveApiService->testConnection();

        $this->assertTrue($result['success']);
        $this->assertTrue($result['connected']);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_handles_connection_test_failure()
    {
        // Mock failed HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/health' => Http::response([
                'success' => false,
                'error' => 'Service unavailable'
            ], 503)
        ]);

        $result = $this->steveApiService->testConnection();

        $this->assertFalse($result['success']);
        $this->assertFalse($result['connected']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_generate_websocket_url()
    {
        $websocketUrl = $this->steveApiService->generateWebSocketUrl('BORNE_123');

        $this->assertEquals(
            'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/BORNE_123',
            $websocketUrl
        );
    }

    /** @test */
    public function it_can_get_charger_info()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123' => Http::response([
                'success' => true,
                'charger_id' => 'BORNE_123',
                'info' => [
                    'model' => 'Tesla Supercharger',
                    'power' => 150,
                    'connectors' => 2
                ]
            ], 200)
        ]);

        $result = $this->steveApiService->getChargerInfo('BORNE_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_can_list_chargers()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers' => Http::response([
                'success' => true,
                'chargers' => [
                    ['id' => 'BORNE_123', 'status' => 'Available'],
                    ['id' => 'BORNE_124', 'status' => 'Charging']
                ]
            ], 200)
        ]);

        $result = $this->steveApiService->listChargers();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('chargers', $result);
        $this->assertEquals(2, $result['count']);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_can_get_charging_stats()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/stats' => Http::response([
                'success' => true,
                'stats' => [
                    'total_sessions' => 150,
                    'total_energy' => 2500.5,
                    'average_session_time' => 45
                ]
            ], 200)
        ]);

        $result = $this->steveApiService->getChargingStats();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_can_get_charging_stats_for_specific_charger()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123/stats' => Http::response([
                'success' => true,
                'stats' => [
                    'sessions' => 25,
                    'energy_delivered' => 450.2,
                    'uptime' => 99.5
                ]
            ], 200)
        ]);

        $result = $this->steveApiService->getChargingStats('BORNE_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('BORNE_123', $result['charger_id']);
        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('duration_ms', $result);
    }

    /** @test */
    public function it_can_check_availability()
    {
        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/health' => Http::response([
                'success' => true,
                'status' => 'healthy'
            ], 200)
        ]);

        $isAvailable = $this->steveApiService->isAvailable();

        $this->assertTrue($isAvailable);
    }

    /** @test */
    public function it_handles_availability_check_failure()
    {
        // Mock failed HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/health' => Http::response([
                'success' => false,
                'error' => 'Service unavailable'
            ], 503)
        ]);

        $isAvailable = $this->steveApiService->isAvailable();

        $this->assertFalse($isAvailable);
    }

    /** @test */
    public function it_can_get_config()
    {
        $config = $this->steveApiService->getConfig();

        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('max_retries', $config);
        $this->assertArrayHasKey('retry_delay', $config);
        $this->assertArrayHasKey('websocket_url', $config);
    }

    /** @test */
    public function it_handles_network_timeout()
    {
        // Mock timeout response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContains('Connection timeout', $result['error']);
    }

    /** @test */
    public function it_handles_http_errors()
    {
        // Mock HTTP error response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'error' => 'Internal server error'
            ], 500)
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_logs_requests_and_responses()
    {
        Log::shouldReceive('info')->atLeast()->once();
        Log::shouldReceive('debug')->atLeast()->once();

        // Mock successful HTTP response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => 'BORNE_123'
            ], 200)
        ]);

        $this->steveApiService->connectCharger('BORNE_123');
    }

    /** @test */
    public function it_handles_retry_mechanism()
    {
        $attemptCount = 0;
        
        // Mock responses that fail first two times, then succeed
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => function () use (&$attemptCount) {
                $attemptCount++;
                if ($attemptCount < 3) {
                    return Http::response(['error' => 'Temporary failure'], 500);
                }
                return Http::response(['success' => true, 'charger_id' => 'BORNE_123'], 200);
            }
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $attemptCount);
    }

    /** @test */
    public function it_handles_max_retries_exceeded()
    {
        // Mock responses that always fail
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'error' => 'Service unavailable'
            ], 503)
        ]);

        $result = $this->steveApiService->connectCharger('BORNE_123');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
}
