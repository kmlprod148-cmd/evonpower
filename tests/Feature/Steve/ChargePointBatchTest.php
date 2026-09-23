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
 * Contract tests for the parallel batch endpoints on /api/v1/steve/charge-points.
 *
 * Each test asserts:
 *  - the BFF speaks the right wire shape outbound (per-PK HTTP calls
 *    via Http::pool, not a single bulk call)
 *  - the response surfaces per-PK results in the same envelope shape used
 *    by single-item endpoints (success + meta.failures)
 *  - partial failure modes (404, 500, mixed) are reported, not swallowed
 */
class ChargePointBatchTest extends TestCase
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

    // ─── batch/show ──────────────────────────────────────────────────────

    #[Test]
    public function batch_show_requires_authentication(): void
    {
        $this->json('POST', '/api/v1/steve/charge-points/batch/show', [
            'chargePointPks' => [1, 2],
        ])->assertStatus(401);
    }

    #[Test]
    public function batch_show_rejects_empty_payload_with_422(): void
    {
        $this->authed('POST', '/api/v1/steve/charge-points/batch/show', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['chargePointPks']);
    }

    #[Test]
    public function batch_show_rejects_over_max_items_with_422(): void
    {
        $this->authed('POST', '/api/v1/steve/charge-points/batch/show', [
            'chargePointPks' => range(1, 51),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['chargePointPks']);
    }

    #[Test]
    public function batch_show_fans_out_concurrent_requests_to_steve(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/1' => Http::response(['chargeBoxPk' => 1, 'chargeBoxId' => 'CB-1'], 200),
            '*manager/api/v1/chargePoints/2' => Http::response(['chargeBoxPk' => 2, 'chargeBoxId' => 'CB-2'], 200),
            '*manager/api/v1/chargePoints/3' => Http::response(['chargeBoxPk' => 3, 'chargeBoxId' => 'CB-3'], 200),
        ]);

        $response = $this->authed('POST', '/api/v1/steve/charge-points/batch/show', [
            'chargePointPks' => [1, 2, 3],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('meta.count', 3);
        $response->assertJsonPath('meta.failures', 0);
        $response->assertJsonPath('data.items.1.chargeBoxId', 'CB-1');
        $response->assertJsonPath('data.items.2.chargeBoxId', 'CB-2');
        $response->assertJsonPath('data.items.3.chargeBoxId', 'CB-3');

        $hit = ['1' => 0, '2' => 0, '3' => 0];
        Http::assertSent(function ($req) use (&$hit) {
            if (preg_match('#/chargePoints/(\d+)$#', $req->url(), $m)) {
                $hit[$m[1]] = ($hit[$m[1]] ?? 0) + 1;
            }
            return true;
        });
        $this->assertSame(['1' => 1, '2' => 1, '3' => 1], $hit, 'Each PK should have been fetched exactly once');
    }

    #[Test]
    public function batch_show_reports_partial_failures_in_envelope(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/1' => Http::response(['chargeBoxPk' => 1, 'chargeBoxId' => 'CB-1'], 200),
            '*manager/api/v1/chargePoints/2' => Http::response('not found', 404),
            '*manager/api/v1/chargePoints/3' => Http::response('boom', 500),
        ]);

        $response = $this->authed('POST', '/api/v1/steve/charge-points/batch/show', [
            'chargePointPks' => [1, 2, 3],
        ]);

        $response->assertStatus(200); // 207-style partial success rolls up to one 200 envelope.
        $response->assertJsonPath('meta.count', 1);
        $response->assertJsonPath('meta.failures', 2);
        $response->assertJsonPath('data.items.1.chargeBoxId', 'CB-1');
        $response->assertJsonPath('data.errors.2.error', 'chargePoint_not_found');
        $response->assertJsonPath('data.errors.3.error', 'upstream_error');
    }

    // ─── batch update ────────────────────────────────────────────────────

    #[Test]
    public function batch_update_rejects_missing_items_with_422(): void
    {
        $this->authed('PUT', '/api/v1/steve/charge-points/batch', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    #[Test]
    public function batch_update_fans_out_put_per_pk(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/5' => Http::response(['chargeBoxPk' => 5, 'description' => 'A'], 200),
            '*manager/api/v1/chargePoints/6' => Http::response(['chargeBoxPk' => 6, 'description' => 'B'], 200),
        ]);

        $response = $this->authed('PUT', '/api/v1/steve/charge-points/batch', [
            'items' => [
                ['chargePointPk' => 5, 'data' => ['description' => 'A']],
                ['chargePointPk' => 6, 'data' => ['description' => 'B']],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('meta.count', 2);
        $response->assertJsonPath('meta.failures', 0);

        $methods = [];
        Http::assertSent(function ($req) use (&$methods) {
            if (preg_match('#/chargePoints/(\d+)$#', $req->url(), $m)) {
                $methods[$m[1]] = $req->method();
            }
            return true;
        });
        $this->assertSame(['5' => 'PUT', '6' => 'PUT'], $methods);
    }

    // ─── batch delete ────────────────────────────────────────────────────

    #[Test]
    public function batch_delete_fans_out_concurrent_deletes(): void
    {
        Http::fake([
            '*manager/api/v1/chargePoints/9'  => Http::response('', 200),
            '*manager/api/v1/chargePoints/10' => Http::response('', 200),
        ]);

        $response = $this->authed('POST', '/api/v1/steve/charge-points/batch/delete', [
            'chargePointPks' => [9, 10],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('meta.count', 2);
    }

    // ─── 503 propagation ─────────────────────────────────────────────────

    #[Test]
    public function batch_show_returns_503_when_steve_unconfigured(): void
    {
        config()->set('steve.api_url', '');

        $this->authed('POST', '/api/v1/steve/charge-points/batch/show', [
            'chargePointPks' => [1, 2],
        ])->assertStatus(503);
    }
}
