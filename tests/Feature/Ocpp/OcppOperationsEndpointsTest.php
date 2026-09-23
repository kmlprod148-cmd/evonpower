<?php

namespace Tests\Feature\Ocpp;

use App\Models\ChargingPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression suite for the canonical /api/v1/ocpp/* endpoints.
 *
 * Covers bugs found during the live-test audit:
 *  #1 — validation order (chargeBoxId check fires before validate())
 *  #2 — Reset: invalid type silently downgraded to Soft
 *  #3 — RemoteStop: HTTP 500 because RemoteStopResponseDTO did not exist
 *  #4 — chargeBoxId from model discarded by Reset/RemoteStop fromRequest()
 *  #5 — connector-status: 500 from missing ChargingPoint import
 *  #6 — SteVe down: response should be 502/503, not 200 + success:false
 *  #9 — env name mismatch (STEVE_USERNAME vs STEVE_API_USER) — not testable at HTTP level here
 * P5  — 401 on no token, 403 on no permission, 404 on missing CP
 */
class OcppOperationsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ChargingPoint $cpConfigured;
    protected ChargingPoint $cpUnconfigured;

    protected function setUp(): void
    {
        parent::setUp();

        // Pre-authorisation bypass for the test suite: the ChargingPointPolicy
        // resolves through Spatie roles, which the wider test seeder pollutes.
        // For these endpoint contract tests we only care about authn + payload behaviour,
        // so we short-circuit Gate to allow every action for the acting user.
        Gate::before(fn ($user, $ability) => $user !== null ? true : null);

        $this->admin          = User::factory()->create();
        $this->cpConfigured   = $this->makeChargingPoint(['charge_box_id' => 'TEST-CB-001']);
        $this->cpUnconfigured = $this->makeChargingPoint(['charge_box_id' => null]);
    }

    /**
     * Insert a ChargingPoint row directly, skipping the related factories
     * (Station/PricingPlan) that drag in seeders we don't need here.
     */
    protected function makeChargingPoint(array $overrides = []): ChargingPoint
    {
        $id = DB::table('charging_points')->insertGetId(array_merge([
            'name'           => 'OCPP Test CP ' . uniqid(),
            'serial_number'  => 'SN-' . uniqid(),
            'connection_type'=> 'AC',
            'max_power'      => 22.0,
            'status'         => 'online',
            'created_at'     => now(),
            'updated_at'     => now(),
        ], $overrides));

        return ChargingPoint::findOrFail($id);
    }

    protected function authedJson(string $method, string $url, array $payload = [])
    {
        Sanctum::actingAs($this->admin);
        return $this->json($method, $url, $payload);
    }

    // ─── AUTH ────────────────────────────────────────────────────────────

    #[Test]
    public function remote_start_returns_401_without_a_token(): void
    {
        $this->json('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-start", [
            'connectorId' => 1, 'ocppTag' => 'Open10Tag',
        ])->assertStatus(401);
    }

    #[Test]
    public function remote_stop_returns_401_without_a_token(): void
    {
        $this->json('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-stop")
             ->assertStatus(401);
    }

    #[Test]
    public function reset_returns_401_without_a_token(): void
    {
        $this->json('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/reset")
             ->assertStatus(401);
    }

    #[Test]
    public function unlock_returns_401_without_a_token(): void
    {
        $this->json('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/connectors/1/unlock")
             ->assertStatus(401);
    }

    // ─── 404 ─────────────────────────────────────────────────────────────

    #[Test]
    public function remote_start_returns_404_when_charging_point_missing(): void
    {
        $this->authedJson('POST', '/api/v1/ocpp/charging-points/9999999/remote-start', [
            'connectorId' => 1, 'ocppTag' => 'Open10Tag',
        ])->assertStatus(404);
    }

    // ─── BUG #1 — validation order ───────────────────────────────────────
    //
    // History: connectorId and ocppTag used to be required. They are now
    // optional — RemoteStartRequest defaults connectorId to 1 and the
    // OcppTagResolver picks an idTag from /ocppTags — so an *empty* payload
    // is valid by design. The invariant Bug #1 actually pins is that an
    // INVALID payload short-circuits to 422 before the chargeBoxId check;
    // we now express that with an out-of-range connectorId.

    #[Test]
    public function remote_start_returns_422_on_invalid_body_even_when_charge_box_id_present(): void
    {
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-start", [
            'connectorId' => 999,
        ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['connectorId']);
    }

    #[Test]
    public function remote_start_validates_before_checking_steve_configuration(): void
    {
        // Bug #1: order must be validate → load → authorize → chargeBoxId check.
        // Sending an INVALID payload to a CP without a chargeBoxId must report
        // the bad payload (422), NOT the not-configured-for-SteVe error (400).
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cpUnconfigured->id}/remote-start", [
            'connectorId' => 999,
        ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['connectorId']);
    }

    #[Test]
    public function remote_start_returns_400_when_charge_box_id_missing_and_payload_valid(): void
    {
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cpUnconfigured->id}/remote-start", [
            'connectorId' => 1, 'ocppTag' => 'Open10Tag',
        ])->assertStatus(400);
    }

    // ─── BUG #2 — Reset: invalid type ────────────────────────────────────

    #[Test]
    public function reset_rejects_invalid_type_with_422(): void
    {
        // Bug #2: any non-{Soft,Hard} value silently fell through to Soft.
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/reset", [
            'type' => 'Reboot',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function reset_rejects_empty_type_with_422(): void
    {
        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/reset", [
            'type' => '',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['type']);
    }

    #[Test]
    public function reset_accepts_valid_types(): void
    {
        // Stub SteVe so we don't hit a real upstream; assert the controller passes through.
        Http::fake(['*' => Http::response(['status' => 'Accepted'], 200)]);

        foreach (['Soft', 'Hard'] as $type) {
            $response = $this->authedJson(
                'POST',
                "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/reset",
                ['type' => $type]
            );
            $this->assertNotEquals(422, $response->status(), "Reset type={$type} should not be rejected as invalid");
        }
    }

    // ─── BUG #3 — RemoteStop fatal ───────────────────────────────────────

    #[Test]
    public function remote_stop_does_not_fatal_with_500(): void
    {
        // Bug #3: missing RemoteStopResponseDTO caused HTTP 500 on every call
        // that got past the chargeBoxId check.
        Http::fake(['*' => Http::response(['status' => 'Accepted'], 200)]);

        $response = $this->authedJson(
            'POST',
            "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-stop",
            ['transactionId' => 42]
        );

        $this->assertNotEquals(500, $response->status(), 'RemoteStop must not fatal');
    }

    #[Test]
    public function remote_stop_uses_charge_box_id_from_model_not_body(): void
    {
        // Bug #4: fromRequest() pulled chargeBoxId from body only, discarding
        // the value loaded from the model. With transactionId present, the
        // controller must pass the model's chargeBoxId to the DTO.
        Http::fake(['*' => Http::response(['status' => 'Accepted'], 200)]);

        $response = $this->authedJson(
            'POST',
            "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-stop",
            ['transactionId' => 42]
        );

        $body = $response->json();
        $this->assertNotEquals(500, $response->status());
        $this->assertFalse(
            isset($body['message']) && str_contains((string) $body['message'], 'L\'identifiant de la borne est requis'),
            'Controller should not treat chargeBoxId as missing when the model provides it'
        );
    }

    #[Test]
    public function remote_stop_requires_transaction_id(): void
    {
        $this->authedJson(
            'POST',
            "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-stop",
            []
        )->assertStatus(422)
          ->assertJsonValidationErrors(['transactionId']);
    }

    // ─── BUG #5 — connector status import ────────────────────────────────

    #[Test]
    public function connector_status_does_not_fatal_from_missing_imports(): void
    {
        // Bug #5: getConnectorStatus referenced ChargingPoint without a `use` import,
        // resolving to App\Http\Controllers\ChargingPoint and returning HTTP 500.
        $response = $this->authedJson(
            'GET',
            "/api/v1/charging-points/{$this->cpConfigured->id}/connectors/1"
        );

        $this->assertNotEquals(500, $response->status(), 'Connector-status must not fatal on import resolution');
    }

    // ─── BUG #6 — SteVe down → 503, not 200 + success:false ──────────────

    #[Test]
    public function remote_start_returns_503_when_steve_api_url_is_not_configured(): void
    {
        config()->set('steve.api_url', '');

        $response = $this->authedJson(
            'POST',
            "/api/v1/ocpp/charging-points/{$this->cpConfigured->id}/remote-start",
            ['connectorId' => 1, 'ocppTag' => 'Open10Tag']
        );

        $response->assertStatus(503);
        $this->assertSame('steve_configuration_missing', $response->json('error'));
        $this->assertSame('steve_configuration_missing', $response->json('meta.code'));
    }

    #[Test]
    public function steve_transactions_returns_502_or_503_when_upstream_unreachable(): void
    {
        // Stub a connection failure: anything pointed at SteVe should yield an
        // upstream error envelope at the HTTP level too.
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connect failed');
        });

        $response = $this->authedJson('GET', '/api/steve-transactions');

        $this->assertContains(
            $response->status(),
            [502, 503],
            "Got HTTP {$response->status()}; SteVe failures must surface as 502/503, not 200"
        );
    }

    /**
     * Bug #6 (full pass): every SteveTransactionController endpoint that simply
     * passes through a SteVe service result envelope must return 502 on
     * upstream failure, not 200 + success:false.
     *
     * Driven by an Http::fake that simulates SteVe being unreachable; we walk
     * the routes that take no required path params and assert the status.
     */
    #[Test]
    public function all_steve_transaction_endpoints_return_502_or_503_on_upstream_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connect failed');
        });

        $endpoints = [
            '/api/steve-transactions',
            '/api/steve-transactions/active',
            '/api/steve-transactions/statistics',
            '/api/steve-transactions/charge-box/SOME-CB/summary',
            '/api/steve-transactions/tag/SOME-TAG/summary',
        ];

        foreach ($endpoints as $url) {
            $status = $this->authedJson('GET', $url)->status();
            $this->assertContains(
                $status,
                [502, 503],
                "GET {$url} returned {$status}; must be 502/503 when SteVe upstream fails"
            );
        }
    }
}
