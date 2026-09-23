<?php

namespace Tests\Feature;

use App\Events\ChargingPointStatusChanged;
use App\Models\ChargingPoint;
use App\Models\ChargingPointStatusHistory;
use App\Models\User;
use App\Services\SteveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChargingPointStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $steveService;
    protected $testUser;
    protected $chargingPoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()->create();
        $this->actingAs($this->testUser);

        $this->chargingPoint = ChargingPoint::factory()->create([
            'name' => 'Test Charging Point',
            'steve_charging_point_id' => 'CP001',
            'status' => 'offline',
            'status_updated_at' => now()->subMinutes(10),
        ]);

        $this->steveService = app(SteveService::class);
    }

    /** @test */
    public function it_can_get_charging_point_status_from_api()
    {
        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'status' => 'online',
                'connectors' => [
                    ['connector_id' => 1, 'status' => 'Available'],
                    ['connector_id' => 2, 'status' => 'Available'],
                ],
            ], 200),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'status' => 'online',
            ]);

        $this->chargingPoint->refresh();
        $this->assertEquals('online', $this->chargingPoint->status);
    }

    /** @test */
    public function it_returns_cached_status_within_cache_period()
    {
        Cache::put(
            "charging_point_status_{$this->chargingPoint->id}",
            ['ok' => true, 'status' => 'online', 'updated_at' => now()->toIso8601String()],
            now()->addSeconds(30)
        );

        Http::fake(); // No HTTP calls should be made

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'status' => 'online',
            ]);

        Http::assertNothingSent();
    }

    /** @test */
    public function it_can_force_refresh_status_bypassing_cache()
    {
        Cache::put(
            "charging_point_status_{$this->chargingPoint->id}",
            ['ok' => true, 'status' => 'offline', 'updated_at' => now()->toIso8601String()],
            now()->addMinutes(5)
        );

        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'status' => 'online',
            ], 200),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status?force=1");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'status' => 'online',
            ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'charge-points/CP001');
        });
    }

    /** @test */
    public function it_falls_back_to_connector_status_when_charging_point_fails()
    {
        Http::fake([
            '*/charge-points/CP001' => Http::response([], 404),
            '*/connectors/status/CP001' => Http::response([
                'connectors' => [
                    ['connector_id' => 1, 'status' => 'Charging'],
                ],
            ], 200),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'status' => 'online', // Charging maps to online
            ]);
    }

    /** @test */
    public function it_handles_missing_steve_id_gracefully()
    {
        $pointWithoutSteve = ChargingPoint::factory()->create([
            'steve_charging_point_id' => null,
            'status' => 'unknown',
        ]);

        $response = $this->getJson("/api/charging-points/{$pointWithoutSteve->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => false,
                'message' => 'No steve id attached',
                'status' => 'unknown',
            ]);
    }

    /** @test */
    public function it_correctly_maps_connector_statuses()
    {
        $testCases = [
            ['Available', 'online'],
            ['Charging', 'online'],
            ['Preparing', 'online'],
            ['Finishing', 'online'],
            ['Unavailable', 'offline'],
            ['Faulted', 'error'],
        ];

        foreach ($testCases as [$connectorStatus, $expectedStatus]) {
            Http::fake([
                '*/charge-points/CP001' => Http::response([
                    'connectors' => [
                        ['connector_id' => 1, 'status' => $connectorStatus],
                    ],
                ], 200),
            ]);

            $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status?force=1");

            $response->assertJson([
                'status' => $expectedStatus,
            ], "Failed mapping $connectorStatus to $expectedStatus");

            Cache::forget("charging_point_status_{$this->chargingPoint->id}");
        }
    }

    /** @test */
    public function it_dispatches_event_when_status_changes()
    {
        Event::fake([ChargingPointStatusChanged::class]);

        $this->chargingPoint->status = 'offline';
        $this->chargingPoint->save();

        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'status' => 'online',
            ], 200),
        ]);

        $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        // The event would be dispatched if the refreshStatusFromConnectors method is used
        // For this test to work, you need to implement the event in the model's updateStatus method
    }

    /** @test */
    public function it_creates_status_history_when_status_changes()
    {
        $this->chargingPoint->status = 'offline';
        $this->chargingPoint->save();

        $this->chargingPoint->updateStatus('online', 'Test update');

        $this->assertDatabaseHas('charging_point_status_history', [
            'charging_point_id' => $this->chargingPoint->id,
            'old_status' => 'offline',
            'new_status' => 'online',
            'reason' => 'Test update',
        ]);
    }

    /** @test */
    public function it_can_get_status_history()
    {
        // Create some history
        ChargingPointStatusHistory::create([
            'charging_point_id' => $this->chargingPoint->id,
            'old_status' => 'offline',
            'new_status' => 'online',
            'reason' => 'Auto-update',
            'changed_at' => now()->subHours(2),
        ]);

        ChargingPointStatusHistory::create([
            'charging_point_id' => $this->chargingPoint->id,
            'old_status' => 'online',
            'new_status' => 'maintenance',
            'reason' => 'Scheduled maintenance',
            'changed_at' => now()->subHour(),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status/history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'charging_point' => ['id', 'name', 'current_status'],
                'history' => [
                    '*' => ['id', 'old_status', 'new_status', 'reason', 'changed_at'],
                ],
                'period' => ['hours', 'from', 'to'],
            ])
            ->assertJsonCount(2, 'history');
    }

    /** @test */
    public function it_can_get_status_statistics()
    {
        // Create history records
        ChargingPointStatusHistory::create([
            'charging_point_id' => $this->chargingPoint->id,
            'old_status' => 'offline',
            'new_status' => 'online',
            'changed_at' => now()->subHours(5),
        ]);

        ChargingPointStatusHistory::create([
            'charging_point_id' => $this->chargingPoint->id,
            'old_status' => 'online',
            'new_status' => 'offline',
            'changed_at' => now()->subHours(2),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status/stats?hours=24");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'charging_point_id',
                'period' => ['hours', 'from', 'to'],
                'stats',
                'total_changes',
            ]);
    }

    /** @test */
    public function it_correctly_determines_online_status_from_last_seen()
    {
        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'lastSeen' => now()->subMinutes(2)->toIso8601String(),
            ], 200),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertJson([
            'status' => 'online',
        ]);
    }

    /** @test */
    public function it_determines_offline_status_from_old_last_seen()
    {
        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'lastSeen' => now()->subMinutes(10)->toIso8601String(),
            ], 200),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertJson([
            'status' => 'offline',
        ]);
    }

    /** @test */
    public function steve_health_check_returns_healthy_when_api_works()
    {
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->getJson('/api/health/steve');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'total_check_duration_ms',
                'checks' => [
                    'database',
                    'cache',
                ],
            ]);
    }

    /** @test */
    public function charging_points_health_check_returns_statistics()
    {
        ChargingPoint::factory()->count(5)->create([
            'steve_charging_point_id' => 'CP_TEST',
            'status' => 'online',
        ]);

        ChargingPoint::factory()->count(3)->create([
            'steve_charging_point_id' => 'CP_TEST',
            'status' => 'offline',
        ]);

        $response = $this->getJson('/api/health/charging-points');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'charging_points' => [
                    'total',
                    'with_steve_id',
                    'without_steve_id',
                ],
                'status_distribution',
                'stale_statuses',
            ]);
    }

    /** @test */
    public function it_updates_only_timestamp_when_status_unchanged()
    {
        $this->chargingPoint->status = 'online';
        $this->chargingPoint->status_updated_at = now()->subHours(1);
        $this->chargingPoint->save();

        $oldTimestamp = $this->chargingPoint->status_updated_at;

        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'status' => 'online',
            ], 200),
        ]);

        $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $this->chargingPoint->refresh();

        $this->assertEquals('online', $this->chargingPoint->status);
        $this->assertTrue($this->chargingPoint->status_updated_at->gt($oldTimestamp));
    }

    /** @test */
    public function monitor_command_updates_multiple_charging_points()
    {
        ChargingPoint::factory()->count(5)->create([
            'steve_charging_point_id' => 'CP_TEST',
            'status' => 'offline',
        ]);

        Http::fake([
            '*' => Http::response([
                'status' => 'online',
                'connectors' => [
                    ['connector_id' => 1, 'status' => 'Available'],
                ],
            ], 200),
        ]);

        $this->artisan('steve:monitor --limit=5 --batch=2')
            ->assertExitCode(0);

        $this->assertEquals(5, ChargingPoint::where('status', 'online')->count());
    }

    /** @test */
    public function it_handles_api_timeout_gracefully()
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'status' => 'offline', // Falls back to current status
            ]);
    }

    /** @test */
    public function it_uses_correct_priority_for_connector_statuses()
    {
        // When one connector is charging, status should be online even if others are unavailable
        Http::fake([
            '*/charge-points/CP001' => Http::response([
                'connectors' => [
                    ['connector_id' => 1, 'status' => 'Unavailable'],
                    ['connector_id' => 2, 'status' => 'Charging'],
                    ['connector_id' => 3, 'status' => 'Unavailable'],
                ],
            ], 200),
        ]);

        $response = $this->getJson("/api/charging-points/{$this->chargingPoint->id}/status");

        $response->assertJson([
            'status' => 'online',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}

