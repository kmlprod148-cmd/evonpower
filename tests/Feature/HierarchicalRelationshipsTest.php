<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\ChargingPoint;
use App\Services\HierarchicalValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HierarchicalRelationshipsTest extends TestCase
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
    public function admin_can_create_integrator()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->assertDatabaseHas('integrators', [
            'id' => $integrator->id,
            'created_by' => $admin->id
        ]);

        $this->assertEquals($admin->id, $integrator->created_by);
    }

    /** @test */
    public function integrator_can_create_operator()
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

        $this->assertDatabaseHas('users', [
            'id' => $operator->id,
            'integrator_id' => $integrator->id
        ]);

        $this->assertEquals($integrator->id, $operator->integrator_id);
    }

    /** @test */
    public function partner_belongs_to_integrator()
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

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'integrator_id' => $integrator->id
        ]);

        $this->assertEquals($integrator->id, $partner->integrator_id);
    }

    /** @test */
    public function group_belongs_to_partner()
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

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'partner_id' => $partner->id
        ]);

        $this->assertEquals($partner->id, $group->partner_id);
    }

    /** @test */
    public function charging_point_belongs_to_group()
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

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'TEST123',
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        $this->assertDatabaseHas('charging_points', [
            'id' => $chargingPoint->id,
            'group_id' => $group->id
        ]);

        $this->assertEquals($group->id, $chargingPoint->group_id);
    }

    /** @test */
    public function hierarchical_validation_service_works()
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

        $chargingPoint = ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'TEST123',
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        // Test hierarchy validation
        $errors = HierarchicalValidationService::validateCompleteHierarchy($chargingPoint);
        $this->assertEmpty($errors, 'Hierarchy validation should pass: ' . implode(', ', $errors));

        // Test hierarchy path
        $path = HierarchicalValidationService::getHierarchyPath($chargingPoint);
        $this->assertNotEmpty($path);
        $this->assertContains('Admin: ' . $admin->name, $path);
    }

    /** @test */
    public function invalid_hierarchy_throws_exception()
    {
        $this->expectException(\Exception::class);

        // Try to create an integrator without an admin creator
        Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => 999, // Non-existent admin
            'created_by_role' => 'admin'
        ]);
    }

    /** @test */
    public function operator_must_belong_to_integrator()
    {
        $this->expectException(\Exception::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Try to create an operator without an integrator
        $operator = User::create([
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'integrator_id' => 999, // Non-existent integrator
            'created_by' => $admin->id
        ]);
        $operator->assignRole('operator');
    }

    /** @test */
    public function group_must_belong_to_partner()
    {
        $this->expectException(\Exception::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Try to create a group without a partner
        Group::create([
            'name' => 'Test Group',
            'description' => 'Test Description',
            'partner_id' => 999, // Non-existent partner
            'user_id' => $admin->id
        ]);
    }

    /** @test */
    public function charging_point_must_belong_to_group()
    {
        $this->expectException(\Exception::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Try to create a charging point without a group
        ChargingPoint::create([
            'name' => 'Test Charging Point',
            'serial_number' => 'TEST123',
            'group_id' => 999, // Non-existent group
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);
    }
}
