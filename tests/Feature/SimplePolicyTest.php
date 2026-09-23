<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class SimplePolicyTest extends TestCase
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
    public function admin_can_view_any_integrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = Integrator::factory()->create();
        
        $this->actingAs($admin);
        
        $this->assertTrue($admin->can('view', $integrator));
    }

    /** @test */
    public function integrator_can_view_own_profile()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $this->actingAs($integrator);
        
        $this->assertTrue($integrator->can('view', $integratorProfile));
    }

    /** @test */
    public function integrator_cannot_view_other_integrators()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $otherIntegrator = Integrator::factory()->create();
        
        $this->actingAs($integrator);
        
        $this->assertFalse($integrator->can('view', $otherIntegrator));
    }

    /** @test */
    public function admin_can_view_any_operator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($admin);
        
        $this->assertTrue($admin->can('view', $operator));
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
        
        $this->assertTrue($integrator->can('view', $operator));
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
        
        $this->assertFalse($integrator->can('view', $operator));
    }

    /** @test */
    public function operator_can_view_own_profile()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $this->assertTrue($operator->can('view', $operator));
    }

    /** @test */
    public function operator_cannot_view_other_operators()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');
        
        $this->actingAs($operator);
        
        $this->assertFalse($operator->can('view', $otherOperator));
    }
}
