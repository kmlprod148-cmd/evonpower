<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\BusinessProfile;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class ChargingPointCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $integratorUser;
    protected User $partnerUser;
    protected Integrator $integrator;
    protected Partner $partner;
    protected Group $group;
    protected BusinessProfile $businessProfile;

    protected function setUp(): void
    {
        parent::setUp();
        // The RefreshDatabase trait handles migrations and seeding.
        // Manual db:seed call is removed to prevent conflicts.

        // Create necessary entities
        $this->businessProfile = BusinessProfile::factory()->create();
        $this->integrator = Integrator::factory()->create([
            'business_profile_id' => $this->businessProfile->id,
        ]);
        $this->partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
            'business_profile_id' => $this->businessProfile->id,
        ]);
        $this->group = Group::factory()->create([
            'integrator_id' => $this->integrator->id,
            'partner_id' => $this->partner->id,
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
            'integrator_id' => $this->integrator->id,
        ]);
        $this->partnerUser->assignRole('partner');
    }

    #[Test]
    public function admin_can_view_charging_points()
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('charging-points.index'));

        $response->assertOk();
        $response->assertViewIs('charging-points.index');
        $response->assertViewHas('chargingPoints');
    }

    #[Test]
    public function integrator_can_view_charging_points_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $response = $this->get(route('charging-points.index'));

        $response->assertOk();
        $response->assertViewIs('charging-points.index');
        $response->assertViewHas('chargingPoints');

        // Add assertions to check if only relevant charging points are shown (requires repository logic)
    }

    #[Test]
    public function partner_can_view_charging_points_within_their_partner()
    {
        $this->actingAs($this->partnerUser);
        $response = $this->get(route('charging-points.index'));

        $response->assertOk();
        $response->assertViewIs('charging-points.index');
        $response->assertViewHas('chargingPoints');

        // Add assertions to check if only relevant charging points are shown (requires repository logic)
    }

    #[Test]
    public function admin_can_create_charging_point()
    {
        $this->actingAs($this->adminUser);

        $chargingPointData = ChargingPoint::factory()->make([
            'integrator_id' => $this->integrator->id,
            'partner_id' => $this->partner->id,
            'group_id' => $this->group->id,
            'user_id' => $this->adminUser->id, // User creating the charging point
        ])->toArray();

        $response = $this->post(route('charging-points.store'), $chargingPointData);

        $response->assertRedirect(route('charging-points.show', ['chargingPoint' => ChargingPoint::latest()->first()->id]));
        $this->assertDatabaseHas('charging_points', ['serial_number' => $chargingPointData['serial_number']]);
    }

    #[Test]
    public function integrator_can_create_charging_point_within_their_integration()
    {
        $this->actingAs($this->integratorUser);

        $chargingPointData = ChargingPoint::factory()->make([
            'integrator_id' => $this->integrator->id, // Should be automatically set by service
            'partner_id' => $this->partner->id, // Integrator can assign to their partners
            'group_id' => $this->group->id, // Integrator can assign to groups within their integration
            'user_id' => $this->integratorUser->id, // User creating the charging point
        ])->toArray();

        // Remove fields that should be automatically set by the service
        unset($chargingPointData['integrator_id']);
        unset($chargingPointData['user_id']);

        $response = $this->post(route('charging-points.store'), $chargingPointData);

        $response->assertRedirect(route('charging-points.show', ['chargingPoint' => ChargingPoint::latest()->first()->id]));
        $this->assertDatabaseHas('charging_points', [
            'serial_number' => $chargingPointData['serial_number'],
            'integrator_id' => $this->integrator->id, // Verify integrator_id is set correctly
            'user_id' => $this->integratorUser->id, // Verify user_id is set correctly
        ]);
    }

    #[Test]
    public function integrator_cannot_create_charging_point_for_other_integrators_partner()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $otherPartner = Partner::factory()->create([
            'integrator_id' => $otherIntegrator->id,
        ]);

        $chargingPointData = ChargingPoint::factory()->make([
            'integrator_id' => $this->integrator->id,
            'partner_id' => $otherPartner->id, // Attempting to assign to another integrator's partner
            'group_id' => $this->group->id,
            'user_id' => $this->integratorUser->id,
        ])->toArray();

        // Remove fields that should be automatically set by the service
        unset($chargingPointData['integrator_id']);
        unset($chargingPointData['user_id']);

        $response = $this->post(route('charging-points.store'), $chargingPointData);

        $response->assertStatus(400); // Assuming a 400 Bad Request or similar for business logic error
        $this->assertDatabaseMissing('charging_points', ['serial_number' => $chargingPointData['serial_number']]);
    }

    #[Test]
    public function partner_can_create_charging_point_within_their_partner_and_group()
    {
        $this->actingAs($this->partnerUser);

        $chargingPointData = ChargingPoint::factory()->make([
            'integrator_id' => $this->integrator->id, // Should be automatically set by service
            'partner_id' => $this->partner->id, // Should be automatically set by service
            'group_id' => $this->group->id, // Partner can assign to their groups
            'user_id' => $this->partnerUser->id, // User creating the charging point
        ])->toArray();

        // Remove fields that should be automatically set by the service
        unset($chargingPointData['integrator_id']);
        unset($chargingPointData['partner_id']);
        unset($chargingPointData['user_id']);

        $response = $this->post(route('charging-points.store'), $chargingPointData);

        $response->assertRedirect(route('charging-points.show', ['chargingPoint' => ChargingPoint::latest()->first()->id]));
        $this->assertDatabaseHas('charging_points', [
            'serial_number' => $chargingPointData['serial_number'],
            'integrator_id' => $this->integrator->id, // Verify integrator_id is set correctly
            'partner_id' => $this->partner->id, // Verify partner_id is set correctly
            'user_id' => $this->partnerUser->id, // Verify user_id is set correctly
        ]);
    }

    #[Test]
    public function partner_cannot_create_charging_point_for_other_partners_group()
    {
        $this->actingAs($this->partnerUser);
        $otherPartner = Partner::factory()->create();
        $otherGroup = Group::factory()->create([
            'partner_id' => $otherPartner->id,
        ]);

        $chargingPointData = ChargingPoint::factory()->make([
            'integrator_id' => $this->integrator->id,
            'partner_id' => $this->partner->id,
            'group_id' => $otherGroup->id, // Attempting to assign to another partner's group
            'user_id' => $this->partnerUser->id,
        ])->toArray();

        // Remove fields that should be automatically set by the service
        unset($chargingPointData['integrator_id']);
        unset($chargingPointData['partner_id']);
        unset($chargingPointData['user_id']);

        $response = $this->post(route('charging-points.store'), $chargingPointData);

        $response->assertStatus(400); // Assuming a 400 Bad Request or similar for business logic error
        $this->assertDatabaseMissing('charging_points', ['serial_number' => $chargingPointData['serial_number']]);
    }


    #[Test]
    public function admin_can_view_charging_point_details()
    {
        $this->actingAs($this->adminUser);
        $chargingPoint = ChargingPoint::factory()->create();

        $response = $this->get(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));

        $response->assertOk();
        $response->assertViewIs('charging-points.show');
        $response->assertViewHas('chargingPoint', $chargingPoint);
    }

    #[Test]
    public function integrator_can_view_charging_point_details_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $chargingPoint = ChargingPoint::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);

        $response = $this->get(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));

        $response->assertOk();
        $response->assertViewIs('charging-points.show');
        $response->assertViewHas('chargingPoint', $chargingPoint);
    }

    #[Test]
    public function integrator_cannot_view_charging_point_details_outside_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create([
            'integrator_id' => $otherIntegrator->id,
        ]);

        $response = $this->get(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function partner_can_view_charging_point_details_within_their_partner()
    {
        $this->actingAs($this->partnerUser);
        $chargingPoint = ChargingPoint::factory()->create([
            'partner_id' => $this->partner->id,
        ]);

        $response = $this->get(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));

        $response->assertOk();
        $response->assertViewIs('charging-points.show');
        $response->assertViewHas('chargingPoint', $chargingPoint);
    }

    #[Test]
    public function partner_cannot_view_charging_point_details_outside_their_partner()
    {
        $this->actingAs($this->partnerUser);
        $otherPartner = Partner::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create([
            'partner_id' => $otherPartner->id,
        ]);

        $response = $this->get(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function admin_can_update_charging_point()
    {
        $this->actingAs($this->adminUser);
        $chargingPoint = ChargingPoint::factory()->create();
        $newData = $chargingPoint->toArray();
        $newData['name'] = 'Updated Charging Point Name';
        $newData['serial_number'] = $chargingPoint->serial_number;
        $newData['manufacturer'] = $chargingPoint->manufacturer ?? 'EVON';
        $newData['model'] = $chargingPoint->model ?? 'Model X';
        $newData['status'] = 'online';
        $response = $this->put(route('charging-points.update', $chargingPoint), $newData);
        $response->assertRedirect(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));
        $this->assertDatabaseHas('charging_points', array_merge(['id' => $chargingPoint->id], ['name' => 'Updated Charging Point Name']));
    }

    #[Test]
    public function integrator_can_update_charging_point_within_their_integration()
    {
        $chargingPoint = ChargingPoint::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->actingAs($this->integratorUser);
        $newData = $chargingPoint->toArray();
        $newData['name'] = 'Updated Charging Point Name';
        $newData['serial_number'] = $chargingPoint->serial_number;
        $newData['manufacturer'] = $chargingPoint->manufacturer ?? 'EVON';
        $newData['model'] = $chargingPoint->model ?? 'Model X';
        $newData['status'] = 'online';
        $response = $this->put(route('charging-points.update', $chargingPoint), $newData);
        $response->assertRedirect(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));
        $this->assertDatabaseHas('charging_points', array_merge(['id' => $chargingPoint->id], ['name' => 'Updated Charging Point Name']));
    }

    #[Test]
    public function integrator_cannot_update_charging_point_outside_their_integration()
    {
        $otherChargingPoint = ChargingPoint::factory()->create();
        $this->actingAs($this->integratorUser);
        $newData = $otherChargingPoint->toArray();
        $newData['name'] = 'Attempted Update';
        $newData['serial_number'] = $otherChargingPoint->serial_number;
        $newData['manufacturer'] = $otherChargingPoint->manufacturer ?? 'EVON';
        $newData['model'] = $otherChargingPoint->model ?? 'Model X';
        $newData['status'] = 'online';
        $response = $this->put(route('charging-points.update', $otherChargingPoint), $newData);
        $response->assertForbidden();
        $this->assertDatabaseMissing('charging_points', array_merge(['id' => $otherChargingPoint->id], ['name' => 'Attempted Update']));
    }

    #[Test]
    public function partner_can_update_own_charging_point()
    {
        $chargingPoint = ChargingPoint::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->actingAs($this->partnerUser);
        $newData = $chargingPoint->toArray();
        $newData['name'] = 'Updated My Charging Point Name';
        $newData['serial_number'] = $chargingPoint->serial_number;
        $newData['manufacturer'] = $chargingPoint->manufacturer ?? 'EVON';
        $newData['model'] = $chargingPoint->model ?? 'Model X';
        $newData['status'] = 'online';
        $response = $this->put(route('charging-points.update', $chargingPoint), $newData);
        $response->assertRedirect(route('charging-points.show', ['chargingPoint' => $chargingPoint->id]));
        $this->assertDatabaseHas('charging_points', array_merge(['id' => $chargingPoint->id], ['name' => 'Updated My Charging Point Name']));
    }

    #[Test]
    public function partner_cannot_update_other_charging_point()
    {
        $otherChargingPoint = ChargingPoint::factory()->create();
        $this->actingAs($this->partnerUser);
        $newData = $otherChargingPoint->toArray();
        $newData['name'] = 'Attempted Update Other';
        $newData['serial_number'] = $otherChargingPoint->serial_number;
        $newData['manufacturer'] = $otherChargingPoint->manufacturer ?? 'EVON';
        $newData['model'] = $otherChargingPoint->model ?? 'Model X';
        $newData['status'] = 'online';
        $response = $this->put(route('charging-points.update', $otherChargingPoint), $newData);
        $response->assertForbidden();
        $this->assertDatabaseMissing('charging_points', array_merge(['id' => $otherChargingPoint->id], ['name' => 'Attempted Update Other']));
    }

    #[Test]
    public function admin_can_delete_charging_point()
    {
        $chargingPoint = ChargingPoint::factory()->create();
        $this->actingAs($this->adminUser);
        $response = $this->delete(route('charging-points.destroy', $chargingPoint));
        $response->assertRedirect(route('charging-points.index'));
        // Soft delete : vérifier que deleted_at est non null
        $this->assertSoftDeleted('charging_points', ['id' => $chargingPoint->id]);
    }

    #[Test]
    public function integrator_can_delete_charging_point_within_their_integration()
    {
        $chargingPoint = ChargingPoint::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->actingAs($this->integratorUser);
        $response = $this->delete(route('charging-points.destroy', $chargingPoint));
        $response->assertRedirect(route('charging-points.index'));
        $this->assertSoftDeleted('charging_points', ['id' => $chargingPoint->id]);
    }

    #[Test]
    public function integrator_cannot_delete_charging_point_outside_their_integration()
    {
        $otherChargingPoint = ChargingPoint::factory()->create();
        $this->actingAs($this->integratorUser);
        $response = $this->delete(route('charging-points.destroy', $otherChargingPoint));
        $response->assertForbidden();
        $this->assertDatabaseHas('charging_points', ['id' => $otherChargingPoint->id]);
    }

    #[Test]
    public function partner_can_delete_own_charging_point()
    {
        $chargingPoint = ChargingPoint::factory()->create([
            'partner_id' => $this->partner->id,
        ]);
        $this->actingAs($this->partnerUser);
        $response = $this->delete(route('charging-points.destroy', $chargingPoint));
        $response->assertRedirect(route('charging-points.index'));
        $this->assertSoftDeleted('charging_points', ['id' => $chargingPoint->id]);
    }

    #[Test]
    public function partner_cannot_delete_other_charging_point()
    {
        $otherChargingPoint = ChargingPoint::factory()->create();
        $this->actingAs($this->partnerUser);
        $response = $this->delete(route('charging-points.destroy', $otherChargingPoint));
        $response->assertForbidden();
        $this->assertDatabaseHas('charging_points', ['id' => $otherChargingPoint->id]);
    }
}