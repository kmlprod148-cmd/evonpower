<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class HierarchicalValidationTest extends TestCase
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
    public function operator_creation_fails_without_integrator_id()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->actingAs($admin);
        
        $operatorData = [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '123456789',
            'address' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'business_profile_id' => 1,
            // Missing integrator_id
        ];
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing Integrator: Vous devez sélectionner un intégrateur.');
        
        $this->post(route('integrator.operators.store'), $operatorData);
    }

    /** @test */
    public function operator_creation_fails_with_invalid_integrator_id()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->actingAs($admin);
        
        $operatorData = [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '123456789',
            'address' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'business_profile_id' => 1,
            'integrator_id' => 999, // Non-existent integrator
        ];
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid Integrator: L\'intégrateur sélectionné n\'existe pas ou n\'est pas actif.');
        
        $this->post(route('integrator.operators.store'), $operatorData);
    }

    /** @test */
    public function group_creation_fails_without_partner_id()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->actingAs($user);
        
        $groupData = [
            'name' => 'Test Group',
            'description' => 'Test Description',
            // Missing partner_id
        ];
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing Partner: Un groupe doit être lié à un partenaire.');
        
        $this->post(route('groups.store'), $groupData);
    }

    /** @test */
    public function group_creation_fails_with_invalid_partner_id()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->actingAs($user);
        
        $groupData = [
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => 999, // Non-existent partner
        ];
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid Partner: Le partenaire spécifié n\'existe pas ou n\'est pas actif.');
        
        $this->post(route('groups.store'), $groupData);
    }

    /** @test */
    public function charging_point_creation_fails_without_group_id()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->actingAs($user);
        
        $chargingPointData = [
            'name' => 'Test Charging Point',
            'location' => 'Test Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online',
            // Missing group_id
        ];
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing Group: Un point de recharge doit être lié à un groupe.');
        
        $this->post(route('charging-points.store'), $chargingPointData);
    }

    /** @test */
    public function charging_point_creation_fails_with_invalid_group_id()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->actingAs($user);
        
        $chargingPointData = [
            'name' => 'Test Charging Point',
            'location' => 'Test Location',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'connector_type' => 'Type2',
            'max_power' => 22,
            'status' => 'online',
            'group_id' => 999, // Non-existent group
        ];
        
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid Group: Le groupe spécifié n\'existe pas ou n\'est pas actif.');
        
        $this->post(route('charging-points.store'), $chargingPointData);
    }

    /** @test */
    public function operator_creation_succeeds_with_valid_hierarchy()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $businessProfile = \App\Models\BusinessProfile::factory()->create([
            'name' => 'Test Business Profile',
            'is_active' => true,
            'created_by' => $admin->id
        ]);
        
        $this->actingAs($admin);
        
        $operatorData = [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '123456789',
            'address' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'business_profile_id' => $businessProfile->id,
            'integrator_id' => $integratorProfile->id,
        ];
        
        $response = $this->post(route('integrator.operators.store'), $operatorData);
        
        $response->assertRedirect();
        
        // Verify operator was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'integrator_id' => $integratorProfile->id,
        ]);
    }

    /** @test */
    public function integrator_can_create_operator_without_specifying_integrator_id()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $businessProfile = \App\Models\BusinessProfile::factory()->create([
            'name' => 'Test Business Profile',
            'is_active' => true,
            'created_by' => $integrator->id
        ]);
        
        $this->actingAs($integrator);
        
        $operatorData = [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '123456789',
            'address' => 'Test Address',
            'city' => 'Test City',
            'postal_code' => '12345',
            'country' => 'Test Country',
            'business_profile_id' => $businessProfile->id,
            // No integrator_id specified - should use integrator's own ID
        ];
        
        $response = $this->post(route('integrator.operators.store'), $operatorData);
        
        $response->assertRedirect();
        
        // Verify operator was created with integrator's ID
        $this->assertDatabaseHas('users', [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'integrator_id' => $integratorProfile->id,
        ]);
    }
}
