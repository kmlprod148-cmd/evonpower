<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Reservations;

use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStartResponseDTO;
use App\DTO\OCPP\RemoteStartStatusEnum;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\DTO\OCPP\RemoteStopResponseDTO;
use App\DTO\OCPP\RemoteStopStatusEnum;
use App\Enums\ReservationStatus;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\User;
use App\Services\OcppOperationsService;
use App\Services\Reservations\ReservationChargingService;
use App\Services\SteVe\OcppTagResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Pins the contract of ReservationChargingService introduced in Slice B.2:
 *   - Calls OcppOperationsService with a correctly-shaped DTO
 *   - On accepted: mutates the reservation row using columns that actually
 *     exist on the table (actual_start_time / actual_end_time, ReservationStatus)
 *   - On rejected: leaves the reservation row untouched
 *   - Tag resolution defers to OcppTagResolver
 */
class ReservationChargingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $ocppService;
    protected $tagResolver;
    protected ReservationChargingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ocppService = Mockery::mock(OcppOperationsService::class);
        $this->tagResolver = Mockery::mock(OcppTagResolver::class);
        $this->service = new ReservationChargingService($this->ocppService, $this->tagResolver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeReservation(): Reservation
    {
        $user = User::factory()->create();
        $cpId = DB::table('charging_points')->insertGetId([
            'name'            => 'CP-RES-' . uniqid(),
            'serial_number'   => 'SN-' . uniqid(),
            'connection_type' => 'AC',
            'max_power'       => 22.0,
            'status'          => 'online',
            'charge_box_id'   => 'CB-RES-01',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        ChargingPoint::findOrFail($cpId);

        return Reservation::create([
            'user_id'           => $user->id,
            'charging_point_id' => $cpId,
            'connector_id'      => 2,
            'status'            => ReservationStatus::CONFIRMED,
            'amount'            => 10.00,
        ]);
    }

    public function test_start_dispatches_canonical_dto_and_flips_reservation_to_active(): void
    {
        $reservation = $this->makeReservation();

        $this->tagResolver->shouldReceive('resolve')->once()->andReturn('TAG-OK');

        $this->ocppService
            ->shouldReceive('remoteStart')
            ->once()
            ->withArgs(function (RemoteStartRequestDTO $dto, array $ctx) {
                return $dto->chargeBoxId === 'CB-RES-01'
                    && $dto->connectorId === 2
                    && $dto->ocppTag === 'TAG-OK'
                    && str_starts_with($ctx['idempotencyKey'], 'reservation:');
            })
            ->andReturn(RemoteStartResponseDTO::accepted(['id' => 99]));

        $result = $this->service->start($reservation);

        $this->assertTrue($result['success']);
        $this->assertSame('ocpp', $result['method']);

        $reservation->refresh();
        $this->assertSame(ReservationStatus::ACTIVE, $reservation->status);
        $this->assertNotNull($reservation->actual_start_time);
    }

    public function test_start_leaves_reservation_untouched_when_steve_rejects(): void
    {
        $reservation = $this->makeReservation();
        $originalStatus = $reservation->status;

        $this->tagResolver->shouldReceive('resolve')->once()->andReturn('TAG-OK');

        $this->ocppService
            ->shouldReceive('remoteStart')
            ->once()
            ->andReturn(RemoteStartResponseDTO::rejected('Charger offline'));

        $result = $this->service->start($reservation);

        $this->assertFalse($result['success']);
        $this->assertSame('remote_start_rejected', $result['code']);

        $reservation->refresh();
        $this->assertEquals($originalStatus, $reservation->status);
        $this->assertNull($reservation->actual_start_time);
    }

    public function test_start_returns_ocpp_tag_unavailable_when_resolver_yields_null(): void
    {
        $reservation = $this->makeReservation();
        $this->tagResolver->shouldReceive('resolve')->once()->andReturn(null);
        $this->ocppService->shouldNotReceive('remoteStart');

        $result = $this->service->start($reservation);

        $this->assertFalse($result['success']);
        $this->assertSame('ocpp_tag_unavailable', $result['code']);
    }

    public function test_start_returns_charge_point_unconfigured_when_charge_box_id_missing(): void
    {
        $reservation = $this->makeReservation();
        DB::table('charging_points')->where('id', $reservation->charging_point_id)
            ->update(['charge_box_id' => null, 'steve_charging_point_id' => null]);
        $reservation->refresh()->load('chargingPoint');

        $this->ocppService->shouldNotReceive('remoteStart');
        $this->tagResolver->shouldNotReceive('resolve');

        $result = $this->service->start($reservation);

        $this->assertFalse($result['success']);
        $this->assertSame('charge_point_unconfigured', $result['code']);
    }

    public function test_stop_flips_reservation_to_completed_on_accepted(): void
    {
        $reservation = $this->makeReservation();
        $reservation->update([
            'status'              => ReservationStatus::ACTIVE,
            'charging_session_id' => $this->createChargingSessionWithSteveTxn(123),
        ]);

        $this->ocppService
            ->shouldReceive('remoteStop')
            ->once()
            ->withArgs(function (RemoteStopRequestDTO $dto, array $ctx) {
                return $dto->chargeBoxId === 'CB-RES-01'
                    && $dto->transactionId === 123;
            })
            ->andReturn(RemoteStopResponseDTO::accepted(123));

        $result = $this->service->stop($reservation, 'manual');

        $this->assertTrue($result['success']);
        $this->assertSame('manual', $result['reason']);

        $reservation->refresh();
        $this->assertSame(ReservationStatus::COMPLETED, $reservation->status);
        $this->assertNotNull($reservation->actual_end_time);
    }

    public function test_stop_returns_transaction_id_unknown_without_charging_session(): void
    {
        $reservation = $this->makeReservation();
        $this->ocppService->shouldNotReceive('remoteStop');

        $result = $this->service->stop($reservation);

        $this->assertFalse($result['success']);
        $this->assertSame('transaction_id_unknown', $result['code']);
    }

    private function createChargingSessionWithSteveTxn(int $txnId): int
    {
        return DB::table('charging_sessions')->insertGetId([
            'charging_point_id'     => 1,
            'user_id'               => 1,
            'status'                => 'active',
            'connector_id'          => 2,
            'started_at'            => now(),
            'steve_transaction_id'  => (string) $txnId,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }
}
