<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use App\Policies\PartnerPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $integratorUser;
    protected $partnerUser;
    protected $regularUser;
    protected $otherIntegratorUser;
    protected $otherPartnerUser;

    protected $integrator;
    protected $otherIntegrator;
    protected $partner;
    protected $otherPartner;

    public function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->integratorUser = User::factory()->create(['role' => 'integrator']);
        $this->partnerUser = User::factory()->create(['role' => 'partner']);
        $this->regularUser = User::factory()->create(['role' => 'user']);
        $this->otherIntegratorUser = User::factory()->create(['role' => 'integrator']);
        $this->otherPartnerUser = User::factory()->create(['role' => 'partner']);

        // Create integrators and partners
        $this->integrator = Integrator::factory()->create(['id' => $this->integratorUser->integrator_id]);
        $this->otherIntegrator = Integrator::factory()->create(['id' => $this->otherIntegratorUser->integrator_id]);
        $this->partner = Partner::factory()->create(['id' => $this->partnerUser->partner_id, 'integrator_id' => $this->integrator->id]);
        $this->otherPartner = Partner::factory()->create(['id' => $this->otherPartnerUser->partner_id, 'integrator_id' => $this->otherIntegrator->id]);
    }

    /**
     * Test viewAny ability.
     */
    public function test_viewAny(): void
    {
        $policy = new PartnerPolicy();

        // Admin can view any
        $this->assertTrue($policy->viewAny($this->adminUser)->allowed());

        // Integrator can view any (if they have partners)
        $this->assertTrue($policy->viewAny($this->integratorUser)->allowed());
        $this->assertTrue($policy->viewAny($this->otherIntegratorUser)->allowed());

        // Partner and Regular user cannot view any
        $this->assertFalse($policy->viewAny($this->partnerUser)->allowed());
        $this->assertFalse($policy->viewAny($this->regularUser)->allowed());
    }

    /**
     * Test view ability.
     */
    public function test_view(): void
    {
        $policy = new PartnerPolicy();

        // Admin can view any partner
        $this->assertTrue($policy->view($this->adminUser, $this->partner)->allowed());
        $this->assertTrue($policy->view($this->adminUser, $this->otherPartner)->allowed());

        // Integrator can view their own partners
        $this->assertTrue($policy->view($this->integratorUser, $this->partner)->allowed());
        $this->assertFalse($policy->view($this->integratorUser, $this->otherPartner)->allowed());

        // Partner can view their own partner profile
        $this->assertTrue($policy->view($this->partnerUser, $this->partner)->allowed());
        $this->assertFalse($policy->view($this->partnerUser, $this->otherPartner)->allowed());

        // Regular user cannot view partners
        $this->assertFalse($policy->view($this->regularUser, $this->partner)->allowed());
        $this->assertFalse($policy->view($this->regularUser, $this->otherPartner)->allowed());
    }

    /**
     * Test create ability.
     */
    public function test_create(): void
    {
        $policy = new PartnerPolicy();

        // Admins and Integrators can create
        $this->assertTrue($policy->create($this->adminUser)->allowed());
        $this->assertTrue($policy->create($this->integratorUser)->allowed());

        // Partners and Regular users cannot create
        $this->assertFalse($policy->create($this->partnerUser)->allowed());
        $this->assertFalse($policy->create($this->regularUser)->allowed());
    }

    /**
     * Test update ability.
     */
    public function test_update(): void
    {
        $policy = new PartnerPolicy();

        // Admin can update any partner
        $this->assertTrue($policy->update($this->adminUser, $this->partner)->allowed());
        $this->assertTrue($policy->update($this->adminUser, $this->otherPartner)->allowed());

        // Integrator can update their own partners
        $this->assertTrue($policy->update($this->integratorUser, $this->partner)->allowed());
        $this->assertFalse($policy->update($this->integratorUser, $this->otherPartner)->allowed());

        // Partner can update their own partner profile
        $this->assertTrue($policy->update($this->partnerUser, $this->partner)->allowed());
        $this->assertFalse($policy->update($this->partnerUser, $this->otherPartner)->allowed());

        // Regular user cannot update
        $this->assertFalse($policy->update($this->regularUser, $this->partner)->allowed());
        $this->assertFalse($policy->update($this->regularUser, $this->otherPartner)->allowed());
    }

    /**
     * Test delete ability.
     */
    public function test_delete(): void
    {
        $policy = new PartnerPolicy();

        // Admins and Integrators can delete
        $this->assertTrue($policy->delete($this->adminUser, $this->partner)->allowed());
        $this->assertTrue($policy->delete($this->adminUser, $this->otherPartner)->allowed());
        $this->assertTrue($policy->delete($this->integratorUser, $this->partner)->allowed());
        $this->assertFalse($policy->delete($this->integratorUser, $this->otherPartner)->allowed());

        // Partners and Regular users cannot delete
        $this->assertFalse($policy->delete($this->partnerUser, $this->partner)->allowed());
        $this->assertFalse($policy->delete($this->regularUser, $this->partner)->allowed());
    }

    /**
     * Test restore ability.
     */
    public function test_restore(): void
    {
        $policy = new PartnerPolicy();

        // Admins and Integrators can restore
        $this->assertTrue($policy->restore($this->adminUser, $this->partner)->allowed());
        $this->assertTrue($policy->restore($this->adminUser, $this->otherPartner)->allowed());
        $this->assertTrue($policy->restore($this->integratorUser, $this->partner)->allowed());
        $this->assertFalse($policy->restore($this->integratorUser, $this->otherPartner)->allowed());

        // Partners and Regular users cannot restore
        $this->assertFalse($policy->restore($this->partnerUser, $this->partner)->allowed());
        $this->assertFalse($policy->restore($this->regularUser, $this->partner)->allowed());
    }

    /**
     * Test forceDelete ability.
     */
    public function test_forceDelete(): void
    {
        $policy = new PartnerPolicy();

        // Admins and Integrators can force delete
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->partner)->allowed());
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->otherPartner)->allowed());
        $this->assertTrue($policy->forceDelete($this->integratorUser, $this->partner)->allowed());
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->otherPartner)->allowed());

        // Partners and Regular users cannot force delete
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->partner)->allowed());
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->partner)->allowed());
    }

    /**
     * Test before method.
     */
    public function test_before(): void
    {
        $policy = new PartnerPolicy();

        // Admin bypasses all checks
        $this->assertTrue($policy->before($this->adminUser, 'anyAbility')->allowed());

        // Other roles do not bypass
        $this->assertNull($policy->before($this->integratorUser, 'anyAbility'));
        $this->assertNull($policy->before($this->partnerUser, 'anyAbility'));
        $this->assertNull($policy->before($this->regularUser, 'anyAbility'));
    }
}