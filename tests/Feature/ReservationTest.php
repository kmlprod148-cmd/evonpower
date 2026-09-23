<?php

namespace Tests\Feature;

use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\User;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected $reservationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = $this->app->make(ReservationService::class);
    }

    /** @test */
    public function a_user_can_create_a_reservation_for_kwh()
    {
        $user = User::factory()->create();
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_kwh' => 0.5,
            'max_energy' => 100,
            'min_charge_duration' => 10,
        ]);
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online', 'pricing_plan_id' => $pricingPlan->id]);

        $this->actingAs($user);

        $data = [
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => 'kwh',
            'reservation_value' => 50,
            'start_time' => now()->addMinutes(5)->format('H:i'),
            'payment_type' => 'offline',
        ];

        $response = $this->postJson(route('reservations.store', ['chargingPoint' => $chargingPoint->id]), $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Votre réservation a été créée avec succès. Le paiement se fera sur place.'
                 ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => 'kwh',
            'reservation_value' => 50,
            'status' => 'pending_confirmation',
        ]);
    }

    /** @test */
    public function a_user_can_create_a_reservation_for_minutes()
    {
        $user = User::factory()->create();
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.1,
            'max_duration' => 120,
            'min_charge_duration' => 5,
        ]);
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online', 'pricing_plan_id' => $pricingPlan->id]);

        $this->actingAs($user);

        $data = [
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => 'minute',
            'reservation_value' => 60,
            'start_time' => now()->addMinutes(10)->format('H:i'),
            'payment_type' => 'offline',
        ];

        $response = $this->postJson(route('reservations.store', ['chargingPoint' => $chargingPoint->id]), $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Votre réservation a été créée avec succès. Le paiement se fera sur place.'
                 ]);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => 'minute',
            'reservation_value' => 60,
            'status' => 'pending_confirmation',
        ]);
    }

    /** @test */
    public function a_user_can_confirm_a_reservation()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        $pricingPlan = PricingPlan::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.confirm', $reservation->id), ['status' => 'confirmed']);

        $response->assertStatus(302);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function a_user_can_start_a_charging_session_from_a_reservation()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        $pricingPlan = PricingPlan::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.start-charging', $reservation->id));

        $response->assertStatus(302);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'reservation_id' => $reservation->id,
        ]);
    }

    /** @test */
    public function a_user_can_end_a_charging_session_from_a_reservation()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        $pricingPlan = PricingPlan::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'status' => 'confirmed',
        ]);

        // Simulate a transaction being created when session starts
        $transaction = $reservation->transaction()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_id' => $reservation->id,
            'start_timestamp' => now(),
            'meter_start' => 0,
            'currency' => 'MAD',
        ]);

        $this->actingAs($user);

        $actualData = [
            'duration' => 30,
            'energy' => 15,
            'cost' => 7.5,
        ];

        $response = $this->postJson(route('reservations.end-charging', $reservation->id), $actualData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Session de charge terminée avec succès'
                 ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'completed',
            'actual_duration' => 30,
            'actual_energy' => 15,
            'actual_cost' => 7.5,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'stop_timestamp' => now()->format('Y-m-d H:i:s'), // Check if it's updated
        ]);
    }

    /** @test */
    public function a_user_can_cancel_a_reservation()
    {
        $user = User::factory()->create();
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online']);
        $pricingPlan = PricingPlan::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('reservations.cancel', $reservation->id), ['status' => 'canceled']);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Réservation annulée avec succès'
                 ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'canceled',
        ]);
    }

    /** @test */
    public function it_calculates_reservation_cost_based_on_duration_and_energy()
    {
        $user = User::factory()->create();
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_kwh' => 0.75,
            'price_per_minute' => 0.1, // This should not be used in the new formula
        ]);
        $chargingPoint = ChargingPoint::factory()->create(['status' => 'online', 'pricing_plan_id' => $pricingPlan->id]);

        $this->actingAs($user);

        $data = [
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => 'both',
            'duration_minutes' => 60,
            'energy_kwh' => 10,
            'payment_type' => 'offline',
            'start_time' => now()->addMinutes(5)->format('H:i'),
        ];

        $this->postJson(route('reservations.store', ['chargingPoint' => $chargingPoint->id]), $data);

        // Expected cost: (60 minutes / 10 kWh) * 0.75/kWh = 4.5
        $expectedCost = (60 / 10) * 0.75;

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'estimated_cost' => $expectedCost,
        ]);
    }

    /** @test */
    public function reservation_options_are_generated_correctly_for_time_based_plan()
    {
        $user = User::factory()->create();
        $pricingPlan = PricingPlan::factory()->create([
            'rate_type' => 'time',
            'price_per_minute' => 0.1,
            'max_duration' => 120,
        ]);
        $chargingPoint = ChargingPoint::factory()->create([
            'status' => 'online', 
            'pricing_plan_id' => $pricingPlan->id,
            'power_output' => 22.0
        ]);

        $this->actingAs($user);

        $response = $this->get(route('reservations.create', ['chargingPoint' => $chargingPoint->id]));

        $response->assertStatus(200);
        $response->assertViewHas('limits');
        
        $limits = $response->viewData('limits');
        
        // Should have minute options but no kWh options for time-based plan
        $this->assertNotEmpty($limits['reservation_options']['minute']);
        $this->assertEmpty($limits['reservation_options']['kwh']);
        
        // Should respect max duration
        $this->assertLessThanOrEqual(120, max($limits['reservation_options']['minute']));
    }

    /** @test */
    public function reservation_options_are_generated_correctly_for_energy_based_plan()
    {
        $user = User::factory()->create();
        $pricingPlan = PricingPlan::factory()->create([
            'rate_type' => 'energy',
            'price_per_kwh' => 0.5,
            'max_duration' => 120, // This will be used to calculate max energy
        ]);
        $chargingPoint = ChargingPoint::factory()->create([
            'status' => 'online', 
            'pricing_plan_id' => $pricingPlan->id,
            'power_output' => 22.0
        ]);

        $this->actingAs($user);

        $response = $this->get(route('reservations.create', ['chargingPoint' => $chargingPoint->id]));

        $response->assertStatus(200);
        $response->assertViewHas('limits');
        
        $limits = $response->viewData('limits');
        
        // Should have kWh options but no minute options for energy-based plan
        $this->assertNotEmpty($limits['reservation_options']['kwh']);
        $this->assertEmpty($limits['reservation_options']['minute']);
        
        // Max energy should be calculated: 22 kW * (120 min / 60) = 44 kWh
        $expectedMaxEnergy = 22.0 * (120 / 60);
        $this->assertEquals($expectedMaxEnergy, $limits['max_energy']);
        
        // Should respect max energy
        $this->assertLessThanOrEqual($expectedMaxEnergy, max($limits['reservation_options']['kwh']));
    }

    /** @test */
    public function reservation_options_are_generated_correctly_for_mixed_plan()
    {
        $user = User::factory()->create();
        $pricingPlan = PricingPlan::factory()->create([
            'rate_type' => 'mixed',
            'price_per_kwh' => 0.5,
            'price_per_minute' => 0.1,
            'max_duration' => 120,
        ]);
        $chargingPoint = ChargingPoint::factory()->create([
            'status' => 'online', 
            'pricing_plan_id' => $pricingPlan->id,
            'power_output' => 22.0
        ]);

        $this->actingAs($user);

        $response = $this->get(route('reservations.create', ['chargingPoint' => $chargingPoint->id]));

        $response->assertStatus(200);
        $response->assertViewHas('limits');
        
        $limits = $response->viewData('limits');
        
        // Should have both options for mixed plan
        $this->assertNotEmpty($limits['reservation_options']['kwh']);
        $this->assertNotEmpty($limits['reservation_options']['minute']);
        
        // Should respect limits
        $this->assertLessThanOrEqual(120, max($limits['reservation_options']['minute']));
        $this->assertLessThanOrEqual(44.0, max($limits['reservation_options']['kwh'])); // 22 kW * (120 min / 60)
    }
}