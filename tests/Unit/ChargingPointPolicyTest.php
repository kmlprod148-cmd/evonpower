<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Policies\ChargingPointPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ChargingPointPolicyTest extends TestCase
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
    public function admin_can_create_charging_point()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $policy = new ChargingPointPolicy();
        
        $this->assertTrue($policy->create($admin));
    }

    /** @test */
    public function integrator_can_create_charging_point()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $policy = new ChargingPointPolicy();
        
        $this->assertTrue($policy->create($integrator));
    }

    /** @test */
    public function partner_can_create_charging_point()
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        
        $policy = new ChargingPointPolicy();
        
        $this->assertTrue($policy->create($partner));
    }

    /** @test */
    public function operator_can_create_charging_point()
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $policy = new ChargingPointPolicy();
        
        $this->assertTrue($policy->create($operator));
    }

    /** @test */
    public function user_without_role_cannot_create_charging_point()
    {
        $user = User::factory()->create();
        
        $policy = new ChargingPointPolicy();
        
        $this->assertFalse($policy->create($user));
    }

    /** @test */
    public function admin_can_view_any_charging_point()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $chargingPoint = ChargingPoint::factory()->create();
        
        $policy = new ChargingPointPolicy();
        
        $this->assertTrue($policy->view($admin, $chargingPoint));
    }

    /** @test */
    public function operator_can_view_charging_point_in_their_integrator()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = \App\Models\Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integratorProfile->id]);
        
        $chargingPoint = ChargingPoint::factory()->create(['integrator_id' => $integratorProfile->id]);
        
        $policy = new ChargingPointPolicy();
        
        $this->assertTrue($policy->view($operator, $chargingPoint));
    }

    /** @test */
    public function operator_cannot_view_charging_point_in_other_integrator()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        $integratorProfile = \App\Models\Integrator::factory()->create(['user_id' => $integrator->id]);
        
        $otherIntegrator = User::factory()->create();
        $otherIntegrator->assignRole('integrator');
        $otherIntegratorProfile = \App\Models\Integrator::factory()->create(['user_id' => $otherIntegrator->id]);
        
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integratorProfile->id]);
        
        $chargingPoint = ChargingPoint::factory()->create(['integrator_id' => $otherIntegratorProfile->id]);
        
        $policy = new ChargingPointPolicy();
        
        $this->assertFalse($policy->view($operator, $chargingPoint));
    }
}
