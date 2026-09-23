<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PolicyAccessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $integratorRole = Role::create(['name' => 'integrator']);
        $operatorRole = Role::create(['name' => 'operator']);
        
        // Create permissions
        $permissions = [
            'view_integrators',
            'edit_integrators',
            'view_operators',
            'edit_operators',
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        $adminRole->givePermissionTo($permissions);
        $integratorRole->givePermissionTo(['view_operators', 'edit_operators']);
    }

    /** @test */
    public function admin_can_view_all_integrators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = Integrator::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->get(route('integrators.show', $integrator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_edit_all_integrators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = Integrator::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->get(route('integrators.edit', $integrator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function integrator_can_view_own_profile()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('integrators.show', $integratorProfile));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function integrator_can_edit_own_profile()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('integrators.edit', $integratorProfile));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function integrator_cannot_view_other_integrators()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $otherIntegrator = Integrator::factory()->create();
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('integrators.show', $otherIntegrator));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function integrator_cannot_edit_other_integrators()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $otherIntegrator = Integrator::factory()->create();
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('integrators.edit', $otherIntegrator));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_all_operators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($admin);
        
        $response = $this->get(route('operators.show', $operator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_edit_all_operators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($admin);
        
        $response = $this->get(route('operators.edit', $operator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function integrator_can_view_operators_they_created()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'created_by_id' => $integrator->id,
            'created_by_type' => get_class($integrator)
        ]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('operators.show', $operator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function integrator_can_edit_operators_they_created()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'created_by_id' => $integrator->id,
            'created_by_type' => get_class($integrator)
        ]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('operators.edit', $operator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function integrator_cannot_view_operators_they_did_not_create()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $otherIntegrator = User::factory()->create();
        $otherIntegrator->assignRole('integrator');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'created_by_id' => $otherIntegrator->id,
            'created_by_type' => get_class($otherIntegrator)
        ]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('operators.show', $operator));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function integrator_cannot_edit_operators_they_did_not_create()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $otherIntegrator = User::factory()->create();
        $otherIntegrator->assignRole('integrator');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'created_by_id' => $otherIntegrator->id,
            'created_by_type' => get_class($otherIntegrator)
        ]);
        
        $this->actingAs($integrator);
        
        $response = $this->get(route('operators.edit', $operator));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function operator_can_view_own_profile()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $response = $this->get(route('operators.show', $operator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function operator_can_edit_own_profile()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $response = $this->get(route('operators.edit', $operator));
        
        $response->assertStatus(200);
    }

    /** @test */
    public function operator_cannot_view_other_operators()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $response = $this->get(route('operators.show', $otherOperator));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_edit_other_operators()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $response = $this->get(route('operators.edit', $otherOperator));
        
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $integrator = Integrator::factory()->create();
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->get(route('integrators.show', $integrator))
            ->assertRedirect(route('login'));
            
        $this->get(route('operators.show', $operator))
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function user_without_role_cannot_access_protected_routes()
    {
        $user = User::factory()->create();
        $integrator = Integrator::factory()->create();
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($user);
        
        $this->get(route('integrators.show', $integrator))
            ->assertStatus(403);
            
        $this->get(route('operators.show', $operator))
            ->assertStatus(403);
    }
}
