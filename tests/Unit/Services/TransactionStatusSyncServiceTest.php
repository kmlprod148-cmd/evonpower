<?php

namespace Tests\Unit\Services;

use App\Models\Reservation;
use App\Services\TransactionStatusSyncService;
use Tests\TestCase;

/**
 * Tests unitaires pour TransactionStatusSyncService.
 * Vérifie la logique de détection PAID et la synchronisation des statuts.
 */
class TransactionStatusSyncServiceTest extends TestCase
{
    protected TransactionStatusSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionStatusSyncService::class);
    }

    /** @test */
    public function it_returns_false_when_reservation_is_null(): void
    {
        $this->assertFalse($this->service->isReservationPaid(null));
    }

    /** @test */
    public function it_recognizes_paid_status(): void
    {
        $reservation = new Reservation(['payment_status' => 'PAID']);
        $this->assertTrue($this->service->isReservationPaid($reservation));

        $reservation = new Reservation(['payment_status' => 'PAYE']);
        $this->assertTrue($this->service->isReservationPaid($reservation));

        $reservation = new Reservation(['payment_status' => 'paid']);
        $this->assertTrue($this->service->isReservationPaid($reservation));
    }

    /** @test */
    public function it_recognizes_non_paid_status(): void
    {
        $reservation = new Reservation(['payment_status' => 'PENDING']);
        $this->assertFalse($this->service->isReservationPaid($reservation));

        $reservation = new Reservation(['payment_status' => null]);
        $this->assertFalse($this->service->isReservationPaid($reservation));

        $reservation = new Reservation(['payment_status' => '']);
        $this->assertFalse($this->service->isReservationPaid($reservation));
    }

    /** @test */
    public function it_returns_false_for_transaction_without_reservation_id(): void
    {
        $transaction = new \App\Models\Transaction(['reservation_id' => null, 'status' => 'pending']);
        $result = $this->service->syncTransactionStatusIfPaid($transaction);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_for_already_completed_transaction(): void
    {
        $transaction = new \App\Models\Transaction([
            'reservation_id' => 1,
            'status' => 'completed',
        ]);
        $result = $this->service->syncTransactionStatusIfPaid($transaction);
        $this->assertFalse($result);
    }
}
