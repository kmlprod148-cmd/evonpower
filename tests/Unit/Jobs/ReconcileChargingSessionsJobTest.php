<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\ReservationStatus;
use App\Jobs\ReconcileChargingSessionsJob;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Models\User;
use App\Services\OcppOperationsService;
use App\Services\StevePostpaidPaymentService;
use App\Services\SteVeHttpClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Pins Slice C contract:
 *   - When SteVe shows stopTimestamp → ChargingSession → STOPPED and
 *     the linked Reservation → COMPLETED with actual_end_time set.
 *   - Postpaid sessions ALSO trigger finalizeFromSteve.
 *   - When SteVe shows no stopTimestamp → session left untouched.
 *   - When SteVe getTransaction fails → no state mutations.
 */
class ReconcileChargingSessionsJobTest extends TestCase
{
    use RefreshDatabase;

    protected $steve;
    protected $ocppOps;
    protected $postpaid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->steve = Mockery::mock(SteVeHttpClientService::class);
        $this->ocppOps = Mockery::mock(OcppOperationsService::class);
        $this->postpaid = Mockery::mock(StevePostpaidPaymentService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function runJob(): void
    {
        (new ReconcileChargingSessionsJob(50, 240))
            ->handle($this->steve, $this->ocppOps, $this->postpaid);
    }

    private function makeActiveSession(string $paymentMode = 'prepaid', ?int $reservationId = null): ChargingSession
    {
        $user = User::factory()->create();
        $cpId = DB::table('charging_points')->insertGetId([
            'name'            => 'CP-REC-' . uniqid(),
            'serial_number'   => 'SN-' . uniqid(),
            'connection_type' => 'AC',
            'max_power'       => 22.0,
            'status'          => 'online',
            'charge_box_id'   => 'CB-REC-' . uniqid(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return ChargingSession::create([
            'charging_point_id'    => $cpId,
            'user_id'              => $user->id,
            'status'               => ChargingSession::STATUS_ACTIVE,
            'connector_id'         => 1,
            'started_at'           => now()->subMinutes(10),
            'steve_transaction_id' => '12345',
            'payment_mode'         => $paymentMode,
            'reservation_id'       => $reservationId,
        ]);
    }

    public function test_session_flips_to_stopped_when_steve_shows_stop_timestamp(): void
    {
        $session = $this->makeActiveSession();

        $this->steve->shouldReceive('getTransaction')
            ->once()
            ->with('12345')
            ->andReturn([
                'success' => true,
                'data'    => [
                    'id'            => 12345,
                    'stopTimestamp' => '2026-05-23T10:00:00Z',
                    'stopValue'     => '15000',
                    'stopReason'    => 'Local',
                ],
            ]);

        $this->ocppOps->shouldReceive('markSessionStoppedFromSteve')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id), Mockery::type('array'));

        $this->postpaid->shouldNotReceive('finalizeFromSteve');

        $this->runJob();
    }

    public function test_session_left_alone_when_steve_shows_no_stop_yet(): void
    {
        $this->makeActiveSession();

        $this->steve->shouldReceive('getTransaction')
            ->once()
            ->andReturn([
                'success' => true,
                'data'    => [
                    'id'             => 12345,
                    'startTimestamp' => '2026-05-23T09:00:00Z',
                    // no stopTimestamp → still active on SteVe
                ],
            ]);

        $this->ocppOps->shouldNotReceive('markSessionStoppedFromSteve');
        $this->postpaid->shouldNotReceive('finalizeFromSteve');

        $this->runJob();
    }

    public function test_postpaid_session_finalises_wallet_debit_on_stop(): void
    {
        $reservation = Reservation::create([
            'user_id'           => User::factory()->create()->id,
            'charging_point_id' => null,
            'status'            => ReservationStatus::ACTIVE,
            'amount'            => 10.00,
            'payment_status'    => 'PENDING',
            'payment_mode'      => 'postpaid',
        ]);
        $session = $this->makeActiveSession('postpaid', $reservation->id);
        $reservation->update(['charging_point_id' => $session->charging_point_id]);

        $this->steve->shouldReceive('getTransaction')
            ->once()
            ->andReturn([
                'success' => true,
                'data'    => [
                    'id'            => 12345,
                    'stopTimestamp' => '2026-05-23T10:00:00Z',
                    'stopValue'     => '20000',
                ],
            ]);

        $this->ocppOps->shouldReceive('markSessionStoppedFromSteve')->once();
        $this->postpaid->shouldReceive('finalizeFromSteve')
            ->once()
            ->andReturn(['success' => true, 'actual_cost' => 5.50]);

        $this->runJob();

        $reservation->refresh();
        $this->assertSame(ReservationStatus::COMPLETED, $reservation->status);
        $this->assertNotNull($reservation->actual_end_time);
    }

    public function test_postpaid_skipped_when_reservation_already_paid(): void
    {
        $reservation = Reservation::create([
            'user_id'           => User::factory()->create()->id,
            'charging_point_id' => null,
            'status'            => ReservationStatus::ACTIVE,
            'amount'            => 10.00,
            'payment_status'    => 'PAID',
            'payment_mode'      => 'postpaid',
        ]);
        $session = $this->makeActiveSession('postpaid', $reservation->id);
        $reservation->update(['charging_point_id' => $session->charging_point_id]);

        $this->steve->shouldReceive('getTransaction')
            ->once()
            ->andReturn([
                'success' => true,
                'data'    => ['id' => 12345, 'stopTimestamp' => '2026-05-23T10:00:00Z'],
            ]);

        $this->ocppOps->shouldReceive('markSessionStoppedFromSteve')->once();
        $this->postpaid->shouldNotReceive('finalizeFromSteve');

        $this->runJob();
    }

    public function test_no_state_mutation_when_steve_call_fails(): void
    {
        $this->makeActiveSession();

        $this->steve->shouldReceive('getTransaction')
            ->once()
            ->andReturn([
                'success' => false,
                'error'   => 'HTTP 500',
            ]);

        $this->ocppOps->shouldNotReceive('markSessionStoppedFromSteve');
        $this->postpaid->shouldNotReceive('finalizeFromSteve');

        $this->runJob();
    }

    public function test_terminal_sessions_are_not_scanned(): void
    {
        $session = $this->makeActiveSession();
        $session->update(['status' => ChargingSession::STATUS_COMPLETED]);

        // No SteVe call should happen — the WHERE filter excludes terminal states.
        $this->steve->shouldNotReceive('getTransaction');

        $this->runJob();
    }
}
