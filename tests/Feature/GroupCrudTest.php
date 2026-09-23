<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\ChargingPoint; // Assuming Group might have ChargingPoints
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class GroupCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $integratorUser;
    protected User $partnerUser;
    protected Integrator $integrator;
    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create necessary entities
        $this->integrator = Integrator::factory()->create();
        $this->partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);

        // Create users with roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->integratorUser = User::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->integratorUser->assignRole('integrator');

        $this->partnerUser = User::factory()->create([
            'partner_id' => $this->partner->id,
            'integrator_id' => $this->integrator->id, // Partners also linked to integrator
        ]);
        $this->partnerUser->assignRole('partner');
    }

    #[Test]
    public function admin_can_view_groups()
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('groups.index'));

        $response->assertOk();
        $response->assertViewIs('groups.index');
        $response->assertViewHas('groups');
    }

    #[Test]
    public function integrator_can_view_groups_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $response = $this->get(route('groups.index'));

        $response->assertOk();
        $response->assertViewIs('groups.index');
        $response->assertViewHas('groups');

        // Add assertions to check if only relevant groups are shown (requires repository logic)
    }

    #[Test]
    public function partner_can_view_groups_within_their_partner()
    {
        $this->actingAs($this->partnerUser);
        $response = $this->get(route('groups.index'));

        $response->assertOk();
        $response->assertViewIs('groups.index');
        $response->assertViewHas('groups');

        // Add assertions to check if only relevant groups are shown (requires repository logic)
    }

    #[Test]
    public function admin_can_create_group()
    {
        $this->actingAs($this->adminUser);

        $groupData = [
            'name' => 'Test Group',
            'type' => 'public',
            'city' => 'Paris',
            'address' => '1 rue de Paris',
            'postal_code' => '75000',
            'country' => 'France',
            'user_id' => $this->adminUser->id,
            'partner_id' => $this->partner->id,
            'integrator_id' => $this->integrator->id,
        ];

        $response = $this->post(route('groups.store'), $groupData);
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseHas('groups', ['name' => $groupData['name']]);
    }

    #[Test]
    public function integrator_can_create_group_within_their_integration()
    {
        $this->actingAs($this->integratorUser);

        $groupData = [
            'name' => 'Integration Group',
            'type' => 'public',
            'city' => 'Paris',
            'address' => '1 rue de Paris',
            'postal_code' => '75000',
            'country' => 'France',
            'user_id' => $this->integratorUser->id,
            'partner_id' => $this->partner->id,
        ];

        $response = $this->post(route('groups.store'), $groupData);
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseHas('groups', [
            'name' => $groupData['name'],
            'integrator_id' => $this->integrator->id,
            'user_id' => $this->integratorUser->id,
        ]);
    }

    #[Test]
    public function integrator_cannot_create_group_for_other_integrators_partner()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $otherPartner = Partner::factory()->create([
            'integrator_id' => $otherIntegrator->id,
        ]);

        $groupData = [
            'name' => 'Other Group',
            'type' => 'public',
            'city' => 'Paris',
            'address' => '1 rue de Paris',
            'postal_code' => '75000',
            'country' => 'France',
            'user_id' => $this->integratorUser->id,
            'partner_id' => $otherPartner->id,
        ];

        $response = $this->post(route('groups.store'), $groupData);
        $response->assertForbidden();
        $this->assertDatabaseMissing('groups', ['name' => $groupData['name']]);
    }

    #[Test]
    public function partner_can_create_group_for_their_partner()
    {
        $this->actingAs($this->partnerUser);

        $groupData = [
            'name' => 'Partner Group',
            'type' => 'public',
            'city' => 'Paris',
            'address' => '1 rue de Paris',
            'postal_code' => '75000',
            'country' => 'France',
            'user_id' => $this->partnerUser->id,
            'partner_id' => $this->partner->id,
        ];

        $response = $this->post(route('groups.store'), $groupData);
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseHas('groups', [
            'name' => $groupData['name'],
            'partner_id' => $this->partner->id,
            'user_id' => $this->partnerUser->id,
        ]);
    }

    #[Test]
    public function admin_can_view_group_details()
    {
        $this->actingAs($this->adminUser);
        $group = Group::factory()->create();

        $response = $this->get(route('groups.show', ['group' => $group->id]));

        $response->assertOk();
        $response->assertViewIs('groups.show');
        $response->assertViewHas('group', $group);
    }

    #[Test]
    public function integrator_can_view_group_details_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $group = Group::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);

        $response = $this->get(route('groups.show', ['group' => $group->id]));

        $response->assertOk();
        $response->assertViewIs('groups.show');
        $response->assertViewHas('group', $group);
    }

    #[Test]
    public function integrator_cannot_view_group_details_outside_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $group = Group::factory()->create([
            'integrator_id' => $otherIntegrator->id,
        ]);

        $response = $this->get(route('groups.show', ['group' => $group->id]));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function partner_can_view_group_details_within_their_partner()
    {
        $this->actingAs($this->partnerUser);
        $group = Group::factory()->create([
            'partner_id' => $this->partner->id,
        ]);

        $response = $this->get(route('groups.show', ['group' => $group->id]));

        $response->assertOk();
        $response->assertViewIs('groups.show');
        $response->assertViewHas('group', $group);
    }

    #[Test]
    public function partner_cannot_view_group_details_outside_their_partner()
    {
        $this->actingAs($this->partnerUser);
        $otherPartner = Partner::factory()->create();
        $group = Group::factory()->create([
            'partner_id' => $otherPartner->id,
        ]);

        $response = $this->get(route('groups.show', ['group' => $group->id]));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function test_admin_can_update_group()
    {
        $group = Group::factory()->create();
        $this->actingAs($this->adminUser);
        $updateData = $group->toArray();
        $updateData['name'] = 'Updated Group Name';
        $updateData['type'] = 'private';
        $updateData['city'] = 'Lyon';
        $updateData['description'] = 'Description mise à jour';
        $updateData['user_id'] = $group->user_id;
        $updateData['integrator_id'] = $group->integrator_id;
        $updateData['partner_id'] = $group->partner_id;
        $response = $this->put(route('groups.update', $group), $updateData);
        $response->assertRedirect(route('groups.show', ['group' => $group->id]));
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Updated Group Name',
            'type' => 'private',
            'city' => 'Lyon',
            'description' => 'Description mise à jour',
        ]);
    }

    #[Test]
    public function test_integrator_can_update_group()
    {
        $group = Group::factory()->create([
            'integrator_id' => $this->integrator->id,
            'user_id' => $this->integratorUser->id,
        ]);
        $this->actingAs($this->integratorUser);
        $newData = $group->toArray();
        $newData['name'] = 'Updated Group Name by Integrator';
        $response = $this->put(route('groups.update', $group), $newData);
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseHas('groups', array_merge(['id' => $group->id], $newData));
    }

    #[Test]
    public function test_integrator_cannot_update_other_group()
    {
        $otherGroup = Group::factory()->create();
        $this->actingAs($this->integratorUser);
        $newData = $otherGroup->toArray();
        $newData['name'] = 'Attempted Update';
        $response = $this->put(route('groups.update', $otherGroup), $newData);
        $response->assertForbidden();
        $this->assertDatabaseMissing('groups', array_merge(['id' => $otherGroup->id], ['name' => 'Attempted Update']));
    }

    #[Test]
    public function test_partner_can_update_own_group()
    {
        $group = Group::factory()->create([
            'partner_id' => $this->partner->id,
            'user_id' => $this->partnerUser->id,
        ]);
        $this->actingAs($this->partnerUser);
        $newData = $group->toArray();
        $newData['name'] = 'Updated My Group Name';
        $response = $this->put(route('groups.update', $group), $newData);
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseHas('groups', array_merge(['id' => $group->id], $newData));
    }

    #[Test]
    public function test_partner_cannot_update_other_group()
    {
        $otherGroup = Group::factory()->create();
        $this->actingAs($this->partnerUser);
        $newData = $otherGroup->toArray();
        $newData['name'] = 'Attempted Update Other';
        $response = $this->put(route('groups.update', $otherGroup), $newData);
        $response->assertForbidden();
        $this->assertDatabaseMissing('groups', array_merge(['id' => $otherGroup->id], ['name' => 'Attempted Update Other']));
    }

    #[Test]
    public function admin_can_delete_group()
    {
        $group = Group::factory()->create();
        $this->actingAs($this->adminUser);
        $response = $this->delete(route('groups.destroy', $group));
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    #[Test]
    public function test_integrator_can_delete_own_group()
    {
        $group = Group::factory()->create([
            'integrator_id' => $this->integrator->id,
            'user_id' => $this->integratorUser->id,
        ]);
        $this->actingAs($this->integratorUser);
        $response = $this->delete(route('groups.destroy', $group));
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    #[Test]
    public function test_integrator_cannot_delete_other_group()
    {
        $otherGroup = Group::factory()->create();
        $this->actingAs($this->integratorUser);
        $response = $this->delete(route('groups.destroy', $otherGroup));
        $response->assertForbidden();
        $this->assertDatabaseHas('groups', ['id' => $otherGroup->id]);
    }

    #[Test]
    public function test_partner_can_delete_own_group()
    {
        $group = Group::factory()->create([
            'partner_id' => $this->partner->id,
            'user_id' => $this->partnerUser->id,
        ]);
        $this->actingAs($this->partnerUser);
        $response = $this->delete(route('groups.destroy', $group));
        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    #[Test]
    public function test_partner_cannot_delete_other_group()
    {
        $otherGroup = Group::factory()->create();
        $this->actingAs($this->partnerUser);
        $response = $this->delete(route('groups.destroy', $otherGroup));
        $response->assertForbidden();
        $this->assertDatabaseHas('groups', ['id' => $otherGroup->id]);
    }

    #[Test]
    public function cannot_delete_group_with_charging_points()
    {
        $this->actingAs($this->adminUser); // Or any user with delete permission
        $group = Group::factory()->create();
        ChargingPoint::factory()->create(['group_id' => $group->id]);

        $response = $this->delete(route('groups.destroy', $group));

        $response->assertSessionHasErrors(); // Assuming an error message is set in the session
        $this->assertDatabaseHas('groups', ['id' => $group->id]); // Ensure group was not deleted
    }
}