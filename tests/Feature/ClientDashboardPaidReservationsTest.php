<?php

namespace Tests\Feature;

use App\Http\Controllers\Client\ClientDashboardController;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

class ClientDashboardPaidReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_confirmed_reservations_remain_visible_without_counting_as_pending(): void
    {
        $user = User::factory()->create([
            'phone' => '+212600000001',
        ]);

        $paidReservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'status' => ReservationStatus::CONFIRMED,
            'payment_status' => 'PAID',
            'payment_method' => 'stripe',
            'start_time' => now()->addDay(),
            'estimated_cost' => 29.50,
        ]);

        $pendingReservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
            'payment_method' => 'offline',
            'start_time' => now()->addDays(2),
        ]);

        $this->actingAs($user);

        $response = app(ClientDashboardController::class)->index(Request::create('/dashboard/client', 'GET'));

        $this->assertInstanceOf(View::class, $response);

        $data = $response->getData();
        $recentReservations = $data['recentReservations'];
        $upcomingReservations = $data['upcomingReservations'];
        $stats = $data['stats'];

        $this->assertTrue($recentReservations->contains('id', $paidReservation->id));
        $this->assertTrue($upcomingReservations->contains('id', $paidReservation->id));
        $this->assertSame(1, $stats['pending_reservations']);
        $this->assertTrue($upcomingReservations->contains('id', $pendingReservation->id));
    }
}
