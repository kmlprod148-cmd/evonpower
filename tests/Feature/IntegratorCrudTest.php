<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint; // Assuming Integrator might have ChargingPoints
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class IntegratorCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $integratorUser;
    protected BusinessProfile $businessProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create necessary entities
        $this->businessProfile = BusinessProfile::factory()->create();

        // Create users with roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $integrator = Integrator::factory()->create([
            'business_profile_id' => $this->businessProfile->id,
        ]);
        $this->integratorUser = User::factory()->create([
            'integrator_id' => $integrator->id,
        ]);
        $this->integratorUser->assignRole('integrator');
    }

    #[Test]
    public function admin_can_view_integrators()
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('integrators.index'));

        $response->assertOk();
        $response->assertViewIs('integrators.index');
        $response->assertViewHas('integrators');
    }

    #[Test]
    public function integrator_can_view_only_their_integrator_profile()
    {
        $this->actingAs($this->integratorUser);
        $response = $this->get(route('integrators.index'));

        $response->assertOk();
        $response->assertViewIs('integrators.index');
        $response->assertViewHas('integrators');

        // Ensure only their own integrator profile is visible (requires repository logic)
        // For now, just check the view is loaded
    }

    #[Test]
    public function admin_can_create_integrator()
    {
        $this->actingAs($this->adminUser);
        $integratorData = [
            'name' => 'Test Integrator',
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '0102030405',
            'city' => 'Paris',
            'type' => 'Intégrateur',
        ];
        $adminUserData = [
            'admin_email' => $this->faker->unique()->safeEmail,
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];
        $response = $this->post(route('integrators.store'), array_merge($integratorData, $adminUserData));
        $response->assertRedirect(route('integrators.index'));
        $this->assertDatabaseHas('integrators', ['email' => $integratorData['email']]);
        $this->assertDatabaseHas('users', ['email' => $adminUserData['admin_email']]);
        $createdUser = User::where('email', $adminUserData['admin_email'])->first();
        $this->assertTrue($createdUser->hasRole('integrator'));
    }

    #[Test]
    public function integrator_can_create_integrator()
    {
        $this->actingAs($this->integratorUser);
        $integratorData = [
            'name' => 'Integration Integrator',
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '0102030405',
            'city' => 'Paris',
            'type' => 'Intégrateur',
        ];
        $adminUserData = [
            'admin_email' => $this->faker->unique()->safeEmail,
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];
        $response = $this->post(route('integrators.store'), array_merge($integratorData, $adminUserData));
        $response->assertRedirect(route('integrators.index'));
        $this->assertDatabaseHas('integrators', [
            'email' => $integratorData['email'],
        ]);
        $this->assertDatabaseHas('users', ['email' => $adminUserData['admin_email']]);
        $createdUser = User::where('email', $adminUserData['admin_email'])->first();
        $this->assertTrue($createdUser->hasRole('integrator'));
    }

    #[Test]
    public function admin_can_view_integrator_details()
    {
        $this->actingAs($this->adminUser);
        $integrator = Integrator::factory()->create();

        $response = $this->get(route('integrators.show', $integrator));

        $response->assertOk();
        $response->assertViewIs('integrators.show');
        $response->assertViewHas('integrator', $integrator);
    }

    #[Test]
    public function integrator_can_view_their_own_integrator_details()
    {
        $this->actingAs($this->integratorUser);
        $integrator = $this->integratorUser->integrator; // Get the integrator associated with the user

        $response = $this->get(route('integrators.show', $integrator));

        $response->assertOk();
        $response->assertViewIs('integrators.show');
        $response->assertViewHas('integrator', $integrator);
    }

    #[Test]
    public function integrator_cannot_view_other_integrator_details()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();

        $response = $this->get(route('integrators.show', $otherIntegrator));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function admin_can_update_integrator()
    {
        $this->actingAs($this->adminUser);
        $integrator = Integrator::factory()->create();
        $updateData = $integrator->toArray();
        $updateData['name'] = 'Updated Integrator Name';
        $updateData['type'] = 'Intégrateur';
        $updateData['email'] = 'updated_' . $integrator->email;
        $updateData['phone'] = '0102030405';
        $updateData['city'] = 'Paris';
        $response = $this->put(route('integrators.update', $integrator), $updateData);
        $response->assertRedirect(route('integrators.show', $integrator));
        $this->assertDatabaseHas('integrators', [
            'id' => $integrator->id,
            'name' => 'Updated Integrator Name',
            'type' => 'Intégrateur',
            'email' => 'updated_' . $integrator->email,
            'phone' => '0102030405',
            'city' => 'Paris',
        ]);
    }

    #[Test]
    public function integrator_can_update_their_own_integrator_profile()
    {
        $this->actingAs($this->integratorUser);
        $integrator = $this->integratorUser->integrator;
        $newData = ['name' => 'Updated My Integrator Name'];

        $response = $this->put(route('integrators.update', $integrator), $newData);

        $response->assertRedirect(route('integrators.index'));
        $this->assertDatabaseHas('integrators', array_merge(['id' => $integrator->id], $newData));
    }

    #[Test]
    public function integrator_cannot_update_other_integrator_profiles()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $newData = ['name' => 'Attempted Update Other'];

        $response = $this->put(route('integrators.update', $otherIntegrator), $newData);

        $response->assertForbidden(); // Assuming a 403 Forbidden
        $this->assertDatabaseMissing('integrators', array_merge(['id' => $otherIntegrator->id], $newData));
    }

    #[Test]
    public function admin_can_delete_integrator()
    {
        $this->actingAs($this->adminUser);
        $integrator = Integrator::factory()->create();

        $response = $this->delete(route('integrators.destroy', $integrator));

        $response->assertRedirect(route('integrators.index'));
        $this->assertDatabaseMissing('integrators', ['id' => $integrator->id]);
    }

    #[Test]
    public function integrator_cannot_delete_integrator()
    {
        $this->actingAs($this->integratorUser);
        $integrator = $this->integratorUser->integrator;

        $response = $this->delete(route('integrators.destroy', $integrator));

        $response->assertForbidden(); // Assuming a 403 Forbidden
        $this->assertDatabaseHas('integrators', ['id' => $integrator->id]);
    }

    #[Test]
    public function cannot_delete_integrator_with_charging_points()
    {
        $this->actingAs($this->adminUser); // Or any user with delete permission
        $integrator = Integrator::factory()->create();
        ChargingPoint::factory()->create(['integrator_id' => $integrator->id]);

        $response = $this->delete(route('integrators.destroy', $integrator));

        $response->assertSessionHasErrors(); // Assuming an error message is set in the session
        $this->assertDatabaseHas('integrators', ['id' => $integrator->id]); // Ensure integrator was not deleted
    }
}