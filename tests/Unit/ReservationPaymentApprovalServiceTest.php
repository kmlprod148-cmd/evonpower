<?php

namespace Tests\Unit;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Services\ReservationPaymentApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPaymentApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_payment_success_auto_approves_when_enabled(): void
    {
        config(['auto-remote-start.auto_approve_on_payment' => true]);

        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
        ]);

        $service = app(ReservationPaymentApprovalService::class);
        $result = $service->applyPaymentSuccess($reservation, 'stripe', 'test');

        $reservation->refresh();
        $statusValue = $reservation->status instanceof ReservationStatus
            ? $reservation->status->value
            : $reservation->status;

        $this->assertTrue($result['auto_approved']);
        $this->assertSame('PAID', strtoupper((string) $reservation->payment_status));
        $this->assertSame('confirmed', $statusValue);
        $this->assertNotNull($reservation->approved_at);
    }

    public function test_apply_payment_success_auto_approves_cmi_even_when_feature_toggle_is_disabled(): void
    {
        config(['auto-remote-start.auto_approve_on_payment' => false]);

        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
        ]);

        $service = app(ReservationPaymentApprovalService::class);
        $result = $service->applyPaymentSuccess($reservation, 'cmi', 'test');

        $reservation->refresh();
        $statusValue = $reservation->status instanceof ReservationStatus
            ? $reservation->status->value
            : $reservation->status;

        $this->assertTrue($result['auto_approved']);
        $this->assertSame('PAID', strtoupper((string) $reservation->payment_status));
        $this->assertSame('confirmed', $statusValue);
        $this->assertNotNull($reservation->approved_at);
        $this->assertNotNull($reservation->payment_confirmed_at);
    }

    public function test_apply_payment_success_auto_approves_credit_even_when_feature_toggle_is_disabled(): void
    {
        config(['auto-remote-start.auto_approve_on_payment' => false]);

        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
            'payment_method' => 'credit',
            'payment_mode' => 'prepaid',
        ]);

        $service = app(ReservationPaymentApprovalService::class);
        $result = $service->applyPaymentSuccess($reservation, 'prepaid_credit', 'wallet_credit');

        $reservation->refresh();
        $statusValue = $reservation->status instanceof ReservationStatus
            ? $reservation->status->value
            : $reservation->status;

        $this->assertTrue($result['auto_approved']);
        $this->assertSame('PAID', strtoupper((string) $reservation->payment_status));
        $this->assertSame('confirmed', $statusValue);
        $this->assertNotNull($reservation->approved_at);
    }
}
