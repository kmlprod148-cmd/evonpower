<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\ChargingPoint;
use App\Services\AutoAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

class AutoAssignmentTest extends TestCase
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
    public function admin_can_assign_to_any_integrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->actingAs($admin);

        $data = ['integrator_id' => $integrator->id];
        $autoAssigned = AutoAssignmentService::assignUserRelationships(new User(), $data);

        $this->assertEquals($admin->id, $autoAssigned['created_by']);
        $this->assertEquals('admin', $autoAssigned['created_by_role']);
    }

    /** @test */
    public function integrator_can_only_assign_to_their_own_integrator()
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

        $this->actingAs($user1);

        // Should auto-assign to integrator1
        $data = [];
        $autoAssigned = AutoAssignmentService::assignUserRelationships(new User(), $data);

        $this->assertEquals($integrator1->id, $autoAssigned['integrator_id']);
        $this->assertEquals($user1->id, $autoAssigned['created_by']);
        $this->assertEquals('integrator', $autoAssigned['created_by_role']);
    }

    /** @test */
    public function partner_auto_assigns_to_their_partner_hierarchy()
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

        $user = User::create([
            'name' => 'Partner User',
            'email' => 'partner@test.com',
            'password' => bcrypt('password'),
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $user->assignRole('partner');

        $this->actingAs($user);

        $data = [];
        $autoAssigned = AutoAssignmentService::assignChargingPointRelationships(new ChargingPoint(), $data);

        $this->assertEquals($partner->id, $autoAssigned['partner_id']);
        $this->assertEquals($integrator->id, $autoAssigned['integrator_id']);
        $this->assertEquals($group->id, $autoAssigned['group_id']);
        $this->assertEquals($user->id, $autoAssigned['created_by']);
        $this->assertEquals('partner', $autoAssigned['created_by_role']);
    }

    /** @test */
    public function operator_auto_assigns_to_their_integrator_hierarchy()
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
            'name' => 'Operator User',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $operator->assignRole('operator');

        $this->actingAs($operator);

        $data = ['group_id' => $group->id];
        $autoAssigned = AutoAssignmentService::assignChargingPointRelationships(new ChargingPoint(), $data);

        $this->assertEquals($integrator->id, $autoAssigned['integrator_id']);
        $this->assertEquals($partner->id, $autoAssigned['partner_id']);
        $this->assertEquals($operator->id, $autoAssigned['created_by']);
        $this->assertEquals('operator', $autoAssigned['created_by_role']);
    }

    /** @test */
    public function auto_assignment_validates_permissions()
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

        $user = User::create([
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator1->id,
            'created_by' => $admin->id
        ]);
        $user->assignRole('integrator');

        $this->actingAs($user);

        // Should be able to assign to their own integrator
        $this->assertTrue(AutoAssignmentService::validateAssignment('user', ['integrator_id' => $integrator1->id]));
        
        // Should not be able to assign to other integrator
        $this->assertFalse(AutoAssignmentService::validateAssignment('user', ['integrator_id' => $integrator2->id]));
    }

    /** @test */
    public function get_available_options_returns_correct_data()
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

        $this->actingAs($admin);

        // Admin should see all integrators
        $integrators = AutoAssignmentService::getAvailableOptions('integrators');
        $this->assertCount(1, $integrators);
        $this->assertEquals($integrator->id, $integrators->first()->id);

        // Admin should see all partners
        $partners = AutoAssignmentService::getAvailableOptions('partners');
        $this->assertCount(1, $partners);
        $this->assertEquals($partner->id, $partners->first()->id);

        // Admin should see all groups
        $groups = AutoAssignmentService::getAvailableOptions('groups');
        $this->assertCount(1, $groups);
        $this->assertEquals($group->id, $groups->first()->id);
    }

    /** @test */
    public function get_default_values_returns_correct_data()
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
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id
        ]);
        $user->assignRole('integrator');

        $this->actingAs($user);

        $defaults = AutoAssignmentService::getDefaultValues('user');
        
        $this->assertEquals($user->id, $defaults['created_by']);
        $this->assertEquals('integrator', $defaults['created_by_role']);
        $this->assertEquals($integrator->id, $defaults['integrator_id']);
    }

    /** @test */
    public function middleware_auto_assigns_relationships()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'operator',
            'integrator_id' => $integrator->id,
        ]);

        $response->assertStatus(201);
        
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($integrator->id, $user->integrator_id);
        $this->assertEquals($admin->id, $user->created_by);
        $this->assertEquals('admin', $user->created_by_role);
    }

    /** @test */
    public function auto_assignment_rule_validation_works()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'operator',
            'integrator_id' => $integrator->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user',
            'auto_assigned_fields'
        ]);
    }
}
