<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Group;
use App\Models\Partner;
use App\Policies\GroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $integratorUser;
    protected $partnerUser;
    protected $regularUser;
    protected $otherIntegratorUser;
    protected $otherPartnerUser;
    protected $otherRegularUser;

    protected $integratorPartner;
    protected $otherIntegratorPartner;
    protected $partnerPartner;
    protected $otherPartnerPartner;

    protected $integratorGroup;
    protected $partnerGroup;
    protected $userGroup;
    protected $otherIntegratorGroup;
    protected $otherPartnerGroup;
    protected $otherUserGroup;

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
        $this->otherRegularUser = User::factory()->create(['role' => 'user']);

        // Create partners associated with integrators and partners
        $this->integratorPartner = Partner::factory()->create(['integrator_id' => $this->integratorUser->integrator_id]);
        $this->otherIntegratorPartner = Partner::factory()->create(['integrator_id' => $this->otherIntegratorUser->integrator_id]);
        $this->partnerPartner = Partner::factory()->create(['id' => $this->partnerUser->partner_id]);
        $this->otherPartnerPartner = Partner::factory()->create(['id' => $this->otherPartnerUser->partner_id]);

        // Create groups with different associations
        $this->integratorGroup = Group::factory()->create(['partner_id' => $this->integratorPartner->id]);
        $this->partnerGroup = Group::factory()->create(['partner_id' => $this->partnerPartner->id]);
        $this->userGroup = Group::factory()->create();
        $this->userGroup->users()->attach($this->regularUser);
        $this->otherIntegratorGroup = Group::factory()->create(['partner_id' => $this->otherIntegratorPartner->id]);
        $this->otherPartnerGroup = Group::factory()->create(['partner_id' => $this->otherPartnerPartner->id]);
        $this->otherUserGroup = Group::factory()->create();
        $this->otherUserGroup->users()->attach($this->otherRegularUser);
    }

    /**
     * Test viewAny ability.
     */
    public function test_viewAny(): void
    {
        $policy = new GroupPolicy();

        // Admin can view any
        $this->assertTrue($policy->viewAny($this->adminUser));

        // Integrator can view any (if they have associated partners with groups)
        $this->assertTrue($policy->viewAny($this->integratorUser));
        $this->assertTrue($policy->viewAny($this->otherIntegratorUser));

        // Partner can view any (if they have groups)
        $this->assertTrue($policy->viewAny($this->partnerUser));
        $this->assertTrue($policy->viewAny($this->otherPartnerUser));

        // Regular user cannot view any (based on current policy logic)
        $this->assertFalse($policy->viewAny($this->regularUser));
    }

    /**
     * Test view ability.
     */
    public function test_view(): void
    {
        $policy = new GroupPolicy();

        // Admin can view any group
        $this->assertTrue($policy->view($this->adminUser, $this->integratorGroup));
        $this->assertTrue($policy->view($this->adminUser, $this->partnerGroup));
        $this->assertTrue($policy->view($this->adminUser, $this->userGroup));

        // Integrator can view groups associated with their partners
        $this->assertTrue($policy->view($this->integratorUser, $this->integratorGroup));
        $this->assertFalse($policy->view($this->integratorUser, $this->partnerGroup));
        $this->assertFalse($policy->view($this->integratorUser, $this->userGroup));
        $this->assertFalse($policy->view($this->integratorUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->view($this->integratorUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->view($this->integratorUser, $this->otherUserGroup));

        // Partner can view their own groups
        $this->assertFalse($policy->view($this->partnerUser, $this->integratorGroup));
        $this->assertTrue($policy->view($this->partnerUser, $this->partnerGroup));
        $this->assertFalse($policy->view($this->partnerUser, $this->userGroup));
        $this->assertFalse($policy->view($this->partnerUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->view($this->partnerUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->view($this->partnerUser, $this->otherUserGroup));

        // Regular user can view groups they are a member of
        $this->assertFalse($policy->view($this->regularUser, $this->integratorGroup));
        $this->assertFalse($policy->view($this->regularUser, $this->partnerGroup));
        $this->assertTrue($policy->view($this->regularUser, $this->userGroup));
        $this->assertFalse($policy->view($this->regularUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->view($this->regularUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->view($this->regularUser, $this->otherUserGroup));
    }

    /**
     * Test create ability.
     */
    public function test_create(): void
    {
        $policy = new GroupPolicy();

        // Admins, Integrators, and Partners can create
        $this->assertTrue($policy->create($this->adminUser));
        $this->assertTrue($policy->create($this->integratorUser));
        $this->assertTrue($policy->create($this->partnerUser));

        // Regular users cannot create
        $this->assertFalse($policy->create($this->regularUser));
    }

    /**
     * Test update ability.
     */
    public function test_update(): void
    {
        $policy = new GroupPolicy();

        // Admin can update any group
        $this->assertTrue($policy->update($this->adminUser, $this->integratorGroup));
        $this->assertTrue($policy->update($this->adminUser, $this->partnerGroup));
        $this->assertTrue($policy->update($this->adminUser, $this->userGroup));

        // Integrator can update groups associated with their partners
        $this->assertTrue($policy->update($this->integratorUser, $this->integratorGroup));
        $this->assertFalse($policy->update($this->integratorUser, $this->partnerGroup));
        $this->assertFalse($policy->update($this->integratorUser, $this->userGroup));
        $this->assertFalse($policy->update($this->integratorUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->update($this->integratorUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->update($this->integratorUser, $this->otherUserGroup));

        // Partner can update their own groups
        $this->assertFalse($policy->update($this->partnerUser, $this->integratorGroup));
        $this->assertTrue($policy->update($this->partnerUser, $this->partnerGroup));
        $this->assertFalse($policy->update($this->partnerUser, $this->userGroup));
        $this->assertFalse($policy->update($this->partnerUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->update($this->partnerUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->update($this->partnerUser, $this->otherUserGroup));

        // Regular user can update groups they are a member of
        $this->assertFalse($policy->update($this->regularUser, $this->integratorGroup));
        $this->assertFalse($policy->update($this->regularUser, $this->partnerGroup));
        $this->assertTrue($policy->update($this->regularUser, $this->userGroup));
        $this->assertFalse($policy->update($this->regularUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->update($this->regularUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->update($this->regularUser, $this->otherUserGroup));
    }

    /**
     * Test delete ability.
     */
    public function test_delete(): void
    {
        $policy = new GroupPolicy();

        // Admin can delete any group
        $this->assertTrue($policy->delete($this->adminUser, $this->integratorGroup));
        $this->assertTrue($policy->delete($this->adminUser, $this->partnerGroup));
        $this->assertTrue($policy->delete($this->adminUser, $this->userGroup));

        // Integrator can delete groups associated with their partners
        $this->assertTrue($policy->delete($this->integratorUser, $this->integratorGroup));
        $this->assertFalse($policy->delete($this->integratorUser, $this->partnerGroup));
        $this->assertFalse($policy->delete($this->integratorUser, $this->userGroup));
        $this->assertFalse($policy->delete($this->integratorUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->delete($this->integratorUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->delete($this->integratorUser, $this->otherUserGroup));

        // Partner can delete their own groups
        $this->assertFalse($policy->delete($this->partnerUser, $this->integratorGroup));
        $this->assertTrue($policy->delete($this->partnerUser, $this->partnerGroup));
        $this->assertFalse($policy->delete($this->partnerUser, $this->userGroup));
        $this->assertFalse($policy->delete($this->partnerUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->delete($this->partnerUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->delete($this->partnerUser, $this->otherUserGroup));

        // Regular user can delete groups they are a member of
        $this->assertFalse($policy->delete($this->regularUser, $this->integratorGroup));
        $this->assertFalse($policy->delete($this->regularUser, $this->partnerGroup));
        $this->assertTrue($policy->delete($this->regularUser, $this->userGroup));
        $this->assertFalse($policy->delete($this->regularUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->delete($this->regularUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->delete($this->regularUser, $this->otherUserGroup));
    }

    /**
     * Test restore ability.
     */
    public function test_restore(): void
    {
        $policy = new GroupPolicy();

        // Admin can restore any group
        $this->assertTrue($policy->restore($this->adminUser, $this->integratorGroup));
        $this->assertTrue($policy->restore($this->adminUser, $this->partnerGroup));
        $this->assertTrue($policy->restore($this->adminUser, $this->userGroup));

        // Integrator can restore groups associated with their partners
        $this->assertTrue($policy->restore($this->integratorUser, $this->integratorGroup));
        $this->assertFalse($policy->restore($this->integratorUser, $this->partnerGroup));
        $this->assertFalse($policy->restore($this->integratorUser, $this->userGroup));
        $this->assertFalse($policy->restore($this->integratorUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->restore($this->integratorUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->restore($this->integratorUser, $this->otherUserGroup));

        // Partner can restore their own groups
        $this->assertFalse($policy->restore($this->partnerUser, $this->integratorGroup));
        $this->assertTrue($policy->restore($this->partnerUser, $this->partnerGroup));
        $this->assertFalse($policy->restore($this->partnerUser, $this->userGroup));
        $this->assertFalse($policy->restore($this->partnerUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->restore($this->partnerUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->restore($this->partnerUser, $this->otherUserGroup));

        // Regular user can restore groups they are a member of
        $this->assertFalse($policy->restore($this->regularUser, $this->integratorGroup));
        $this->assertFalse($policy->restore($this->regularUser, $this->partnerGroup));
        $this->assertTrue($policy->restore($this->regularUser, $this->userGroup));
        $this->assertFalse($policy->restore($this->regularUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->restore($this->regularUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->restore($this->regularUser, $this->otherUserGroup));
    }

    /**
     * Test forceDelete ability.
     */
    public function test_forceDelete(): void
    {
        $policy = new GroupPolicy();

        // Admin can force delete any group
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->integratorGroup));
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->partnerGroup));
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->userGroup));

        // Integrator can force delete groups associated with their partners
        $this->assertTrue($policy->forceDelete($this->integratorUser, $this->integratorGroup));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->partnerGroup));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->userGroup));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->otherUserGroup));

        // Partner can force delete their own groups
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->integratorGroup));
        $this->assertTrue($policy->forceDelete($this->partnerUser, $this->partnerGroup));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->userGroup));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->otherUserGroup));

        // Regular user can force delete groups they are a member of
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->integratorGroup));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->partnerGroup));
        $this->assertTrue($policy->forceDelete($this->regularUser, $this->userGroup));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->otherIntegratorGroup));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->otherPartnerGroup));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->otherUserGroup));
    }

    /**
     * Test before method.
     */
    public function test_before(): void
    {
        $policy = new GroupPolicy();

        // Admin bypasses all checks
        $this->assertTrue($policy->before($this->adminUser, 'anyAbility'));

        // Other roles do not bypass
        $this->assertNull($policy->before($this->integratorUser, 'anyAbility'));
        $this->assertNull($policy->before($this->partnerUser, 'anyAbility'));
        $this->assertNull($policy->before($this->regularUser, 'anyAbility'));
    }
}