<?php
namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChargingPointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The RefreshDatabase trait handles migrations and seeding.
        // Manual db:seed call is removed to prevent conflicts.
    }
    #[Test]
    public function test_admin_can_view_all_charging_points()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        ChargingPoint::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/charging-points');

        $response->assertStatus(200);
        $response->assertViewHas('chargingPoints');
    }
    #[Test]
    public function test_partner_can_only_view_own_charging_points()
    {
        $partner = User::factory()->create(['partner_id' => 1]);
        $partner->assignRole('partner');

        ChargingPoint::factory()->create(['partner_id' => 1]);
        ChargingPoint::factory()->create(['partner_id' => 2]);

        $response = $this->actingAs($partner)->get('/api/v1/charging-points');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }
}