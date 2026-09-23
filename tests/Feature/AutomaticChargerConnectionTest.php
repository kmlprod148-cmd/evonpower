<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Models\Group;
use App\Services\AutomaticChargerConnectionService;
use App\Services\SteveApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AutomaticChargerConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $group;
    protected $chargingPoint;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();
        $this->chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $this->group->id,
            'charge_box_id' => 'BORNE_123'
        ]);
    }

    /** @test */
    public function it_can_connect_charger_automatically()
    {
        // Mock successful Steve API response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => 'BORNE_123',
                'connected' => true
            ], 200)
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect/{$this->chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Borne connectée avec succès',
                    'data' => [
                        'charging_point_id' => $this->chargingPoint->id,
                        'charger_id' => 'BORNE_123',
                        'connected' => true
                    ]
                ]);
    }

    /** @test */
    public function it_handles_charger_connection_failure()
    {
        // Mock failed Steve API response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => false,
                'error' => 'Charger not found'
            ], 404)
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect/{$this->chargingPoint->id}");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Échec de la connexion de la borne'
                ]);
    }

    /** @test */
    public function it_can_disconnect_charger()
    {
        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/disconnect/{$this->chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Borne déconnectée avec succès',
                    'data' => [
                        'charging_point_id' => $this->chargingPoint->id,
                        'charger_id' => 'BORNE_123',
                        'connected' => false
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_charger_status()
    {
        // Mock successful status check
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123/status' => Http::response([
                'success' => true,
                'status' => 'Available'
            ], 200)
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/auto-connect/status/{$this->chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Statut récupéré avec succès',
                    'data' => [
                        'charging_point_id' => $this->chargingPoint->id,
                        'charger_id' => 'BORNE_123',
                        'connected' => true
                    ]
                ]);
    }

    /** @test */
    public function it_can_connect_all_chargers_in_group()
    {
        // Create additional charging points in the same group
        $chargingPoint2 = ChargingPoint::factory()->create([
            'group_id' => $this->group->id,
            'charge_box_id' => 'BORNE_124'
        ]);

        // Mock successful Steve API responses
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => 'BORNE_123',
                'connected' => true
            ], 200)
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect-group/{$this->group->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'group_id' => $this->group->id,
                        'total_chargers' => 2,
                        'successful_connections' => 2
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_connection_stats()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/auto-connect/stats');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Statistiques récupérées avec succès',
                    'data' => [
                        'total_chargers' => 1,
                        'connected' => 0,
                        'disconnected' => 1,
                        'connection_rate' => 0
                    ]
                ]);
    }

    /** @test */
    public function it_can_clear_connection_cache()
    {
        $this->actingAs($this->user);

        $response = $this->deleteJson("/api/auto-connect/cache/{$this->chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Cache de connexion nettoyé pour la borne',
                    'data' => [
                        'charging_point_id' => $this->chargingPoint->id
                    ]
                ]);
    }

    /** @test */
    public function it_can_test_connectivity()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/auto-connect/test-connectivity');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Service de connexion automatique opérationnel',
                    'data' => [
                        'service_status' => 'operational'
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson("/api/auto-connect/connect/{$this->chargingPoint->id}");
        $response->assertStatus(401);

        $response = $this->getJson("/api/auto-connect/status/{$this->chargingPoint->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_charging_point_not_found()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/auto-connect/connect/999');
        $response->assertStatus(404);
    }

    /** @test */
    public function it_caches_connection_status()
    {
        // Mock successful status check
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/BORNE_123/status' => Http::response([
                'success' => true,
                'status' => 'Available'
            ], 200)
        ]);

        $this->actingAs($this->user);

        // First request should call the API
        $response1 = $this->getJson("/api/auto-connect/status/{$this->chargingPoint->id}");
        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this->getJson("/api/auto-connect/status/{$this->chargingPoint->id}");
        $response2->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'cached' => true
                    ]
                ]);

        // Verify only one HTTP request was made
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_handles_network_errors_gracefully()
    {
        // Mock network error
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect/{$this->chargingPoint->id}");

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Erreur lors de la connexion de la borne'
                ]);
    }

    /** @test */
    public function it_uses_stored_charger_id_when_available()
    {
        $chargingPoint = ChargingPoint::factory()->create([
            'charge_box_id' => 'CUSTOM_BORNE_456'
        ]);

        // Mock successful Steve API response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => 'CUSTOM_BORNE_456',
                'connected' => true
            ], 200)
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect/{$chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'charger_id' => 'CUSTOM_BORNE_456'
                    ]
                ]);
    }

    /** @test */
    public function it_generates_charger_id_when_not_stored()
    {
        $chargingPoint = ChargingPoint::factory()->create([
            'charge_box_id' => null
        ]);

        // Mock successful Steve API response
        Http::fake([
            'http://158.69.27.239:8080/api/v1/chargers/connect' => Http::response([
                'success' => true,
                'charger_id' => "BORNE_{$chargingPoint->id}",
                'connected' => true
            ], 200)
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect/{$chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'charger_id' => "BORNE_{$chargingPoint->id}"
                    ]
                ]);
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

        $this->actingAs($this->user);

        $response = $this->postJson("/api/auto-connect/connect/{$this->chargingPoint->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertEquals(3, $attemptCount);
    }
}
