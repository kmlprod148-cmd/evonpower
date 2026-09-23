<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Policies\OperatorPolicy;
use App\Policies\IntegratorPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class PolicyTest extends TestCase
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
    public function operator_policy_admin_can_view_any_operator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $policy = new OperatorPolicy();
        
        $this->assertTrue($policy->view($admin, $operator));
    }

    /** @test */
    public function operator_policy_integrator_can_view_operators_they_created()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update([
            'created_by_id' => $integrator->id,
            'created_by_type' => get_class($integrator)
        ]);
        
        $policy = new OperatorPolicy();
        
        $this->assertTrue($policy->view($integrator, $operator));
    }

    /** @test */
    public function operator_policy_integrator_cannot_view_operators_they_did_not_create()
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
        
        $policy = new OperatorPolicy();
        
        $this->assertFalse($policy->view($integrator, $operator));
    }

    /** @test */
    public function operator_policy_operator_can_view_own_profile()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $policy = new OperatorPolicy();
        
        $this->assertTrue($policy->view($operator, $operator));
    }

    /** @test */
    public function operator_policy_operator_cannot_view_other_operators()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');
        
        $policy = new OperatorPolicy();
        
        $this->assertFalse($policy->view($operator, $otherOperator));
    }

    /** @test */
    public function integrator_policy_admin_can_view_any_integrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = Integrator::factory()->create();
        
        $policy = new IntegratorPolicy();
        
        $this->assertTrue($policy->view($admin, $integrator));
    }

    /** @test */
    public function integrator_policy_integrator_can_view_own_profile()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $integratorProfile = Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $policy = new IntegratorPolicy();
        
        $this->assertTrue($policy->view($integrator, $integratorProfile));
    }

    /** @test */
    public function integrator_policy_integrator_cannot_view_other_integrators()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $otherIntegrator = Integrator::factory()->create();
        
        $policy = new IntegratorPolicy();
        
        $this->assertFalse($policy->view($integrator, $otherIntegrator));
    }

    /** @test */
    public function operator_policy_admin_can_update_any_operator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $policy = new OperatorPolicy();
        
        $this->assertTrue($policy->update($admin, $operator));
    }

    /** @test */
    public function integrator_policy_admin_can_update_any_integrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $integrator = Integrator::factory()->create();
        
        $policy = new IntegratorPolicy();
        
        $this->assertTrue($policy->update($admin, $integrator));
    }
}
