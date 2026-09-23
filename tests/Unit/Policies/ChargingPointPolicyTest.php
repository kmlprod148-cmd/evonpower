<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Partner;
use App\Models\Group;
use App\Policies\ChargingPointPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargingPointPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $integratorUser;
    protected $partnerUser;
    protected $regularUser;
    protected $otherIntegratorUser;
    protected $otherPartnerUser;

    protected $integratorPartner;
    protected $otherIntegratorPartner;
    protected $partnerPartner;
    protected $otherPartnerPartner;

    protected $integratorChargingPoint;
    protected $partnerChargingPoint;
    protected $otherIntegratorChargingPoint;
    protected $otherPartnerChargingPoint;
    protected $publicChargingPoint;
    protected $groupChargingPoint;
    protected $userGroup;

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

        // Create partners associated with integrators and partners
        $this->integratorPartner = Partner::factory()->create(['integrator_id' => $this->integratorUser->integrator_id]);
        $this->otherIntegratorPartner = Partner::factory()->create(['integrator_id' => $this->otherIntegratorUser->integrator_id]);
        $this->partnerPartner = Partner::factory()->create(['id' => $this->partnerUser->partner_id]);
        $this->otherPartnerPartner = Partner::factory()->create(['id' => $this->otherPartnerUser->partner_id]);

        // Create groups
        $this->userGroup = Group::factory()->create();
        $this->userGroup->users()->attach($this->regularUser);

        // Create charging points with different associations
        $this->integratorChargingPoint = ChargingPoint::factory()->create(['partner_id' => $this->integratorPartner->id, 'public_access' => false]);
        $this->partnerChargingPoint = ChargingPoint::factory()->create(['partner_id' => $this->partnerPartner->id, 'public_access' => false]);
        $this->otherIntegratorChargingPoint = ChargingPoint::factory()->create(['partner_id' => $this->otherIntegratorPartner->id, 'public_access' => false]);
        $this->otherPartnerChargingPoint = ChargingPoint::factory()->create(['partner_id' => $this->otherPartnerPartner->id, 'public_access' => false]);
        $this->publicChargingPoint = ChargingPoint::factory()->create(['public_access' => true]);
        $this->groupChargingPoint = ChargingPoint::factory()->create(['group_id' => $this->userGroup->id, 'public_access' => false]);
    }

    /**
     * Test viewAny ability.
     */
    public function test_viewAny(): void
    {
        $policy = new ChargingPointPolicy();

        // Admin can view any
        $this->assertTrue($policy->viewAny($this->adminUser));

        // Integrator can view any (if they have associated partners with charging points)
        // Note: The policy scope handles the actual filtering, this policy method just checks if they *can* view any.
        // The current policy logic for integrator viewAny is basic, relying on existence.
        // With the scope, the policy method can be simplified or removed if using implicit model binding with scopes.
        // For now, testing the existing policy logic.
        $this->assertTrue($policy->viewAny($this->integratorUser)); // Assuming integrator has partners with CPs
        $this->assertTrue($policy->viewAny($this->otherIntegratorUser)); // Assuming other integrator has partners with CPs

        // Partner can view any (if they have charging points)
        // Similar note as integrator viewAny.
        $this->assertTrue($policy->viewAny($this->partnerUser)); // Assuming partner has CPs
        $this->assertTrue($policy->viewAny($this->otherPartnerUser)); // Assuming other partner has CPs

        // Regular user cannot view any (based on current policy logic)
        $this->assertFalse($policy->viewAny($this->regularUser));
    }

    /**
     * Test view ability.
     */
    public function test_view(): void
    {
        $policy = new ChargingPointPolicy();

        // Admin can view any charging point
        $this->assertTrue($policy->view($this->adminUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->view($this->adminUser, $this->partnerChargingPoint));
        $this->assertTrue($policy->view($this->adminUser, $this->publicChargingPoint));
        $this->assertTrue($policy->view($this->adminUser, $this->groupChargingPoint));

        // Integrator can view charging points associated with their partners
        $this->assertTrue($policy->view($this->integratorUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->view($this->integratorUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->view($this->integratorUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->view($this->integratorUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->view($this->integratorUser, $this->publicChargingPoint)); // Integrators don't view public unless associated
        $this->assertFalse($policy->view($this->integratorUser, $this->groupChargingPoint)); // Integrators don't view group CPs unless associated

        // Partner can view their own charging points
        $this->assertFalse($policy->view($this->partnerUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->view($this->partnerUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->view($this->partnerUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->view($this->partnerUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->view($this->partnerUser, $this->publicChargingPoint)); // Partners don't view public unless associated
        $this->assertFalse($policy->view($this->partnerUser, $this->groupChargingPoint)); // Partners don't view group CPs unless associated

        // Regular user can view public charging points or those in their groups
        $this->assertFalse($policy->view($this->regularUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->view($this->regularUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->view($this->regularUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->view($this->regularUser, $this->otherPartnerChargingPoint));
        $this->assertTrue($policy->view($this->regularUser, $this->publicChargingPoint));
        $this->assertTrue($policy->view($this->regularUser, $this->groupChargingPoint));
    }

    /**
     * Test create ability.
     */
    public function test_create(): void
    {
        $policy = new ChargingPointPolicy();

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
        $policy = new ChargingPointPolicy();

        // Admin can update any charging point
        $this->assertTrue($policy->update($this->adminUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->update($this->adminUser, $this->partnerChargingPoint));
        $this->assertTrue($policy->update($this->adminUser, $this->publicChargingPoint));
        $this->assertTrue($policy->update($this->adminUser, $this->groupChargingPoint));

        // Integrator can update charging points associated with their partners
        $this->assertTrue($policy->update($this->integratorUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->update($this->integratorUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->update($this->integratorUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->update($this->integratorUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->update($this->integratorUser, $this->publicChargingPoint));
        $this->assertFalse($policy->update($this->integratorUser, $this->groupChargingPoint));

        // Partner can update their own charging points
        $this->assertFalse($policy->update($this->partnerUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->update($this->partnerUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->update($this->partnerUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->update($this->partnerUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->update($this->partnerUser, $this->publicChargingPoint));
        $this->assertFalse($policy->update($this->partnerUser, $this->groupChargingPoint));

        // Regular user cannot update
        $this->assertFalse($policy->update($this->regularUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->update($this->regularUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->update($this->regularUser, $this->publicChargingPoint));
        $this->assertFalse($policy->update($this->regularUser, $this->groupChargingPoint));
    }

    /**
     * Test delete ability.
     */
    public function test_delete(): void
    {
        $policy = new ChargingPointPolicy();

        // Admin can delete any charging point
        $this->assertTrue($policy->delete($this->adminUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->delete($this->adminUser, $this->partnerChargingPoint));
        $this->assertTrue($policy->delete($this->adminUser, $this->publicChargingPoint));
        $this->assertTrue($policy->delete($this->adminUser, $this->groupChargingPoint));

        // Integrator can delete charging points associated with their partners
        $this->assertTrue($policy->delete($this->integratorUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->delete($this->integratorUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->delete($this->integratorUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->delete($this->integratorUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->delete($this->integratorUser, $this->publicChargingPoint));
        $this->assertFalse($policy->delete($this->integratorUser, $this->groupChargingPoint));

        // Partner can delete their own charging points
        $this->assertFalse($policy->delete($this->partnerUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->delete($this->partnerUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->delete($this->partnerUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->delete($this->partnerUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->delete($this->partnerUser, $this->publicChargingPoint));
        $this->assertFalse($policy->delete($this->partnerUser, $this->groupChargingPoint));

        // Regular user cannot delete
        $this->assertFalse($policy->delete($this->regularUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->delete($this->regularUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->delete($this->regularUser, $this->publicChargingPoint));
        $this->assertFalse($policy->delete($this->regularUser, $this->groupChargingPoint));
    }

    /**
     * Test restore ability.
     */
    public function test_restore(): void
    {
        $policy = new ChargingPointPolicy();

        // Admin can restore any charging point
        $this->assertTrue($policy->restore($this->adminUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->restore($this->adminUser, $this->partnerChargingPoint));
        $this->assertTrue($policy->restore($this->adminUser, $this->publicChargingPoint));
        $this->assertTrue($policy->restore($this->adminUser, $this->groupChargingPoint));

        // Integrator can restore charging points associated with their partners
        $this->assertTrue($policy->restore($this->integratorUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->restore($this->integratorUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->restore($this->integratorUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->restore($this->integratorUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->restore($this->integratorUser, $this->publicChargingPoint));
        $this->assertFalse($policy->restore($this->integratorUser, $this->groupChargingPoint));

        // Partner can restore their own charging points
        $this->assertFalse($policy->restore($this->partnerUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->restore($this->partnerUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->restore($this->partnerUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->restore($this->partnerUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->restore($this->partnerUser, $this->publicChargingPoint));
        $this->assertFalse($policy->restore($this->partnerUser, $this->groupChargingPoint));

        // Regular user cannot restore
        $this->assertFalse($policy->restore($this->regularUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->restore($this->regularUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->restore($this->regularUser, $this->publicChargingPoint));
        $this->assertFalse($policy->restore($this->regularUser, $this->groupChargingPoint));
    }

    /**
     * Test forceDelete ability.
     */
    public function test_forceDelete(): void
    {
        $policy = new ChargingPointPolicy();

        // Admin can force delete any charging point
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->partnerChargingPoint));
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->publicChargingPoint));
        $this->assertTrue($policy->forceDelete($this->adminUser, $this->groupChargingPoint));

        // Integrator can force delete charging points associated with their partners
        $this->assertTrue($policy->forceDelete($this->integratorUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->publicChargingPoint));
        $this->assertFalse($policy->forceDelete($this->integratorUser, $this->groupChargingPoint));

        // Partner can force delete their own charging points
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->integratorChargingPoint));
        $this->assertTrue($policy->forceDelete($this->partnerUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->otherIntegratorChargingPoint));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->otherPartnerChargingPoint));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->publicChargingPoint));
        $this->assertFalse($policy->forceDelete($this->partnerUser, $this->groupChargingPoint));

        // Regular user cannot force delete
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->integratorChargingPoint));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->partnerChargingPoint));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->publicChargingPoint));
        $this->assertFalse($policy->forceDelete($this->regularUser, $this->groupChargingPoint));
    }

    /**
     * Test before method.
     */
    public function test_before(): void
    {
        $policy = new ChargingPointPolicy();

        // Admin bypasses all checks
        $this->assertTrue($policy->before($this->adminUser, 'anyAbility'));

        // Other roles do not bypass
        $this->assertNull($policy->before($this->integratorUser, 'anyAbility'));
        $this->assertNull($policy->before($this->partnerUser, 'anyAbility'));
        $this->assertNull($policy->before($this->regularUser, 'anyAbility'));
    }
}