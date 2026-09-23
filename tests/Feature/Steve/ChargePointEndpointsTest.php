<?php

declare(strict_types=1);

namespace Tests\Feature\Steve;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * BFF contract tests for /api/v1/steve/charge-points.
 *
 * The wire format we promise to clients is the P3 envelope; the wire format we
 * speak to SteVe is documented in /manager/api/v1/chargePoints (3.9.0). These
 * tests pin both: the inbound request shape AND that the right upstream call
 * is made.
 */
class ChargePointEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();

        // Pin a deterministic base URL so the assertSent path checks are stable.
        config()->set('steve.api_url', 'http://steve.test/steve');
        config()->set('steve.username', 'admin');
        config()->set('steve.password', 'pw');
    }

    protected function authed(string $method, string $url, array $body = [])
    {
        Sanctum::actingAs($this->admin);
        return $this->json($method, $url, $body);
    }

    // ─── Auth ────────────────────────────────────────────────────────────

    #[Test]
    public function index_requires_authentication(): void
    {
        $this->json('GET', '/api/v1/steve/charge-points')->assertStatus(401);
    }

    // ─── Index ───────────────────────────────────────────────────────────

    #[Test]
    public function index_calls_manager_chargepoints_with_filters(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints*' => Http::response([
                ['chargeBoxPk' => 1, 'chargeBoxId' => 'CB-A', 'ocppProtocol' => 'ocpp1.6'],
                ['chargeBoxPk' => 2, 'chargeBoxId' => 'CB-B', 'ocppProtocol' => 'ocpp1.6'],
            ], 200),
        ]);

        $response = $this->authed('GET', '/api/v1/steve/charge-points?ocppVersion=V_16');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.0.chargeBoxId', 'CB-A');
        $response->assertJsonPath('meta.count', 2);

        Http::assertSent(function ($req) {
            return $req->method() === 'GET'
                && str_contains($req->url(), '/manager/api/v1/chargePoints')
                && str_contains($req->url(), 'ocppVersion=V_16');
        });
    }

    #[Test]
    public function index_rejects_invalid_ocpp_version(): void
    {
        $this->authed('GET', '/api/v1/steve/charge-points?ocppVersion=V_99')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ocppVersion']);
    }

    // ─── Store ───────────────────────────────────────────────────────────

    #[Test]
    public function store_requires_charge_box_id(): void
    {
        $this->authed('POST', '/api/v1/steve/charge-points', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['chargeBoxId']);
    }

    #[Test]
    public function store_posts_charge_point_form_and_returns_201(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints' => Http::response([
                'chargeBoxPk' => 42,
                'chargeBoxId' => 'CB-NEW',
            ], 201),
        ]);

        $response = $this->authed('POST', '/api/v1/steve/charge-points', [
            'chargeBoxId' => 'CB-NEW',
            'description' => 'Test station',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.chargeBoxPk', 42);

        Http::assertSent(function ($req) {
            return $req->method() === 'POST'
                && str_contains($req->url(), '/manager/api/v1/chargePoints')
                && $req->data() === ['chargeBoxId' => 'CB-NEW', 'description' => 'Test station'];
        });
    }

    // ─── Show / Update / Destroy ─────────────────────────────────────────

    #[Test]
    public function show_calls_manager_with_int_pk(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/42' => Http::response(['chargeBoxPk' => 42, 'chargeBoxId' => 'CB-42'], 200),
        ]);

        $this->authed('GET', '/api/v1/steve/charge-points/42')
            ->assertStatus(200)
            ->assertJsonPath('data.chargeBoxId', 'CB-42');

        Http::assertSent(fn ($req) => $req->method() === 'GET' && str_ends_with($req->url(), '/chargePoints/42'));
    }

    #[Test]
    public function update_sends_put_with_body(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/7' => Http::response(['chargeBoxPk' => 7, 'description' => 'updated'], 200),
        ]);

        $this->authed('PUT', '/api/v1/steve/charge-points/7', ['description' => 'updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.description', 'updated');

        Http::assertSent(fn ($req) => $req->method() === 'PUT'
            && str_ends_with($req->url(), '/chargePoints/7')
            && $req->data() === ['description' => 'updated']);
    }

    #[Test]
    public function destroy_sends_delete(): void
    {
        Http::fake(['*manager/api/v1/chargePoints/9' => Http::response('', 200)]);

        $this->authed('DELETE', '/api/v1/steve/charge-points/9')
            ->assertStatus(200);

        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_ends_with($req->url(), '/chargePoints/9'));
    }

    // ─── Upstream failure → 502 ──────────────────────────────────────────

    #[Test]
    public function index_returns_502_when_steve_unreachable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connect failed');
        });

        $this->authed('GET', '/api/v1/steve/charge-points')->assertStatus(502);
    }

    #[Test]
    public function show_returns_503_when_steve_unconfigured(): void
    {
        config()->set('steve.api_url', '');

        $this->authed('GET', '/api/v1/steve/charge-points/42')->assertStatus(503);
    }
}
