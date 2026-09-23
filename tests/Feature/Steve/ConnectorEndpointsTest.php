<?php

declare(strict_types=1);

namespace Tests\Feature\Steve;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectorEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();

        config()->set('steve.api_url', 'http://steve.test/steve');
        config()->set('steve.username', 'admin');
        config()->set('steve.password', 'pw');
    }

    protected function authed(string $method, string $url)
    {
        Sanctum::actingAs($this->admin);
        return $this->json($method, $url);
    }

    // ─── Auth ────────────────────────────────────────────────────────────

    #[Test]
    public function index_requires_authentication(): void
    {
        $this->json('GET', '/api/v1/steve/connectors?chargeBoxId=CB-1')->assertStatus(401);
    }

    // ─── Index ───────────────────────────────────────────────────────────

    #[Test]
    public function index_requires_charge_box_id(): void
    {
        $this->authed('GET', '/api/v1/steve/connectors')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['chargeBoxId']);
    }

    #[Test]
    public function index_passes_through_connector_list(): void
    {
        Http::fake([
            '*manager/api/v1/connectors*' => Http::response([
                ['chargeBoxId' => 'CB-1', 'connectorId' => 1, 'status' => 'Available'],
                ['chargeBoxId' => 'CB-1', 'connectorId' => 2, 'status' => 'Charging'],
            ], 200),
        ]);

        $this->authed('GET', '/api/v1/steve/connectors?chargeBoxId=CB-1')
            ->assertStatus(200)
            ->assertJsonPath('data.0.connectorId', 1)
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('meta.chargeBoxId', 'CB-1');

        Http::assertSent(function ($req) {
            return $req->method() === 'GET'
                && str_contains($req->url(), '/manager/api/v1/connectors')
                && str_contains($req->url(), 'chargeBoxId=CB-1');
        });
    }

    // ─── /all ────────────────────────────────────────────────────────────

    #[Test]
    public function all_calls_connectors_all(): void
    {
        Http::fake([
            '*manager/api/v1/connectors/all' => Http::response([
                ['chargeBoxId' => 'CB-A', 'connectorId' => 1, 'status' => 'Available'],
                ['chargeBoxId' => 'CB-B', 'connectorId' => 1, 'status' => 'Unavailable'],
            ], 200),
        ]);

        $this->authed('GET', '/api/v1/steve/connectors/all')
            ->assertStatus(200)
            ->assertJsonPath('meta.count', 2);

        Http::assertSent(fn ($req) => $req->method() === 'GET'
            && str_ends_with($req->url(), '/manager/api/v1/connectors/all'));
    }

    // ─── /status ─────────────────────────────────────────────────────────

    #[Test]
    public function status_surfaces_online_flag_in_meta(): void
    {
        Http::fake([
            '*manager/api/v1/connectors/status*' => Http::response([
                'online' => true,
                'connectors' => [
                    ['chargeBoxId' => 'CB-1', 'connectorId' => 1, 'status' => 'Available'],
                ],
            ], 200),
        ]);

        $this->authed('GET', '/api/v1/steve/connectors/status?chargeBoxId=CB-1')
            ->assertStatus(200)
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('meta.online', true)
            ->assertJsonPath('meta.chargeBoxId', 'CB-1');

        Http::assertSent(fn ($req) => str_contains($req->url(), '/manager/api/v1/connectors/status')
            && str_contains($req->url(), 'chargeBoxId=CB-1'));
    }

    #[Test]
    public function status_requires_charge_box_id(): void
    {
        $this->authed('GET', '/api/v1/steve/connectors/status')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['chargeBoxId']);
    }

    // ─── Upstream failure ────────────────────────────────────────────────

    #[Test]
    public function all_returns_502_when_upstream_fails(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connect failed');
        });

        $this->authed('GET', '/api/v1/steve/connectors/all')->assertStatus(502);
    }
}
