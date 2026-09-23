<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class OperatorChargingPointCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'integrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'operator']);
    }

    /** @test */
    public function operator_can_create_charging_point()
    {
        // Create integrator
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        // Create partner
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        $partnerProfile = Partner::factory()->create(['user_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'integrator_id' => $integratorProfile->id,
            'partner_id' => $partnerProfile->id,
            'group_id' => 1 // Assuming group_id exists
        ]);
        
        $this->actingAs($operator);
        
        $chargingPointData = [
            'name' => 'Test Charging Point',
            'location' => 'Test Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online'
        ];
        
        $response = $this->post(route('charging-points.store'), $chargingPointData);
        
        $response->assertStatus(302); // Redirect after successful creation
        
        // Verify charging point was created with correct fields
        $this->assertDatabaseHas('charging_points', [
            'name' => 'Test Charging Point',
            'operator_id' => $operator->id,
            'integrator_id' => $operator->integrator_id,
            'group_id' => $operator->group_id
        ]);
    }

    /** @test */
    public function operator_cannot_create_charging_point_for_other_operator()
    {
        // Create integrator
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        // Create partner
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        $partnerProfile = Partner::factory()->create(['user_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'integrator_id' => $integratorProfile->id,
            'partner_id' => $partnerProfile->id,
            'group_id' => 1
        ]);
        
        // Create another operator
        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $chargingPointData = [
            'name' => 'Test Charging Point',
            'location' => 'Test Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online',
            'operator_id' => $otherOperator->id // Try to create for another operator
        ];
        
        $response = $this->post(route('charging-points.store'), $chargingPointData);
        
        $response->assertStatus(400); // Bad request
        $response->assertJson([
            'message' => 'Vous ne pouvez créer des bornes que pour votre propre compte'
        ]);
    }

    /** @test */
    public function admin_can_create_charging_point_for_anyone()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->actingAs($admin);
        
        $chargingPointData = [
            'name' => 'Admin Charging Point',
            'location' => 'Admin Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online',
            'operator_id' => 999, // Admin can specify any operator
            'integrator_id' => 999,
            'group_id' => 999
        ];
        
        $response = $this->post(route('charging-points.store'), $chargingPointData);
        
        $response->assertStatus(302); // Redirect after successful creation
        
        // Verify charging point was created
        $this->assertDatabaseHas('charging_points', [
            'name' => 'Admin Charging Point',
            'operator_id' => 999,
            'integrator_id' => 999,
            'group_id' => 999
        ]);
    }

    /** @test */
    public function integrator_can_create_charging_point_for_their_operators()
    {
        // Create integrator
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        // Create partner
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        $partnerProfile = Partner::factory()->create(['user_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'integrator_id' => $integratorProfile->id,
            'partner_id' => $partnerProfile->id,
            'group_id' => 1
        ]);
        
        $this->actingAs($integrator);
        
        $chargingPointData = [
            'name' => 'Integrator Charging Point',
            'location' => 'Integrator Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online',
            'operator_id' => $operator->id,
            'integrator_id' => $integratorProfile->id
        ];
        
        $response = $this->post(route('charging-points.store'), $chargingPointData);
        
        $response->assertStatus(302); // Redirect after successful creation
        
        // Verify charging point was created
        $this->assertDatabaseHas('charging_points', [
            'name' => 'Integrator Charging Point',
            'operator_id' => $operator->id,
            'integrator_id' => $integratorProfile->id
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_charging_point()
    {
        $chargingPointData = [
            'name' => 'Unauthorized Charging Point',
            'location' => 'Unauthorized Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online'
        ];
        
        $response = $this->post(route('charging-points.store'), $chargingPointData);
        
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function user_without_role_cannot_create_charging_point()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $chargingPointData = [
            'name' => 'No Role Charging Point',
            'location' => 'No Role Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online'
        ];
        
        $response = $this->post(route('charging-points.store'), $chargingPointData);
        
        $response->assertStatus(403);
    }
}
