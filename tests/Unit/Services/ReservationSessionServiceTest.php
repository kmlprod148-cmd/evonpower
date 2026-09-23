<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Models\ChargingPoint;
use App\Models\Connector;
use App\Models\User;
use App\Services\ReservationSessionService;
use App\Services\ReservationLockingService;
use App\Services\OcppOperationsService;
use App\Enums\ReservationStatus;
use Carbon\Carbon;
use Mockery;

/**
 * Tests unitaires pour ReservationSessionService
 * 
 * Tests des scénarios critiques :
 * - Initiation de session après paiement carte
 * - Workflow de confirmation crédit solde avec timeout
 * - Validation de paiement synchronisé
 */
class ReservationSessionServiceTest extends TestCase
{
    protected ReservationSessionService $service;
    protected $mockLockingService;
    protected $mockOcppService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLockingService = Mockery::mock(ReservationLockingService::class);
        $this->mockOcppService = Mockery::mock(OcppOperationsService::class);

        $this->service = new ReservationSessionService(
            $this->mockOcppService,
            $this->mockLockingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_validate_card_payment_and_initiate_session()
    {
        // Arrange
        $reservation = $this->createMockReservation([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
            'connector_locked' => false,
        ]);

        $this->mockLockingService
            ->shouldReceive('lockConnector')
            ->andReturn(['success' => true]);

        $this->mockOcppService
            ->shouldReceive('remoteStart')
            ->andReturn($this->createMockRemoteStartResponse(true));

        // Act
        $result = $this->service->validateCardPayment(
            $reservation,
            'txn_12345',
            ['status' => 'success']
        );

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Paiement validé et session initiée', $result['message']);
        $this->assertArrayHasKey('session_id', $result);
    }

    /** @test */
    public function it_handles_delayed_payment_webhook()
    {
        // Arrange
        $reservation = $this->createMockReservation([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
        ]);

        // Simuler un webhook retardé - la réservation devrait être traitée normalement
        $this->mockLockingService
            ->shouldReceive('lockConnector')
            ->andReturn(['success' => true]);

        $this->mockOcppService
            ->shouldReceive('remoteStart')
            ->andReturn($this->createMockRemoteStartResponse(true));

        // Act
        $result = $this->service->handleDelayedPaymentWebhook($reservation, [
            'status' => 'success',
            'transaction_id' => 'txn_delayed',
        ]);

        // Assert
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_handles_failed_payment_webhook()
    {
        // Arrange
        $reservation = $this->createMockReservation([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
            'connector_locked' => true,
        ]);

        $this->mockLockingService
            ->shouldReceive('unlockConnector')
            ->andReturn(['success' => true]);

        // Act
        $result = $this->service->handleDelayedPaymentWebhook($reservation, [
            'status' => 'failed',
        ]);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Paiement échoué', $result['message']);
    }

    /** @test */
    public function it_can_start_credit_balance_confirmation()
    {
        // Arrange
        $reservation = $this->createMockReservation([
            'status' => ReservationStatus::PENDING,
            'payment_mode' => null,
        ]);

        $reservation->shouldReceive('userHasSufficientBalance')->andReturn(true);

        // Act
        $result = $this->service->startCreditBalanceConfirmation($reservation);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertInstanceOf(Carbon::class, $result['expires_at']);
    }

    /** @test */
    public function it_fails_credit_confirmation_when_insufficient_balance()
    {
        // Arrange
        $reservation = $this->createMockReservation([
            'status' => ReservationStatus::PENDING,
        ]);

        $reservation->shouldReceive('userHasSufficientBalance')->andReturn(false);

        // Act
        $result = $this->service->startCreditBalanceConfirmation($reservation);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Solde insuffisant', $result['message']);
    }

    /** @test */
    public function it_cancels_reservation_when_confirmation_expired()
    {
        // Arrange
        $reservation = $this->createMockReservation([
            'status' => ReservationStatus::PENDING_CONFIRMATION,
            'payment_confirmation_expires_at' => Carbon::now()->subMinutes(1),
            'connector_locked' => true,
        ]);

        $this->mockLockingService
            ->shouldReceive('unlockConnector')
            ->andReturn(['success' => true]);

        // Act
        $result = $this->service->confirmCreditBalancePayment($reservation);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('délai de confirmation', $result['message']);
    }

    /** @test */
    public function it_correctly_detects_expired_confirmation()
    {
        // Arrange
        $expiredReservation = $this->createMockReservation([
            'payment_confirmation_expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $validReservation = $this->createMockReservation([
            'payment_confirmation_expires_at' => Carbon::now()->addMinutes(3),
        ]);

        // Act & Assert
        $this->assertTrue($this->service->isConfirmationExpired($expiredReservation));
        $this->assertFalse($this->service->isConfirmationExpired($validReservation));
    }

    /**
     * Helper methods
     */
    protected function createMockReservation(array $attributes = [])
    {
        $reservation = Mockery::mock(Reservation::class)->makePartial();

        foreach ($attributes as $key => $value) {
            $reservation->shouldReceive('getAttribute')
                ->with($key)
                ->andReturn($value);
            $reservation->{$key} = $value;
        }

        $reservation->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        return $reservation;
    }

    protected function createMockRemoteStartResponse(bool $accepted = true)
    {
        $mock = Mockery::mock(\App\DTO\OCPP\RemoteStartResponseDTO::class);
        $mock->shouldReceive('isAccepted')->andReturn($accepted);
        $mock->shouldReceive('toArray')->andReturn([
            'success' => $accepted,
            'status' => $accepted ? 'ACCEPTED' : 'REJECTED',
        ]);

        return $mock;
    }
}
