<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\ChargingStation;
use App\Models\Reservation;
use App\Models\TariffPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_page_loads_correctly(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $operator = User::factory()->create();
        $businessProfile = BusinessProfile::factory()->create(['user_id' => $operator->id]);
        $tariffPlan = TariffPlan::factory()->create(['price_per_minute' => 0.50, 'max_minutes_per_reservation' => 120]);
        $station = ChargingStation::factory()->create(['operator_id' => $businessProfile->id, 'pricing_plan_id' => $tariffPlan->id]);

        // 2. Act
        $response = $this->actingAs($user)->get(route('offer.show', $station));

        // 3. Assert
        $response->assertStatus(200);
        $response->assertSee($station->location);
        $response->assertSee($tariffPlan->name);
        $response->assertSee('data-tariff-plan-id="' . $tariffPlan->id . '"');
    }

    public function test_reservation_creation_with_duration_minutes_persists_data(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $operator = User::factory()->create();
        $businessProfile = BusinessProfile::factory()->create(['user_id' => $operator->id]);
        $tariffPlan = TariffPlan::factory()->create(['price_per_minute' => 0.50, 'max_minutes_per_reservation' => 120]);
        $station = ChargingStation::factory()->create(['operator_id' => $businessProfile->id, 'pricing_plan_id' => $tariffPlan->id]);

        $durationMinutes = 60;
        $expectedAmount = $durationMinutes * $tariffPlan->price_per_minute;
        $startTime = now()->addHour();
        $guestEmail = 'guest@example.com';
        $guestPhone = '+1234567890';

        // 2. Act
        $response = $this->actingAs($user)->post(route('public.charging-point.offer.store-reservation', $station), [
            'pricing_plan_id' => $tariffPlan->id,
            'reservation_type' => 'duration',
            'duration_minutes' => $durationMinutes,
            'start_time' => $startTime->format('Y-m-d\TH:i'),
            'guest_email' => $guestEmail,
            'guest_phone' => $guestPhone,
        ]);

        // 3. Assert
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_price' => $expectedAmount,
            'status' => 'pending_payment', // Assuming CMI payment flow
        ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'station_id' => $station->id,
            'pricing_plan_id' => $tariffPlan->id,
            'duration_minutes' => $durationMinutes,
            'energy_kwh' => null,
            'amount' => $expectedAmount,
            'status' => 'pending_payment', // Status after successful creation, before CMI callback
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'guest_email' => $guestEmail,
            'guest_phone' => $guestPhone,
        ]);

        $this->assertDatabaseHas('transactions', [
            'business_profile_id' => $businessProfile->id,
            'amount' => $expectedAmount,
            'status' => 'pending_payment', // Status after successful creation, before CMI callback
        ]);

        $reservation = Reservation::where('user_id', $user->id)
                                ->where('station_id', $station->id)
                                ->where('pricing_plan_id', $tariffPlan->id)
                                ->first();

        $response->assertRedirect(route('reservations.show', $reservation));

        // 4. Follow redirect and assert confirmation page
        $confirmationResponse = $this->get($response->headers->get('Location'));
        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee('Reservation created successfully!');
        $confirmationResponse->assertSee($station->location);
        $confirmationResponse->assertSee($durationMinutes . ' minutes');
        $confirmationResponse->assertSee(number_format($expectedAmount, 2) . '€');
        $confirmationResponse->assertSee($guestEmail);
        $confirmationResponse->assertSee($guestPhone);
    }

    public function test_reservation_creation_with_energy_kwh_persists_data(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        $operator = User::factory()->create();
        $businessProfile = BusinessProfile::factory()->create(['user_id' => $operator->id]);
        $tariffPlan = TariffPlan::factory()->create(['price_per_kwh' => 0.75, 'max_kwh_per_reservation' => 50]);
        $station = ChargingStation::factory()->create(['operator_id' => $businessProfile->id, 'pricing_plan_id' => $tariffPlan->id]);

        $energyKwh = 10;
        $expectedAmount = $energyKwh * $tariffPlan->price_per_kwh;
        $startTime = now()->addHour()->addMinutes(30);
        $guestEmail = 'guest2@example.com';
        $guestPhone = '+0987654321';

        // 2. Act
        $response = $this->actingAs($user)->post(route('public.charging-point.offer.store-reservation', $station), [
            'pricing_plan_id' => $tariffPlan->id,
            'reservation_type' => 'energy',
            'energy_kwh' => $energyKwh,
            'start_time' => $startTime->format('Y-m-d\TH:i'),
            'guest_email' => $guestEmail,
            'guest_phone' => $guestPhone,
        ]);

        // 3. Assert
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_price' => $expectedAmount,
            'status' => 'pending_payment',
        ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'station_id' => $station->id,
            'pricing_plan_id' => $tariffPlan->id,
            'duration_minutes' => null,
            'energy_kwh' => $energyKwh,
            'amount' => $expectedAmount,
            'status' => 'pending_payment',
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'guest_email' => $guestEmail,
            'guest_phone' => $guestPhone,
        ]);

        $this->assertDatabaseHas('transactions', [
            'business_profile_id' => $businessProfile->id,
            'amount' => $expectedAmount,
            'status' => 'pending_payment',
        ]);

        $reservation = Reservation::where('user_id', $user->id)
                                ->where('station_id', $station->id)
                                ->where('pricing_plan_id', $tariffPlan->id)
                                ->first();

        $response->assertRedirect(route('reservations.show', $reservation));

        // 4. Follow redirect and assert confirmation page
        $confirmationResponse = $this->get($response->headers->get('Location'));
        $confirmationResponse->assertStatus(200);
        $confirmationResponse->assertSee('Reservation created successfully!');
        $confirmationResponse->assertSee($station->location);
        $confirmationResponse->assertSee($energyKwh . ' kWh');
        $confirmationResponse->assertSee(number_format($expectedAmount, 2) . '€');
        $confirmationResponse->assertSee($guestEmail);
        $confirmationResponse->assertSee($guestPhone);
    }
}