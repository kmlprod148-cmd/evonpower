<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class OperatorCreationWithIntegratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'integrator']);
        Role::create(['name' => 'operator']);
    }

    /** @test */
    public function admin_can_see_integrator_select_field()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $this->actingAs($admin);
        
        $response = $this->get(route('integrator.operators.create'));
        
        $response->assertStatus(200);
        $response->assertSee('Assignation d\'Intégrateur');
        $response->assertSee('Choisir un intégrateur');
        $response->assertSee($integratorProfile->name);
    }

    /** @test */
    public function integrator_cannot_see_integrator_select_field()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('integrator.operators.create'));
        
        $response->assertStatus(200);
        $response->assertDontSee('Assignation d\'Intégrateur');
        $response->assertDontSee('Choisir un intégrateur');
    }

    /** @test */
    public function admin_can_create_operator_with_specific_integrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $businessProfile = BusinessProfile::factory()->create([
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
        
        // Verify operator was created with correct integrator
        $this->assertDatabaseHas('users', [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'integrator_id' => $integratorProfile->id,
            'created_by_id' => $admin->id,
            'created_by_type' => get_class($admin),
            'created_by_role' => 'admin',
        ]);
    }

    /** @test */
    public function integrator_can_create_operator_with_own_integrator_id()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $businessProfile = BusinessProfile::factory()->create([
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
        ];
        
        $response = $this->post(route('integrator.operators.store'), $operatorData);
        
        $response->assertRedirect();
        
        // Verify operator was created with integrator's ID
        $this->assertDatabaseHas('users', [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'integrator_id' => $integratorProfile->id,
            'created_by_id' => $integrator->id,
            'created_by_type' => get_class($integrator),
            'created_by_role' => 'integrator',
        ]);
    }

    /** @test */
    public function admin_must_select_integrator_when_creating_operator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $businessProfile = BusinessProfile::factory()->create([
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
            // Missing integrator_id
        ];
        
        $response = $this->post(route('integrator.operators.store'), $operatorData);
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Veuillez sélectionner un intégrateur.');
    }

    /** @test */
    public function integrator_cannot_create_operator_for_other_integrator()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $otherIntegrator = User::factory()->create();
        $otherIntegrator->assignRole('integrator');
        $otherIntegratorProfile = Integrator::factory()->create(['user_id' => $otherIntegrator->id]);
        
        $businessProfile = BusinessProfile::factory()->create([
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
            'integrator_id' => $otherIntegratorProfile->id, // Try to assign to other integrator
        ];
        
        $response = $this->post(route('integrator.operators.store'), $operatorData);
        
        // Should still create with integrator's own ID, not the one specified
        $this->assertDatabaseHas('users', [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'integrator_id' => $integratorProfile->id, // Should be integrator's own ID
        ]);
    }
}
