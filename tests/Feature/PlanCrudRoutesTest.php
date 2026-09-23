<?php

namespace Tests\Feature;

use App\Models\PricingPlan;
use App\Models\User;
use App\Models\VatRate;
use App\Support\RequestAwareRoute;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlanCrudRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected VatRate $vatRate;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin', 'web');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->vatRate = VatRate::factory()->create([
            'is_active' => true,
            'is_default' => true,
            'rate' => 20,
        ]);
    }

    #[Test]
    public function authenticated_user_can_open_the_plan_create_form(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('plans.create'));

        $response->assertOk();
        $response->assertViewIs('plans.create');
    }

    #[Test]
    public function store_route_accepts_the_create_form_payload(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->adminUser)
            ->post(route('plans.store'), [
                'name' => 'Minute Plan',
                'rate_type' => 'time',
                'price' => '1.75',
                'vat_rate_id' => $this->vatRate->id,
                'max_duration' => 120,
                'activation_fee' => '2.50',
                'has_weekend_pricing' => '1',
                'weekend_price' => '0.30',
                'has_night_pricing' => '1',
                'night_price' => '0.15',
                'night_start_time' => '22:00',
                'night_end_time' => '06:00',
            ]);

        $response->assertRedirect(route('plans.index'));

        $plan = PricingPlan::query()->where('name', 'Minute Plan')->first();

        $this->assertNotNull($plan);
        $this->assertSame('time', $plan->rate_type);
        $this->assertEquals(1.75, (float) $plan->base_rate);
        $this->assertEquals(1.75, (float) $plan->price_per_minute);
        $this->assertSame('session', $plan->billing_interval);
        $this->assertSame('EUR', $plan->currency);
        $this->assertTrue((bool) $plan->is_active);
    }

    #[Test]
    public function show_route_resolves_and_renders_the_plan_detail_page(): void
    {
        $plan = PricingPlan::factory()->create([
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('plans.show', $plan));

        $response->assertOk();
        $response->assertViewIs('pricing-plans.show');
    }

    #[Test]
    public function request_aware_routes_keep_the_public_prefix_for_plan_urls(): void
    {
        $request = Request::create('http://localhost/public/plans/create', 'GET');

        $this->assertSame('/public/plans', RequestAwareRoute::to($request, 'plans.store'));
        $this->assertSame('/public/plans/create', RequestAwareRoute::to($request, 'plans.create'));
        $this->assertSame('/public/pricing-plans', RequestAwareRoute::to($request, 'pricing-plans.index'));

        $doublePublicRequest = Request::create('http://localhost/public/public/plans/create', 'GET');
        $this->assertSame('/public/plans/create', RequestAwareRoute::to($doublePublicRequest, 'plans.create'));

        $plan = PricingPlan::factory()->create([
            'vat_rate_id' => $this->vatRate->id,
        ]);
        $this->assertSame('/public/plans/' . $plan->id . '/edit', RequestAwareRoute::to($request, 'plans.edit', $plan));
    }

    #[Test]
    public function edit_and_update_routes_use_the_working_plan_form(): void
    {
        $plan = PricingPlan::query()->create([
            'name' => 'Legacy Plan',
            'description' => 'Legacy description',
            'rate_type' => 'time',
            'base_rate' => 0.55,
            'fixed_price' => 0,
            'price_per_kwh' => 0,
            'price_per_minute' => 0.55,
            'activation_fee' => 0,
            'vat_rate_id' => $this->vatRate->id,
            'priority' => 0,
            'max_duration' => 120,
            'is_active' => true,
            'currency' => 'EUR',
            'billing_interval' => 'session',
        ]);

        $newVatRate = VatRate::factory()->create([
            'is_active' => true,
            'rate' => 10,
        ]);

        $editResponse = $this->actingAs($this->adminUser)
            ->get(route('plans.edit', $plan->id));

        $editResponse->assertOk();
        $editResponse->assertViewIs('plans.edit');

        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('plans.update', $plan->id), [
                'name' => 'Updated Energy Plan',
                'description' => 'Updated description',
                'rate_type' => 'energy',
                'base_rate' => '2.40',
                'vat_rate_id' => $newVatRate->id,
                'activation_fee' => '3.10',
                'priority' => '4',
                'max_duration' => 90,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'valid_from' => now()->format('Y-m-d\TH:i'),
                'valid_until' => now()->addDay()->format('Y-m-d\TH:i'),
                'has_weekend_pricing' => '1',
                'weekend_price' => '0.30',
                'has_night_pricing' => '1',
                'night_price' => '0.20',
                'night_start_time' => '22:00',
                'night_end_time' => '06:00',
                'is_active' => '1',
                'mobile_theme_color' => '#123456',
            ]);

        $updateResponse->assertRedirect(route('plans.index'));

        $plan->refresh();

        $this->assertSame('Updated Energy Plan', $plan->name);
        $this->assertSame('energy', $plan->rate_type);
        $this->assertEquals(2.40, (float) $plan->base_rate);
        $this->assertEquals(2.40, (float) $plan->price_per_kwh);
        $this->assertSame('monthly', $plan->billing_interval);
        $this->assertSame('USD', $plan->currency);
        $this->assertSame('#123456', $plan->mobile_theme_color);
    }

    #[Test]
    public function destroy_route_removes_unused_plans(): void
    {
        $plan = PricingPlan::factory()->create([
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('plans.destroy', $plan->id));

        $response->assertRedirect(route('plans.index'));
        $this->assertDatabaseMissing('pricing_plans', [
            'id' => $plan->id,
        ]);
    }
}
