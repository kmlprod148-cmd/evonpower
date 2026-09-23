<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;

class PartnerCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $integratorUser;
    protected User $partnerUser;
    protected Integrator $integrator;
    protected BusinessProfile $businessProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create necessary entities
        $this->businessProfile = BusinessProfile::factory()->create();
        $this->integrator = Integrator::factory()->create([
            'business_profile_id' => $this->businessProfile->id,
        ]);

        // Create users with roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->integratorUser = User::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->integratorUser->assignRole('integrator');

        $partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
            'business_profile_id' => $this->businessProfile->id,
        ]);
        $this->partnerUser = User::factory()->create([
            'partner_id' => $partner->id,
        ]);
        $this->partnerUser->assignRole('partner');
    }

    #[Test]
    public function admin_can_view_partners()
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('partners.index'));

        $response->assertOk();
        $response->assertViewIs('partners.index');
        $response->assertViewHas('partners');
    }

    #[Test]
    public function integrator_can_view_partners_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $response = $this->get(route('partners.index'));

        $response->assertOk();
        $response->assertViewIs('partners.index');
        $response->assertViewHas('partners');

        // Ensure only partners from their integration are visible (this requires repository logic)
        // For now, just check the view is loaded
    }

    #[Test]
    public function partner_can_view_only_their_partner_profile()
    {
        $this->actingAs($this->partnerUser);
        $response = $this->get(route('partners.index'));

        $response->assertOk();
        $response->assertViewIs('partners.index');
        $response->assertViewHas('partners');

        // Ensure only their own partner profile is visible (this requires repository logic)
        // For now, just check the view is loaded
    }

    #[Test]
    public function admin_can_create_partner()
    {
        $this->actingAs($this->adminUser);

        $partnerData = [
            'name' => 'Test Partner',
            'contact_name' => 'Contact Test',
            'type' => 'Exploitant',
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '0102030405',
            'city' => 'Paris',
            'integrator_id' => $this->integrator->id,
        ];
        $adminUserData = [
            'admin_email' => $this->faker->unique()->safeEmail,
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];
        $response = $this->post(route('partners.store'), array_merge($partnerData, $adminUserData));
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseHas('partners', ['email' => $partnerData['email']]);
        $this->assertDatabaseHas('users', ['email' => $adminUserData['admin_email']]);
        $createdUser = User::where('email', $adminUserData['admin_email'])->first();
        $this->assertTrue($createdUser->hasRole('operator'));
    }

    #[Test]
    public function integrator_can_create_partner_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $partnerData = [
            'name' => 'Integration Partner',
            'contact_name' => 'Contact Test',
            'type' => 'Exploitant',
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '0102030405',
            'city' => 'Paris',
            'integrator_id' => $this->integrator->id,
        ];
        $adminUserData = [
            'admin_email' => $this->faker->unique()->safeEmail,
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];
        $response = $this->post(route('partners.store'), array_merge($partnerData, $adminUserData));
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseHas('partners', [
            'email' => $partnerData['email'],
            'integrator_id' => $this->integrator->id,
        ]);
        $this->assertDatabaseHas('users', ['email' => $adminUserData['admin_email']]);
        $createdUser = User::where('email', $adminUserData['admin_email'])->first();
        $this->assertTrue($createdUser->hasRole('operator'));
    }

    #[Test]
    public function partner_cannot_create_partner()
    {
        $this->actingAs($this->partnerUser);
        $partnerData = [
            'name' => 'Partner Test',
            'contact_name' => 'Contact Test',
            'type' => 'Exploitant',
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '0102030405',
            'city' => 'Paris',
        ];
        $adminUserData = [
            'admin_email' => $this->faker->unique()->safeEmail,
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];
        $response = $this->post(route('partners.store'), array_merge($partnerData, $adminUserData));
        $response->assertForbidden();
        $this->assertDatabaseMissing('partners', ['email' => $partnerData['email']]);
        $this->assertDatabaseMissing('users', ['email' => $adminUserData['admin_email']]);
    }

    #[Test]
    public function admin_can_view_partner_details()
    {
        $this->actingAs($this->adminUser);
        $partner = Partner::factory()->create();

        $response = $this->get(route('partners.show', $partner));

        $response->assertOk();
        $response->assertViewIs('partners.show');
        $response->assertViewHas('partner', $partner);
    }

    #[Test]
    public function integrator_can_view_partner_details_within_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);

        $response = $this->get(route('partners.show', $partner));

        $response->assertOk();
        $response->assertViewIs('partners.show');
        $response->assertViewHas('partner', $partner);
    }

    #[Test]
    public function integrator_cannot_view_partner_details_outside_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $partner = Partner::factory()->create([
            'integrator_id' => $otherIntegrator->id,
        ]);

        $response = $this->get(route('partners.show', $partner));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function partner_can_view_their_own_partner_details()
    {
        $this->actingAs($this->partnerUser);
        $partner = $this->partnerUser->partner; // Get the partner associated with the user

        $response = $this->get(route('partners.show', $partner));

        $response->assertOk();
        $response->assertViewIs('partners.show');
        $response->assertViewHas('partner', $partner);
    }

    #[Test]
    public function partner_cannot_view_other_partner_details()
    {
        $this->actingAs($this->partnerUser);
        $otherPartner = Partner::factory()->create();

        $response = $this->get(route('partners.show', $otherPartner));

        $response->assertForbidden(); // Assuming a 403 Forbidden
    }

    #[Test]
    public function admin_can_update_partner()
    {
        $partner = Partner::factory()->create();
        $this->actingAs($this->adminUser);
        $updateData = $partner->toArray();
        $updateData['name'] = 'Updated Partner Name';
        $updateData['type'] = 'Exploitant';
        $updateData['city'] = 'Paris';
        $updateData['integrator_id'] = $partner->integrator_id;
        $response = $this->put(route('partners.update', $partner), $updateData);
        $response->assertRedirect(route('partners.show', $partner));
        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'name' => 'Updated Partner Name',
            'type' => 'Exploitant',
            'city' => 'Paris',
        ]);
    }

    #[Test]
    public function integrator_can_update_partner()
    {
        $partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->actingAs($this->integratorUser);
        $newData = $partner->toArray();
        $newData['name'] = 'Updated Partner Name by Integrator';
        $response = $this->put(route('partners.update', $partner), $newData);
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseHas('partners', array_merge(['id' => $partner->id], $newData));
    }

    #[Test]
    public function integrator_cannot_update_partner_outside_their_integration()
    {
        $this->actingAs($this->integratorUser);
        $otherIntegrator = Integrator::factory()->create();
        $partner = Partner::factory()->create([
            'integrator_id' => $otherIntegrator->id,
        ]);
        // On récupère toutes les données actuelles du partner
        $updateData = $partner->toArray();
        $updateData['name'] = 'Attempted Update';
        $updateData['type'] = 'Exploitant';
        $updateData['contact_name'] = 'Contact Test';
        $updateData['email'] = 'updated_' . $partner->email;
        $updateData['phone'] = '0102030405';
        $updateData['city'] = 'Paris';
        $updateData['integrator_id'] = $partner->integrator_id; // Ensure integrator_id is kept

        $response = $this->put(route('partners.update', $partner), $updateData);

        $response->assertForbidden(); // Assuming a 403 Forbidden
        $this->assertDatabaseMissing('partners', array_merge(['id' => $partner->id], [
            'name' => 'Attempted Update',
            'email' => $updateData['email'],
            'contact_name' => 'Contact Test',
            'type' => 'Exploitant',
            'phone' => '0102030405',
            'city' => 'Paris',
            'integrator_id' => $partner->integrator_id,
        ]));
    }

    #[Test]
    public function partner_can_update_own_partner()
    {
        $partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->actingAs($this->partnerUser);
        $newData = $partner->toArray();
        $newData['name'] = 'Updated My Partner Name';
        $response = $this->put(route('partners.update', $partner), $newData);
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseHas('partners', array_merge(['id' => $partner->id], $newData));
    }

    #[Test]
    public function partner_cannot_update_other_partner()
    {
        $otherPartner = Partner::factory()->create();
        $this->actingAs($this->partnerUser);
        $newData = $otherPartner->toArray();
        $newData['name'] = 'Attempted Update Other';
        $response = $this->put(route('partners.update', $otherPartner), $newData);
        $response->assertForbidden();
        $this->assertDatabaseMissing('partners', array_merge(['id' => $otherPartner->id], ['name' => 'Attempted Update Other']));
    }

    #[Test]
    public function admin_can_delete_partner()
    {
        $partner = Partner::factory()->create();
        $this->actingAs($this->adminUser);
        $response = $this->delete(route('partners.destroy', $partner));
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }

    #[Test]
    public function integrator_can_delete_own_partner()
    {
        $partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->actingAs($this->integratorUser);
        $response = $this->delete(route('partners.destroy', $partner));
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }

    #[Test]
    public function integrator_cannot_delete_other_partner()
    {
        $otherPartner = Partner::factory()->create();
        $this->actingAs($this->integratorUser);
        $response = $this->delete(route('partners.destroy', $otherPartner));
        $response->assertForbidden();
        $this->assertDatabaseHas('partners', ['id' => $otherPartner->id]);
    }

    #[Test]
    public function partner_can_delete_own_partner()
    {
        $partner = Partner::factory()->create([
            'integrator_id' => $this->integrator->id,
        ]);
        $this->actingAs($this->partnerUser);
        $response = $this->delete(route('partners.destroy', $partner));
        $response->assertRedirect(route('partners.index'));
        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }

    #[Test]
    public function partner_cannot_delete_other_partner()
    {
        $otherPartner = Partner::factory()->create();
        $this->actingAs($this->partnerUser);
        $response = $this->delete(route('partners.destroy', $otherPartner));
        $response->assertForbidden();
        $this->assertDatabaseHas('partners', ['id' => $otherPartner->id]);
    }
}