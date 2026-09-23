<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\Group;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Services\BusinessProfileAutoLinkService;
use App\Services\ChargingPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class BusinessProfileAutoLinkTest extends TestCase
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
    public function it_auto_links_operator_business_profile()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create operator business profile
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Auto-link business profile
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($operatorProfile->id, $linkedProfile->id);
        $this->assertEquals($operatorProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_auto_links_partner_business_profile_when_no_operator_profile()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create partner business profile
        $partnerProfile = BusinessProfile::factory()->create([
            'owner_type' => Partner::class,
            'owner_id' => $partner->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
        ]);
        
        // Auto-link business profile
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($partnerProfile->id, $linkedProfile->id);
        $this->assertEquals($partnerProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_auto_links_integrator_business_profile_when_no_operator_or_partner_profile()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create integrator business profile
        $integratorProfile = BusinessProfile::factory()->create([
            'owner_type' => Integrator::class,
            'owner_id' => $integrator->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'integrator_id' => $integrator->id,
        ]);
        
        // Auto-link business profile
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($integratorProfile->id, $linkedProfile->id);
        $this->assertEquals($integratorProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_auto_links_default_business_profile_when_no_specific_profile()
    {
        // Create default business profile
        $defaultProfile = BusinessProfile::factory()->create([
            'is_default' => true,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create();
        
        // Auto-link business profile
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($defaultProfile->id, $linkedProfile->id);
        $this->assertEquals($defaultProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_throws_exception_when_no_business_profile_found()
    {
        // Create charging point without any business profiles
        $chargingPoint = ChargingPoint::factory()->create();
        
        // Expect exception when no business profile is found
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No business profile found for this charging point hierarchy.');
        
        BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
    }

    /** @test */
    public function it_gets_available_business_profiles_for_charging_point()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        $partnerProfile = BusinessProfile::factory()->create([
            'owner_type' => Partner::class,
            'owner_id' => $partner->id,
            'is_active' => true,
        ]);
        
        $integratorProfile = BusinessProfile::factory()->create([
            'owner_type' => Integrator::class,
            'owner_id' => $integrator->id,
            'is_active' => true,
        ]);
        
        $defaultProfile = BusinessProfile::factory()->create([
            'is_default' => true,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Get available business profiles
        $availableProfiles = BusinessProfileAutoLinkService::getAvailableBusinessProfiles($chargingPoint);
        
        $this->assertCount(4, $availableProfiles);
        
        // Check that all profiles are included
        $profileIds = array_column($availableProfiles, 'id');
        $this->assertContains($operatorProfile->id, $profileIds);
        $this->assertContains($partnerProfile->id, $profileIds);
        $this->assertContains($integratorProfile->id, $profileIds);
        $this->assertContains($defaultProfile->id, $profileIds);
    }

    /** @test */
    public function it_gets_recommended_business_profile_for_charging_point()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create operator business profile
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Get recommended business profile
        $recommendedProfile = BusinessProfileAutoLinkService::getRecommendedBusinessProfile($chargingPoint);
        
        $this->assertNotNull($recommendedProfile);
        $this->assertEquals($operatorProfile->id, $recommendedProfile['id']);
        $this->assertEquals('operator', $recommendedProfile['type']);
        $this->assertStringContainsString('operator', $recommendedProfile['reason']);
    }

    /** @test */
    public function it_validates_business_profile_compatibility()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        $otherProfile = BusinessProfile::factory()->create([
            'owner_type' => Partner::class,
            'owner_id' => $partner->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Test compatibility
        $this->assertTrue(BusinessProfileAutoLinkService::isBusinessProfileCompatible($operatorProfile->id, $chargingPoint));
        $this->assertTrue(BusinessProfileAutoLinkService::isBusinessProfileCompatible($otherProfile->id, $chargingPoint));
        
        // Test with invalid profile
        $this->assertFalse(BusinessProfileAutoLinkService::isBusinessProfileCompatible(999, $chargingPoint));
    }

    /** @test */
    public function it_gets_business_profile_statistics()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        $partnerProfile = BusinessProfile::factory()->create([
            'owner_type' => Partner::class,
            'owner_id' => $partner->id,
            'is_active' => true,
        ]);
        
        $integratorProfile = BusinessProfile::factory()->create([
            'owner_type' => Integrator::class,
            'owner_id' => $integrator->id,
            'is_active' => true,
        ]);
        
        $defaultProfile = BusinessProfile::factory()->create([
            'is_default' => true,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Get statistics
        $stats = BusinessProfileAutoLinkService::getBusinessProfileStats($chargingPoint);
        
        $this->assertEquals(4, $stats['total_available']);
        $this->assertTrue($stats['has_recommended']);
        $this->assertNotNull($stats['recommended_profile']);
        $this->assertEquals(1, $stats['by_type']['operator']);
        $this->assertEquals(1, $stats['by_type']['partner']);
        $this->assertEquals(1, $stats['by_type']['integrator']);
        $this->assertEquals(1, $stats['by_type']['default']);
    }

    /** @test */
    public function it_auto_links_business_profile_on_charging_point_creation()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create operator business profile
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        // Create charging point (should auto-link business profile)
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Check that business profile was auto-linked
        $this->assertEquals($operatorProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_does_not_auto_link_when_business_profile_already_assigned()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create business profiles
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        $partnerProfile = BusinessProfile::factory()->create([
            'owner_type' => Partner::class,
            'owner_id' => $partner->id,
            'is_active' => true,
        ]);
        
        // Create charging point with business profile already assigned
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
            'business_profile_id' => $partnerProfile->id,
        ]);
        
        // Auto-link should not change the existing business profile
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($partnerProfile->id, $linkedProfile->id);
        $this->assertEquals($partnerProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_handles_inactive_business_profiles()
    {
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create inactive operator business profile
        $inactiveProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => false,
        ]);
        
        // Create active partner business profile
        $activeProfile = BusinessProfile::factory()->create([
            'owner_type' => Partner::class,
            'owner_id' => $partner->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Auto-link should use active profile, not inactive one
        $linkedProfile = BusinessProfileAutoLinkService::autoLinkBusinessProfile($chargingPoint);
        
        $this->assertEquals($activeProfile->id, $linkedProfile->id);
        $this->assertEquals($activeProfile->id, $chargingPoint->fresh()->business_profile_id);
    }

    /** @test */
    public function it_uses_charging_point_service_for_business_profile_operations()
    {
        $service = new ChargingPointService();
        
        // Create integrator
        $integrator = Integrator::factory()->create();
        
        // Create partner
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        
        // Create group
        $group = Group::factory()->create(['partner_id' => $partner->id]);
        
        // Create operator
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $operator->update(['integrator_id' => $integrator->id]);
        
        // Create operator business profile
        $operatorProfile = BusinessProfile::factory()->create([
            'owner_type' => User::class,
            'owner_id' => $operator->id,
            'is_active' => true,
        ]);
        
        // Create charging point
        $chargingPoint = ChargingPoint::factory()->create([
            'group_id' => $group->id,
            'partner_id' => $partner->id,
            'integrator_id' => $integrator->id,
            'user_id' => $operator->id,
        ]);
        
        // Test service methods
        $availableProfiles = $service->getAvailableBusinessProfiles($chargingPoint);
        $this->assertCount(1, $availableProfiles);
        
        $recommendedProfile = $service->getRecommendedBusinessProfile($chargingPoint);
        $this->assertNotNull($recommendedProfile);
        
        $stats = $service->getBusinessProfileStats($chargingPoint);
        $this->assertEquals(1, $stats['total_available']);
        
        $isCompatible = $service->validateBusinessProfileCompatibility($chargingPoint, $operatorProfile->id);
        $this->assertTrue($isCompatible);
    }
}
