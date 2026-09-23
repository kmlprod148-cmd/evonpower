<?php

declare(strict_types=1);

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
 * Default-fill behaviour for POST /api/v1/ocpp/charging-points/{id}/remote-start.
 *
 *  - When `connectorId` is omitted, the controller defaults to 1.
 *  - When `ocppTag` is omitted, OcppTagResolver picks one:
 *      * config('steve.default_id_tag') if it appears in SteVe's usable tags
 *      * otherwise the first row from /ocppTags filtered by
 *        expired=FALSE, blocked=FALSE, inTransaction=FALSE
 *  - When no tag is configured AND SteVe returns no usable tags, the endpoint
 *    surfaces a 422 with code `ocpp_tag_unavailable` instead of dispatching
 *    a RemoteStart that the charger would just reject.
 */
class RemoteStartDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ChargingPoint $cp;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn ($user, $ability) => $user !== null ? true : null);

        config()->set('steve.api_url', 'http://steve.test/steve');
        config()->set('steve.username', 'admin');
        config()->set('steve.password', 'pw');
        config()->set('steve.default_id_tag', 'DEFAULT-TAG');

        $this->admin = User::factory()->create();
        $id = DB::table('charging_points')->insertGetId([
            'name'            => 'CP-DEF-' . uniqid(),
            'serial_number'   => 'SN-' . uniqid(),
            'connection_type' => 'AC',
            'max_power'       => 22.0,
            'status'          => 'online',
            'charge_box_id'   => 'CB-DEFAULTS-01',
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

    /**
     * Inspect the last RemoteStart body sent on the wire. Returns the decoded
     * JSON payload for the /ocpp/remote-start request, or null if none was sent.
     */
    private function lastRemoteStartBody(): ?array
    {
        $body = null;
        Http::assertSent(function ($req) use (&$body) {
            if (str_contains($req->url(), '/ocpp/remote-start')) {
                $body = json_decode($req->body(), true);
            }
            return true;
        });
        return $body;
    }

    #[Test]
    public function omitting_connector_id_defaults_to_one(): void
    {
        Http::fake([
            '*manager/api/v1/ocppTags*' => Http::response([
                ['ocppTagPk' => 1, 'idTag' => 'DEFAULT-TAG'],
            ], 200),
            '*manager/api/v1/ocpp/remote-start' => Http::response(['status' => 'ACCEPTED', 'transaction' => ['id' => 1]], 200),
        ]);

        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'ocppTag' => 'Open10Tag',
        ])->assertStatus(200);

        $body = $this->lastRemoteStartBody();
        $this->assertSame(1, $body['connectorId'] ?? null, 'connectorId must default to 1 when omitted');
    }

    #[Test]
    public function omitting_ocpp_tag_uses_configured_default_when_available(): void
    {
        Http::fake([
            '*manager/api/v1/ocppTags*' => Http::response([
                ['ocppTagPk' => 11, 'idTag' => 'DEFAULT-TAG'],
                ['ocppTagPk' => 12, 'idTag' => 'OTHER-TAG'],
            ], 200),
            '*manager/api/v1/ocpp/remote-start' => Http::response(['status' => 'ACCEPTED', 'transaction' => ['id' => 1]], 200),
        ]);

        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
        ])->assertStatus(200);

        $body = $this->lastRemoteStartBody();
        $this->assertSame('DEFAULT-TAG', $body['ocppTag'] ?? null, 'configured default tag should be used when usable');

        // The /ocppTags lookup must have applied the documented filter values.
        $sawUsableFilter = false;
        Http::assertSent(function ($req) use (&$sawUsableFilter) {
            if (str_contains($req->url(), '/manager/api/v1/ocppTags')) {
                $sawUsableFilter = str_contains($req->url(), 'expired=FALSE')
                    && str_contains($req->url(), 'blocked=FALSE')
                    && str_contains($req->url(), 'inTransaction=FALSE');
            }
            return true;
        });
        $this->assertTrue($sawUsableFilter, '/ocppTags lookup must filter usable tags only');
    }

    #[Test]
    public function omitting_ocpp_tag_falls_back_to_first_usable_tag_when_default_blocked(): void
    {
        // SteVe says the configured default ('DEFAULT-TAG') isn't in the
        // usable list, so the resolver picks the first usable row instead.
        Http::fake([
            '*manager/api/v1/ocppTags*' => Http::response([
                ['ocppTagPk' => 22, 'idTag' => 'FALLBACK-TAG'],
                ['ocppTagPk' => 23, 'idTag' => 'OTHER-TAG'],
            ], 200),
            '*manager/api/v1/ocpp/remote-start' => Http::response(['status' => 'ACCEPTED', 'transaction' => ['id' => 1]], 200),
        ]);

        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
        ])->assertStatus(200);

        $body = $this->lastRemoteStartBody();
        $this->assertSame('FALLBACK-TAG', $body['ocppTag'] ?? null);
    }

    #[Test]
    public function returns_422_when_no_usable_tag_is_available(): void
    {
        config()->set('steve.default_id_tag', ''); // no configured default
        Http::fake([
            '*manager/api/v1/ocppTags*' => Http::response([], 200), // empty list
        ]);

        $response = $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'ocpp_tag_unavailable');

        // Crucially, no RemoteStart should have hit SteVe.
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/ocpp/remote-start'));
    }

    #[Test]
    public function explicit_ocpp_tag_skips_the_resolver_lookup(): void
    {
        Http::fake([
            '*manager/api/v1/ocpp/remote-start' => Http::response(['status' => 'ACCEPTED', 'transaction' => ['id' => 1]], 200),
        ]);

        $this->authedJson('POST', "/api/v1/ocpp/charging-points/{$this->cp->id}/remote-start", [
            'connectorId' => 1,
            'ocppTag'     => 'Explicit-Tag',
        ])->assertStatus(200);

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/manager/api/v1/ocppTags'));
    }
}
