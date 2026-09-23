<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\ChargingPoint;
use App\Exceptions\PolicyException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PolicyEnforcementTest extends TestCase
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
    public function admin_can_view_all_operators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $operator->assignRole('operator');

        $this->actingAs($admin);
        $this->assertTrue($admin->can('view', $operator));
        $this->assertTrue($admin->can('viewAny', User::class));
    }

    /** @test */
    public function integrator_can_only_view_their_operators()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator1 = Integrator::create([
            'name' => 'Integrator 1',
            'email' => 'integrator1@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $integrator2 = Integrator::create([
            'name' => 'Integrator 2',
            'email' => 'integrator2@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator1->id,
            'created_by' => $admin->id
        ]);
        $user1->assignRole('integrator');

        $operator1 = User::create([
            'name' => 'Operator 1',
            'email' => 'operator1@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator1->id,
            'created_by' => $user1->id
        ]);
        $operator1->assignRole('operator');

        $operator2 = User::create([
            'name' => 'Operator 2',
            'email' => 'operator2@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator2->id,
            'created_by' => $admin->id
        ]);
        $operator2->assignRole('operator');

        $this->actingAs($user1);
        
        // Can view their own operators
        $this->assertTrue($user1->can('view', $operator1));
        
        // Cannot view other integrators' operators
        $this->assertFalse($user1->can('view', $operator2));
    }

    /** @test */
    public function operator_can_only_manage_their_own_charging_points()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $operator->assignRole('operator');

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'TEST123',
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->actingAs($operator);
        
        // Can view and manage their integrator's charging points
        $this->assertTrue($operator->can('view', $chargingPoint));
        $this->assertTrue($operator->can('update', $chargingPoint));
        $this->assertTrue($operator->can('manageSessions', $chargingPoint));
        $this->assertTrue($operator->can('control', $chargingPoint));
    }

    /** @test */
    public function policy_exception_provides_helpful_error_messages()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $user->assignRole('operator');

        $this->actingAs($user);

        // Try to access an integrator they don't belong to
        $otherIntegrator = Integrator::create([
            'name' => 'Other Integrator',
            'email' => 'other@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->assertFalse($user->can('view', $otherIntegrator));
    }

    /** @test */
    public function hierarchical_gates_work_correctly()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $group = Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => $partner->id,
            'user_id' => $admin->id
        ]);

        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $operator->assignRole('operator');

        $this->actingAs($operator);

        // Test hierarchical gates
        $this->assertTrue($operator->can('manage-integrator-operators', $integrator->id));
        $this->assertTrue($operator->can('manage-partner-groups', $partner->id));
        $this->assertTrue($operator->can('manage-group-charging-points', $group->id));
    }

    /** @test */
    public function unauthorized_access_throws_policy_exception()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $user->assignRole('operator');

        $this->actingAs($user);

        // Try to access an integrator they don't belong to
        $otherIntegrator = Integrator::create([
            'name' => 'Other Integrator',
            'email' => 'other@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->assertFalse($user->can('view', $otherIntegrator));
    }

    /** @test */
    public function policy_exception_renders_correctly()
    {
        $exception = \App\Exceptions\PolicyException::denied('ChargingPointPolicy', 'view', null, null);
        
        $this->assertInstanceOf(\App\Exceptions\PolicyException::class, $exception);
        $this->assertEquals('ChargingPointPolicy', $exception->getPolicy());
        $this->assertEquals('view', $exception->getAction());
    }
}
