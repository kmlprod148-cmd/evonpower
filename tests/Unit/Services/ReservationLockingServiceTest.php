<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\Connector;
use App\Models\User;
use App\Services\ReservationLockingService;
use App\Services\OcppOperationsService;
use App\DTO\OCPP\LockConnectorResponseDTO;
use App\DTO\OCPP\UnlockConnectorResponseDTO;
use Mockery;
use Exception;

/**
 * Tests unitaires pour ReservationLockingService
 */
class ReservationLockingServiceTest extends TestCase
{
    protected ReservationLockingService $service;
    protected $mockOcppService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockOcppService = Mockery::mock(OcppOperationsService::class);
        $this->service = new ReservationLockingService($this->mockOcppService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_lock_connector_successfully()
    {
        // Arrange
        $reservation = $this->createMockReservation();
        $chargingPoint = $this->createMockChargingPoint();
        $connector = $this->createMockConnector();

        $reservation->shouldReceive('chargingPoint')->andReturn($chargingPoint);
        $reservation->shouldReceive('connector')->andReturn($connector);
        $reservation->shouldReceive('lock_operation_attempts')->andReturn(0);
        $reservation->shouldReceive('connector_locked')->andReturn(false);

        $chargingPoint->shouldReceive('getAttribute')
            ->with('charge_box_id')
            ->andReturn('CP-001');
        $chargingPoint->shouldReceive('getAttribute')
            ->with('steve_charging_point_id')
            ->andReturn(null);

        $connector->shouldReceive('getAttribute')
            ->with('connector_id')
            ->andReturn(1);

        $lockResponse = new LockConnectorResponseDTO([
            'success' => true,
            'status' => 'Locked',
        ]);

        $this->mockOcppService
            ->shouldReceive('lockConnector')
            ->andReturn($lockResponse);

        // Act
        $result = $this->service->lockConnector($reservation);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Connecteur verrouillé avec succès', $result['message']);
        $this->assertArrayHasKey('locked_at', $result['data']);
    }

    /** @test */
    public function it_returns_already_locked_when_connector_is_locked()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        $reservation->shouldReceive('connector_locked')->andReturn(true);

        // Act
        $result = $this->service->lockConnector($reservation);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Connecteur déjà verrouillé', $result['message']);
        $this->assertTrue($result['data']['already_locked']);
    }

    /** @test */
    public function it_fails_when_max_attempts_reached()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        $reservation->shouldReceive('connector_locked')->andReturn(false);
        $reservation->shouldReceive('lock_operation_attempts')->andReturn(3);

        // Act
        $result = $this->service->lockConnector($reservation);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Nombre maximum de tentatives', $result['message']);
    }

    /** @test */
    public function it_can_check_can_lock_connector()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);
        $connector = Mockery::mock(Connector::class);

        $reservation->shouldReceive('status->value')->andReturn('confirmed');
        $reservation->shouldReceive('payment_status')->andReturn('PAID');
        $reservation->shouldReceive('connector')->andReturn($connector);
        $reservation->shouldReceive('lock_operation_attempts')->andReturn(0);

        $connector->shouldReceive('isAvailable')->andReturn(true);

        // Act
        $result = $this->service->canLockConnector($reservation);

        // Assert
        $this->assertTrue($result['can_lock']);
    }

    /** @test */
    public function it_prevents_lock_when_payment_not_confirmed()
    {
        // Arrange
        $reservation = Mockery::mock(Reservation::class);

        $reservation->shouldReceive('status->value')->andReturn('confirmed');
        $reservation->shouldReceive('payment_status')->andReturn('PENDING');

        // Act
        $result = $this->service->canLockConnector($reservation);

        // Assert
        $this->assertFalse($result['can_lock']);
        $this->assertStringContainsString('Paiement non confirmé', $result['reason']);
    }

    /**
     * Helper methods
     */
    protected function createMockReservation()
    {
        return Mockery::mock(Reservation::class)->makePartial();
    }

    protected function createMockChargingPoint()
    {
        return Mockery::mock(ChargingPoint::class)->makePartial();
    }

    protected function createMockConnector()
    {
        return Mockery::mock(Connector::class)->makePartial();
    }
}
