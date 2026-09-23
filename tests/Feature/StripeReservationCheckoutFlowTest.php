<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Services\AdminConfigurationService;
use App\Services\StripeReservationCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class StripeReservationCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(AdminConfigurationService::class, new class {
            public function getSetting(string $category, string $key, $default = null)
            {
                return $default;
            }
        });

        if (!Schema::hasTable('admin_settings')) {
            Schema::create('admin_settings', function (Blueprint $table) {
                $table->id();
                $table->string('category');
                $table->string('key');
                $table->text('value')->nullable();
                $table->string('type')->default('text');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_successful_stripe_webhook_marks_payment_complete_and_queues_remote_start(): void
    {
        config([
            'auto-remote-start.enabled' => true,
            'auto-remote-start.auto_approve_on_payment' => true,
            'auto-remote-start.mode.start_mode' => 'immediate',
        ]);

        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
            'estimated_cost' => 24.50,
            'amount' => 24.50,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $transaction = Transaction::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'charging_point_id' => $reservation->charging_point_id,
            'amount' => 24.50,
            'currency' => 'EUR',
            'status' => 'pending',
            'payment_method' => 'stripe',
            'payment_reference' => 'cs_test_checkout',
            'stripe_session_id' => 'cs_test_checkout',
        ]);

        $service = app(StripeReservationCheckoutService::class);

        $result = $service->handleWebhookEvent([
            'id' => 'evt_successful_checkout',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_checkout',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_checkout',
                    'currency' => 'eur',
                    'metadata' => [
                        'reservation_id' => (string) $reservation->id,
                        'transaction_id' => (string) $transaction->id,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result['success']);

        $reservation->refresh();
        $transaction->refresh();

        $statusValue = $reservation->status instanceof ReservationStatus
            ? $reservation->status->value
            : $reservation->status;

        $this->assertSame('completed', $transaction->status);
        $this->assertNotNull($transaction->completed_at);
        $this->assertSame('cs_test_checkout', $transaction->stripe_session_id);
        $this->assertSame('PAID', strtoupper((string) $reservation->payment_status));
        $this->assertSame('confirmed', $statusValue);
        $this->assertSame('queued', $reservation->session_initiation_status);
        $this->assertSame('pi_test_checkout', $reservation->payment_gateway_transaction_id);
        $this->assertNotNull($reservation->payment_confirmed_at);
        $this->assertNotNull($reservation->payment_webhook_received_at);
    }

    public function test_duplicate_webhooks_remain_idempotent(): void
    {
        config([
            'auto-remote-start.enabled' => true,
            'auto-remote-start.auto_approve_on_payment' => true,
            'auto-remote-start.mode.start_mode' => 'immediate',
        ]);

        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::PENDING,
            'payment_status' => 'PENDING',
            'estimated_cost' => 18.00,
            'amount' => 18.00,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);

        Transaction::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'charging_point_id' => $reservation->charging_point_id,
            'amount' => 18.00,
            'currency' => 'EUR',
            'status' => 'pending',
            'payment_method' => 'stripe',
            'payment_reference' => 'cs_duplicate_checkout',
            'stripe_session_id' => 'cs_duplicate_checkout',
        ]);

        $event = [
            'id' => 'evt_duplicate_checkout',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_duplicate_checkout',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_duplicate_checkout',
                    'currency' => 'eur',
                    'metadata' => [
                        'reservation_id' => (string) $reservation->id,
                    ],
                ],
            ],
        ];

        $service = app(StripeReservationCheckoutService::class);

        $first = $service->handleWebhookEvent($event);
        $second = $service->handleWebhookEvent($event);

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertTrue((bool) ($second['already_processed'] ?? false));

        $reservation->refresh();

        $this->assertSame('PAID', strtoupper((string) $reservation->payment_status));
        $this->assertSame('queued', $reservation->session_initiation_status);
        $this->assertCount(1, Transaction::where('reservation_id', $reservation->id)->get());
    }

    public function test_thank_you_status_endpoint_returns_payment_and_remote_start_state(): void
    {
        $reservation = Reservation::factory()->create([
            'status' => ReservationStatus::ACTIVE,
            'payment_status' => 'PAID',
            'estimated_cost' => 31.75,
            'amount' => 31.75,
            'payment_confirmed_at' => now(),
            'session_initiation_status' => 'success',
            'payment_gateway_transaction_id' => 'pi_status_test',
        ]);

        $transaction = Transaction::create([
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'charging_point_id' => $reservation->charging_point_id,
            'amount' => 31.75,
            'currency' => 'EUR',
            'status' => 'completed',
            'payment_method' => 'stripe',
            'payment_reference' => 'cs_status_test',
            'stripe_session_id' => 'cs_status_test',
            'completed_at' => now(),
        ]);

        $chargingSession = ChargingSession::create([
            'session_id' => (string) Str::uuid(),
            'reservation_id' => $reservation->id,
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'status' => ChargingSession::STATUS_ACTIVE,
            'payment_status' => 'paid',
            'started_at' => now(),
            'steve_transaction_id' => 'steve_tx_123',
        ]);

        $reservation->update([
            'charging_session_id' => $chargingSession->id,
        ]);

        $response = $this->getJson(route('reservations.thank-you.status', [
            'id' => $reservation->id,
            'session_id' => $transaction->stripe_session_id,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status.payment.confirmed', true)
            ->assertJsonPath('status.payment.gateway_transaction_id', 'pi_status_test')
            ->assertJsonPath('status.remote_start.status', 'success')
            ->assertJsonPath('status.remote_start.steve_transaction_id', 'steve_tx_123');
    }
}
