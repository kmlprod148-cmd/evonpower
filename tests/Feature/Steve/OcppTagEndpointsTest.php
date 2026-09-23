<?php

declare(strict_types=1);

namespace Tests\Feature\Steve;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OcppTagEndpointsTest extends TestCase
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

    // ─── Auth ────────────────────────────────────────────────────────────

    #[Test]
    public function index_requires_authentication(): void
    {
        $this->json('GET', '/api/v1/steve/ocpp-tags')->assertStatus(401);
    }

    // ─── Index + filters ─────────────────────────────────────────────────

    #[Test]
    public function index_passes_through_documented_filters(): void
    {
        Http::fake([
            '*manager/api/v1/ocppTags*' => Http::response([
                ['ocppTagPk' => 1, 'idTag' => 'RFID-1', 'blocked' => false],
            ], 200),
        ]);

        $response = $this->authed('GET', '/api/v1/steve/ocpp-tags?expired=FALSE&blocked=FALSE&userFilter=OnlyTagsWithUser');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.idTag', 'RFID-1')
            ->assertJsonPath('meta.count', 1);

        Http::assertSent(function ($req) {
            $url = $req->url();
            return $req->method() === 'GET'
                && str_contains($url, '/manager/api/v1/ocppTags')
                && str_contains($url, 'expired=FALSE')
                && str_contains($url, 'blocked=FALSE')
                && str_contains($url, 'userFilter=OnlyTagsWithUser');
        });
    }

    #[Test]
    public function index_rejects_invalid_tristate(): void
    {
        $this->authed('GET', '/api/v1/steve/ocpp-tags?expired=MAYBE')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expired']);
    }

    // ─── CRUD ────────────────────────────────────────────────────────────

    #[Test]
    public function store_requires_id_tag(): void
    {
        $this->authed('POST', '/api/v1/steve/ocpp-tags', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['idTag']);
    }

    #[Test]
    public function store_creates_tag_and_returns_201(): void
    {
        Http::fake([
            '*manager/api/v1/ocppTags' => Http::response([
                'ocppTagPk' => 7, 'idTag' => 'RFID-NEW', 'blocked' => false,
            ], 201),
        ]);

        $this->authed('POST', '/api/v1/steve/ocpp-tags', [
            'idTag' => 'RFID-NEW', 'maxActiveTransactionCount' => 1,
        ])->assertStatus(201)
          ->assertJsonPath('data.ocppTagPk', 7);

        Http::assertSent(fn ($req) => $req->method() === 'POST'
            && str_contains($req->url(), '/manager/api/v1/ocppTags')
            && $req->data() === ['idTag' => 'RFID-NEW', 'maxActiveTransactionCount' => 1]);
    }

    #[Test]
    public function show_calls_manager_with_int_pk(): void
    {
        Http::fake([
            '*manager/api/v1/ocppTags/5' => Http::response(['ocppTagPk' => 5, 'idTag' => 'RFID-5'], 200),
        ]);

        $this->authed('GET', '/api/v1/steve/ocpp-tags/5')
            ->assertStatus(200)
            ->assertJsonPath('data.idTag', 'RFID-5');

        Http::assertSent(fn ($req) => $req->method() === 'GET' && str_ends_with($req->url(), '/ocppTags/5'));
    }

    #[Test]
    public function destroy_sends_delete_and_passes_through_returned_tag(): void
    {
        Http::fake([
            '*manager/api/v1/ocppTags/9' => Http::response(['ocppTagPk' => 9, 'idTag' => 'RFID-9'], 200),
        ]);

        $this->authed('DELETE', '/api/v1/steve/ocpp-tags/9')
            ->assertStatus(200)
            ->assertJsonPath('data.idTag', 'RFID-9');

        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_ends_with($req->url(), '/ocppTags/9'));
    }

    // ─── Upstream failure → 502 ──────────────────────────────────────────

    #[Test]
    public function index_returns_502_when_upstream_fails(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('connect failed');
        });

        $this->authed('GET', '/api/v1/steve/ocpp-tags')->assertStatus(502);
    }
}
