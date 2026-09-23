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
 * Contract tests for the two PK-scoped read endpoints:
 *   GET /api/v1/steve/charge-points/{pk}/status
 *   GET /api/v1/steve/charge-points/{pk}/transactions
 *
 * Both resolve the PK to chargeBoxId via /chargePoints/{pk}, then route the
 * actual data fetch through /connectors/status or /transactions respectively.
 */
class ChargePointNestedReadsTest extends TestCase
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

    protected function authed(string $method, string $url, array $body = [])
    {
        Sanctum::actingAs($this->admin);
        return $this->json($method, $url, $body);
    }

    // ─── /charge-points/{pk}/status ──────────────────────────────────────

    #[Test]
    public function status_requires_authentication(): void
    {
        $this->json('GET', '/api/v1/steve/charge-points/1/status')->assertStatus(401);
    }

    #[Test]
    public function status_returns_composite_online_and_connector_list(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/7' => Http::response([
                'chargeBoxPk' => 7,
                'chargeBoxId' => 'CB-LIVE',
            ], 200),
            '*manager/api/v1/connectors/status*' => Http::response([
                'online'     => true,
                'connectors' => [
                    ['connectorId' => 1, 'status' => 'Available'],
                    ['connectorId' => 2, 'status' => 'Charging'],
                    ['connectorId' => 3, 'status' => 'Faulted'],
                ],
            ], 200),
        ]);

        $response = $this->authed('GET', '/api/v1/steve/charge-points/7/status');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.chargeBoxId', 'CB-LIVE');
        $response->assertJsonPath('data.online', true);
        $response->assertJsonPath('meta.count', 3);
        $response->assertJsonPath('meta.available', 1);
        $response->assertJsonPath('data.connectors.0.status', 'Available');
    }

    #[Test]
    public function status_surfaces_upstream_404_as_404(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints*' => Http::response('not found', 404),
        ]);

        $this->authed('GET', '/api/v1/steve/charge-points/99/status')->assertStatus(502);
    }

    // ─── /charge-points/{pk}/transactions ────────────────────────────────

    #[Test]
    public function transactions_requires_authentication(): void
    {
        $this->json('GET', '/api/v1/steve/charge-points/1/transactions')->assertStatus(401);
    }

    #[Test]
    public function transactions_passes_filters_through_to_manager_api(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/5' => Http::response([
                'chargeBoxPk' => 5,
                'chargeBoxId' => 'CB-TX',
            ], 200),
            '*manager/api/v1/transactions*' => Http::response([
                ['transactionPk' => 1, 'ocppIdTag' => 'TAG-A'],
                ['transactionPk' => 2, 'ocppIdTag' => 'TAG-B'],
            ], 200),
        ]);

        $response = $this->authed('GET', '/api/v1/steve/charge-points/5/transactions?type=ACTIVE&periodType=TODAY');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.ocppIdTag', 'TAG-A');
        $response->assertJsonPath('meta.count', 2);
        $response->assertJsonPath('meta.chargeBoxId', 'CB-TX');

        Http::assertSent(function ($req) {
            if (!str_contains($req->url(), '/manager/api/v1/transactions')) {
                return false;
            }
            return str_contains($req->url(), 'chargeBoxId=CB-TX')
                && str_contains($req->url(), 'type=ACTIVE')
                && str_contains($req->url(), 'periodType=TODAY');
        });
    }

    #[Test]
    public function transactions_rejects_unknown_period_with_422(): void
    {
        $this->authed('GET', '/api/v1/steve/charge-points/5/transactions?periodType=NEXT_YEAR')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['periodType']);
    }
}
