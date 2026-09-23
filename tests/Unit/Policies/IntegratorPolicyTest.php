<?php

namespace Tests\Unit\Policies;

use App\Models\Integrator;
use App\Models\Partner;
use App\Models\User;
use App\Policies\IntegratorPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegratorPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles are seeded or mocked if not using RefreshDatabase
        // For RefreshDatabase, roles should be seeded in a seeder that runs before tests
        // Example: $this->seed(RoleSeeder::class);
    }

    // Helper to create users with specific roles and tenant IDs
    private function createUserWithRole(string $role, ?int $integratorId = null, ?int $partnerId = null): User
    {
        $user = User::factory()->create([
            'integrator_id' => $integratorId,
            'partner_id' => $partnerId,
        ]);
        $user->assignRole($role);
        return $user;
    }

    // Helper to create an Integrator
    private function createIntegrator(?int $integratorId = null): Integrator
    {
        return Integrator::factory()->create(['id' => $integratorId]);
    }

    /**
     * Test viewAny method.
     */
    public function test_admin_can_view_any_integrators(): void
    {
        $admin = $this->createUserWithRole('admin');
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->viewAny($admin));
    }

    public function test_integrator_can_view_any_integrators(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->viewAny($integratorUser));
    }

    public function test_partner_can_view_any_integrators(): void
    {
        $partnerUser = $this->createUserWithRole('partner', 1, 1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->viewAny($partnerUser));
    }

    public function test_user_can_view_any_integrators(): void
    {
        $user = $this->createUserWithRole('user', 1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->viewAny($user));
    }

    /**
     * Test view method.
     */
    public function test_admin_can_view_any_specific_integrator(): void
    {
        $admin = $this->createUserWithRole('admin');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->view($admin, $integrator));
    }

    public function test_integrator_can_view_own_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->view($integratorUser, $integrator));
    }

    public function test_integrator_cannot_view_other_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(2); // Different integrator
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->view($integratorUser, $integrator));
    }

    public function test_partner_can_view_associated_integrator(): void
    {
        $integrator = $this->createIntegrator(1);
        $partnerUser = $this->createUserWithRole('partner', $integrator->id, 1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->view($partnerUser, $integrator));
    }

    public function test_partner_cannot_view_unassociated_integrator(): void
    {
        $integrator = $this->createIntegrator(1);
        $partnerUser = $this->createUserWithRole('partner', 2, 1); // Associated with different integrator
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->view($partnerUser, $integrator));
    }

    public function test_user_can_view_associated_integrator(): void
    {
        $integrator = $this->createIntegrator(1);
        $user = $this->createUserWithRole('user', $integrator->id);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->view($user, $integrator));
    }

    public function test_user_cannot_view_unassociated_integrator(): void
    {
        $integrator = $this->createIntegrator(1);
        $user = $this->createUserWithRole('user', 2); // Associated with different integrator
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->view($user, $integrator));
    }

    /**
     * Test create method.
     */
    public function test_admin_can_create_integrator(): void
    {
        $admin = $this->createUserWithRole('admin');
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->create($admin));
    }

    public function test_integrator_cannot_create_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->create($integratorUser));
    }

    public function test_partner_cannot_create_integrator(): void
    {
        $partnerUser = $this->createUserWithRole('partner', null, 1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->create($partnerUser));
    }

    public function test_user_cannot_create_integrator(): void
    {
        $user = $this->createUserWithRole('user');
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->create($user));
    }

    /**
     * Test update method.
     */
    public function test_admin_can_update_integrator(): void
    {
        $admin = $this->createUserWithRole('admin');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->update($admin, $integrator));
    }

    public function test_integrator_can_update_own_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->update($integratorUser, $integrator));
    }

    public function test_integrator_cannot_update_other_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(2); // Different integrator
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->update($integratorUser, $integrator));
    }

    public function test_partner_cannot_update_integrator(): void
    {
        $partnerUser = $this->createUserWithRole('partner', null, 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->update($partnerUser, $integrator));
    }

    public function test_user_cannot_update_integrator(): void
    {
        $user = $this->createUserWithRole('user');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->update($user, $integrator));
    }

    /**
     * Test delete method.
     */
    public function test_admin_can_delete_integrator(): void
    {
        $admin = $this->createUserWithRole('admin');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->delete($admin, $integrator));
    }

    public function test_integrator_cannot_delete_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->delete($integratorUser, $integrator));
    }

    public function test_partner_cannot_delete_integrator(): void
    {
        $partnerUser = $this->createUserWithRole('partner', null, 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->delete($partnerUser, $integrator));
    }

    public function test_user_cannot_delete_integrator(): void
    {
        $user = $this->createUserWithRole('user');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->delete($user, $integrator));
    }

    /**
     * Test restore method.
     */
    public function test_admin_can_restore_integrator(): void
    {
        $admin = $this->createUserWithRole('admin');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->restore($admin, $integrator));
    }

    public function test_integrator_cannot_restore_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->restore($integratorUser, $integrator));
    }

    public function test_partner_cannot_restore_integrator(): void
    {
        $partnerUser = $this->createUserWithRole('partner', null, 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->restore($partnerUser, $integrator));
    }

    public function test_user_cannot_restore_integrator(): void
    {
        $user = $this->createUserWithRole('user');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->restore($user, $integrator));
    }

    /**
     * Test forceDelete method.
     */
    public function test_admin_can_force_delete_integrator(): void
    {
        $admin = $this->createUserWithRole('admin');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertTrue($policy->forceDelete($admin, $integrator));
    }

    public function test_integrator_cannot_force_delete_integrator(): void
    {
        $integratorUser = $this->createUserWithRole('integrator', 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->forceDelete($integratorUser, $integrator));
    }

    public function test_partner_cannot_force_delete_integrator(): void
    {
        $partnerUser = $this->createUserWithRole('partner', null, 1);
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->forceDelete($partnerUser, $integrator));
    }

    public function test_user_cannot_force_delete_integrator(): void
    {
        $user = $this->createUserWithRole('user');
        $integrator = $this->createIntegrator(1);
        $policy = new IntegratorPolicy();
        $this->assertFalse($policy->forceDelete($user, $integrator));
    }
}
