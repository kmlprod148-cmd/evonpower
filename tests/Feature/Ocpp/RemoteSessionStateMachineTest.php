<?php

declare(strict_types=1);

namespace Tests\Feature\Ocpp;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests the hardened remote-{start,stop} state machine:
 *
 *   - Accepted RemoteStart promotes a pending ChargingSession to ACTIVE and
 *     stamps `steve_transaction_id` from the upstream response.
 *   - Accepted RemoteStop transitions a matching active session to STOPPED
 *     and records `steve_stop_response`.
 *   - A second RemoteStart while a non-terminal session occupies the slot
 *     short-circuits with 409 Conflict — no SteVe round-trip.
 *   - A RemoteStop targeting an already-terminal session returns 409 too.
 *   - Tighter FormRequest validation (ocppTag regex, transactionId range)
 *     rejects malformed payloads with 422.
 */
class RemoteSessionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ChargingPoint $cp;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn ($user, $ability) => $user !== null ? true : null);

        // Without these, SteVeHttpClientService::makeRequest() throws
        // SteVeConfigurationException → HTTP 503 before Http::fake() can
        // intercept, and every accepted-path test rejects with 503.
        config()->set('steve.api_url', 'http://steve.test/steve');
        config()->set('steve.username', 'admin');
        config()->set('steve.password', 'pw');

        $this->admin = User::factory()->create();
        $id = DB::table('charging_points')->insertGetId([
            'name'            => 'CP-STATE-' . uniqid(),
            'serial_number'   => 'SN-' . uniqid(),
            'connection_type' => 'AC',
            'max_power'       => 22.0,
            'status'          => 'online',
            'charge_box_id'   => 'CB-STATE-01',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        $this->cp = ChargingPoint::findOrFail($id);
    }

    protected function authedJson(string $method, string $url, array $payload = [])
    {
        Sanctum::actingAs($this->admin);
        return $this->json($method, $url, $payload);
    }

    // ─── Tighter validation ──────────────────────────────────────────────

    #[Test]
    public function remote_start_rejects_ocpp_tag_with_invalid_characters(): void
    {
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
            'ocppTag'     => 'has spaces!',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ocppTag']);
    }

    #[Test]
    public function remote_start_rejects_connector_id_above_max(): void
    {
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 999,
            'ocppTag'     => 'Open10Tag',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['connectorId']);
    }

    #[Test]
    public function remote_stop_rejects_transaction_id_above_int32(): void
    {
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-stop", [
            'transactionId' => 9999999999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transactionId']);
    }

    // ─── State machine: RemoteStart ──────────────────────────────────────

    #[Test]
    public function accepted_remote_start_promotes_pending_session_to_active(): void
    {
        // Seed an offer-flow-style pending session for this CP+connector.
        $session = ChargingSession::create([
            'session_id'         => 'SESS-' . uniqid(),
            'charging_point_id'  => $this->cp->id,
            'user_id'            => $this->admin->id,
            'connector_id'       => 1,
            'status'             => ChargingSession::STATUS_PENDING,
            'started_at'         => now(),
        ]);

        Http::fake(['*' => Http::response([
            'status'      => 'ACCEPTED',
            'transaction' => ['id' => 4242],
        ], 200)]);

        $response = $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
            'ocppTag'     => 'Open10Tag',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('meta.status', 'ACCEPTED');

        $session->refresh();
        $this->assertSame(ChargingSession::STATUS_ACTIVE, $session->status);
        $this->assertSame('4242', $session->steve_transaction_id);
        $this->assertSame('Open10Tag', $session->ocpp_tag);
    }

    #[Test]
    public function second_remote_start_on_active_session_returns_409_conflict(): void
    {
        ChargingSession::create([
            'session_id'         => 'SESS-' . uniqid(),
            'charging_point_id'  => $this->cp->id,
            'user_id'            => $this->admin->id,
            'connector_id'       => 1,
            'status'             => ChargingSession::STATUS_ACTIVE,
            'steve_transaction_id' => '1001',
            'started_at'         => now(),
        ]);

        Http::fake(['*' => Http::response(['status' => 'ACCEPTED'], 200)]);

        $response = $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
            'ocppTag'     => 'Open10Tag',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error', 'session_already_active');
        // Crucially: SteVe must not have been called.
        Http::assertNothingSent();
    }

    #[Test]
    public function operator_initiated_start_without_local_session_still_succeeds(): void
    {
        // No local ChargingSession row → operator-initiated path.
        Http::fake(['*' => Http::response([
            'status'      => 'ACCEPTED',
            'transaction' => ['id' => 77],
        ], 200)]);

        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
            'ocppTag'     => 'Op1Tag',
        ])->assertStatus(200);

        $this->assertSame(0, ChargingSession::count(), 'No local session should be auto-created');
    }

    // ─── State machine: RemoteStop ───────────────────────────────────────

    #[Test]
    public function accepted_remote_stop_transitions_active_session_to_stopped(): void
    {
        $session = ChargingSession::create([
            'session_id'           => 'SESS-' . uniqid(),
            'charging_point_id'    => $this->cp->id,
            'user_id'              => $this->admin->id,
            'connector_id'         => 1,
            'status'               => ChargingSession::STATUS_ACTIVE,
            'steve_transaction_id' => '5050',
            'started_at'           => now(),
        ]);

        Http::fake(['*' => Http::response([
            'status'      => 'ACCEPTED',
            'transaction' => ['id' => 5050],
        ], 200)]);

        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-stop", [
            'transactionId' => 5050,
        ])->assertStatus(200);

        $session->refresh();
        $this->assertSame(ChargingSession::STATUS_STOPPED, $session->status);
        $this->assertNotNull($session->stopped_at);
        $this->assertSame('remote_stop', $session->stop_reason);
    }

    #[Test]
    public function remote_stop_on_already_terminated_session_returns_409(): void
    {
        ChargingSession::create([
            'session_id'           => 'SESS-' . uniqid(),
            'charging_point_id'    => $this->cp->id,
            'user_id'              => $this->admin->id,
            'connector_id'         => 1,
            'status'               => ChargingSession::STATUS_STOPPED,
            'steve_transaction_id' => '6060',
            'started_at'           => now(),
            'stopped_at'           => now(),
        ]);

        Http::fake(['*' => Http::response(['status' => 'ACCEPTED'], 200)]);

        $response = $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-stop", [
            'transactionId' => 6060,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error', 'session_already_terminated');
        Http::assertNothingSent();
    }
}
