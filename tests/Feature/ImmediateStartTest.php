<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Wallet;
use App\Services\ImmediateStartService;
use App\Services\SteVeApiService;
use App\Services\SteveService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;

class ImmediateStartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_get_immediate_start_status()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        $response = $this->getJson("/api/immediate-start/status/{$chargingPoint->id}");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'available',
                        'status',
                        'active_session',
                        'steve_connection'
                    ]
                ]);
    }

    /** @test */
    public function it_can_start_charging_immediately()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Mock Steve API service
        $this->mock(SteVeApiService::class, function ($mock) {
            $mock->shouldReceive('startCharging')
                ->once()
                ->andReturn([
                    'success' => true,
                    'session_id' => 'test-session-123'
                ]);
        });

        $sessionData = [
            'connector_id' => 1,
            'id_tag' => $this->user->id,
            'estimated_cost' => 10.00,
            'prepaid_amount' => 10.00,
            'min_threshold' => 5.00,
        ];

        $response = $this->postJson("/api/immediate-start/start/{$chargingPoint->id}", $sessionData);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Charging started successfully'
                ]);

        // Check that charging session was created
        $this->assertDatabaseHas('charging_sessions', [
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $this->user->id,
            'status' => 'charging',
            'mode' => 'immediate'
        ]);
    }

    /** @test */
    public function it_can_process_payment_and_start_charging()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create wallet for user
        $wallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'balance' => 50.00
        ]);

        // Mock Steve API service
        $this->mock(SteVeApiService::class, function ($mock) {
            $mock->shouldReceive('startCharging')
                ->once()
                ->andReturn([
                    'success' => true,
                    'session_id' => 'test-session-123'
                ]);
        });

        $paymentData = [
            'amount' => 10.00,
            'connector_id' => 1,
            'id_tag' => $this->user->id,
            'estimated_cost' => 10.00,
            'prepaid_amount' => 10.00,
            'min_threshold' => 5.00,
        ];

        $response = $this->postJson("/api/immediate-start/process-payment/{$chargingPoint->id}", $paymentData);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment processed and charging started successfully'
                ]);

        // Check that wallet was debited
        $this->assertEquals(40.00, $wallet->fresh()->balance);
        
        // Check that charging session was created
        $this->assertDatabaseHas('charging_sessions', [
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $this->user->id,
            'status' => 'charging',
            'mode' => 'immediate'
        ]);
    }

    /** @test */
    public function it_handles_insufficient_wallet_balance()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create wallet with insufficient balance
        $wallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'balance' => 5.00
        ]);

        $paymentData = [
            'amount' => 10.00,
            'connector_id' => 1,
            'id_tag' => $this->user->id,
        ];

        $response = $this->postJson("/api/immediate-start/process-payment/{$chargingPoint->id}", $paymentData);
        
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ])
                ->assertJsonFragment([
                    'message' => 'Insufficient wallet balance for immediate start'
                ]);
    }

    /** @test */
    public function it_handles_charging_point_not_available()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'offline']);
        
        $sessionData = [
            'connector_id' => 1,
            'id_tag' => $this->user->id,
        ];

        $response = $this->postJson("/api/immediate-start/start/{$chargingPoint->id}", $sessionData);
        
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ])
                ->assertJsonFragment([
                    'message' => 'Charging point is not available for immediate start'
                ]);
    }

    /** @test */
    public function it_can_check_wallet_balance()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create wallet for user
        $wallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'balance' => 25.00
        ]);

        $response = $this->getJson("/api/immediate-start/check-balance/{$chargingPoint->id}");
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'current_balance' => 25.00,
                        'sufficient_balance' => true
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_active_sessions()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create active charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $this->user->id,
            'status' => 'charging',
            'mode' => 'immediate'
        ]);

        $response = $this->getJson('/api/immediate-start/active-sessions');
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_stop_charging_session()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $this->user->id,
            'status' => 'charging',
            'mode' => 'immediate'
        ]);

        // Mock Steve API service
        $this->mock(SteVeApiService::class, function ($mock) {
            $mock->shouldReceive('stopCharging')
                ->once()
                ->andReturn([
                    'success' => true
                ]);
        });

        $response = $this->postJson("/api/immediate-start/stop/{$session->id}");
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Charging session stopped successfully'
                ]);

        // Check that session status was updated
        $this->assertEquals('completed', $session->fresh()->status);
    }

    /** @test */
    public function it_can_get_session_status()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create charging session
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $this->user->id,
            'status' => 'charging',
            'mode' => 'immediate'
        ]);

        $response = $this->getJson("/api/immediate-start/session-status/{$session->id}");
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'session_id' => $session->id,
                        'status' => 'charging'
                    ]
                ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_sessions()
    {
        $otherUser = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create charging session for other user
        $session = ChargingSession::factory()->create([
            'charging_point_id' => $chargingPoint->id,
            'user_id' => $otherUser->id,
            'status' => 'charging',
            'mode' => 'immediate'
        ]);

        $response = $this->postJson("/api/immediate-start/stop/{$session->id}");
        
        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized to stop this charging session'
                ]);
    }

    /** @test */
    public function it_handles_steve_api_failure_gracefully()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Mock Steve API service to fail
        $this->mock(SteVeApiService::class, function ($mock) {
            $mock->shouldReceive('startCharging')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Steve API error'
                ]);
        });

        $sessionData = [
            'connector_id' => 1,
            'id_tag' => $this->user->id,
        ];

        $response = $this->postJson("/api/immediate-start/start/{$chargingPoint->id}", $sessionData);
        
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ])
                ->assertJsonFragment([
                    'message' => 'Failed to start charging via Steve API: Steve API error'
                ]);
    }

    /** @test */
    public function it_rolls_back_payment_on_charging_failure()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Create wallet for user
        $wallet = Wallet::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'balance' => 50.00
        ]);

        // Mock Steve API service to fail
        $this->mock(SteVeApiService::class, function ($mock) {
            $mock->shouldReceive('startCharging')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'Steve API error'
                ]);
        });

        $paymentData = [
            'amount' => 10.00,
            'connector_id' => 1,
            'id_tag' => $this->user->id,
        ];

        $response = $this->postJson("/api/immediate-start/process-payment/{$chargingPoint->id}", $paymentData);
        
        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);

        // Check that wallet balance was restored (rollback)
        $this->assertEquals(50.00, $wallet->fresh()->balance);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        $response = $this->postJson("/api/immediate-start/process-payment/{$chargingPoint->id}", []);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['amount']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        
        // Logout user
        Auth::logout();
        
        $response = $this->getJson("/api/immediate-start/status/{$chargingPoint->id}");
        
        $response->assertStatus(401);
    }
}
