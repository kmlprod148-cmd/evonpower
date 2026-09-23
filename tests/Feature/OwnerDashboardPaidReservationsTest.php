<?php

namespace Tests\Feature;

use App\Http\Controllers\OwnerDashboardController;
use App\Enums\ReservationStatus;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OwnerDashboardPaidReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_dashboard_shows_paid_reservations_on_integrator_charging_points(): void
    {
        Role::findOrCreate('operator', 'web');

        $integrator = Integrator::factory()->create();

        $operator = User::factory()->create([
            'integrator_id' => $integrator->id,
        ]);
        $operator->assignRole('operator');

        $client = User::factory()->create();

        $chargingPoint = ChargingPoint::factory()->create([
            'integrator_id' => $integrator->id,
            'user_id' => null,
            'created_by' => null,
            'created_by_id' => null,
        ]);

        $reservation = Reservation::factory()->create([
            'user_id' => $client->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $chargingPoint->pricing_plan_id,
            'status' => ReservationStatus::ACTIVE,
            'payment_status' => 'PAID',
            'payment_method' => 'stripe',
            'estimated_cost' => 42.50,
        ]);

        $this->actingAs($operator);

        $response = app(OwnerDashboardController::class)->index();

        $this->assertInstanceOf(View::class, $response);
        $recentReservations = $response->getData()['recentReservations'];
        $pendingCount = $response->getData()['pendingCount'];

        $this->assertTrue($recentReservations->contains('id', $reservation->id));
        $this->assertSame(0, $pendingCount);
    }
}
