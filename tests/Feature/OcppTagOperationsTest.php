<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Services\OcppTagRemoteOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class OcppTagOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $chargingPoint;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur admin
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        // Créer un point de charge
        $this->chargingPoint = ChargingPoint::factory()->create([
            'name' => 'Test Charging Point',
            'charge_box_id' => 'TEST_BORNE_001',
            'status' => 'online'
        ]);
    }

    /** @test */
    public function it_can_access_ocpp_operations_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('charging-points.ocpp-tag.index', $this->chargingPoint));

        $response->assertStatus(200);
        $response->assertViewIs('charging-points.ocpp-operations');
        $response->assertViewHas('chargingPoint');
        $response->assertViewHas('defaultTag');
    }

    /** @test */
    public function it_validates_tag_before_starting_transaction()
    {
        Http::fake([
            '*/api/v1/ocppTags*' => Http::response([
                [
                    'idTag' => 'Open10Tag',
                    'blocked' => false,
                    'inTransaction' => false,
                    'maxActiveTransactionCount' => 10,
                    'activeTransactionCount' => 0,
                    'ocppTagPk' => 1
                ]
            ], 200),
            '*/api/v1/commands/remoteStartTransaction' => Http::response([
                'status' => 'Accepted',
                'transactionId' => 12345
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('charging-points.ocpp-tag.start', $this->chargingPoint), [
                'id_tag' => 'Open10Tag',
                'connector_id' => 1
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
    }

    /** @test */
    public function it_rejects_blocked_tag()
    {
        Http::fake([
            '*/api/v1/ocppTags*' => Http::response([
                [
                    'idTag' => 'BlockedTag',
                    'blocked' => true,
                    'ocppTagPk' => 2
                ]
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('charging-points.ocpp-tag.start', $this->chargingPoint), [
                'id_tag' => 'BlockedTag',
                'connector_id' => 1
            ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false
        ]);
    }

    /** @test */
    public function it_can_stop_transaction()
    {
        Http::fake([
            '*/api/v1/commands/remoteStopTransaction' => Http::response([
                'status' => 'Accepted'
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('charging-points.ocpp-tag.stop', $this->chargingPoint), [
                'transaction_id' => 12345
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->get(route('charging-points.ocpp-tag.index', $this->chargingPoint));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_can_list_ocpp_tags()
    {
        Http::fake([
            '*/api/v1/ocppTags*' => Http::response([
                [
                    'idTag' => 'Tag1',
                    'ocppTagPk' => 1,
                    'blocked' => false
                ],
                [
                    'idTag' => 'Tag2',
                    'ocppTagPk' => 2,
                    'blocked' => false
                ]
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ocpp-tags.index'));

        $response->assertStatus(200);
        $response->assertViewIs('ocpp-tags.index');
        $response->assertViewHas('tags');
    }

    /** @test */
    public function admin_can_create_tag()
    {
        Http::fake([
            '*/api/v1/ocppTags' => Http::response([
                'idTag' => 'NewTag',
                'ocppTagPk' => 3,
                'blocked' => false,
                'maxActiveTransactionCount' => -1
            ], 201)
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('ocpp-tags.store'), [
                'id_tag' => 'NewTag',
                'max_active_transaction_count' => -1,
                'note' => 'Test tag'
            ]);

        $response->assertRedirect(route('ocpp-tags.index'));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function it_can_get_active_transactions()
    {
        Http::fake([
            '*/api/v1/transactions*' => Http::response([
                [
                    'id' => 12345,
                    'chargeBoxId' => 'TEST_BORNE_001',
                    'ocppIdTag' => 'Open10Tag',
                    'startTimestamp' => '2025-12-08T10:00:00.000Z'
                ]
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('charging-points.ocpp-tag.active-transactions', $this->chargingPoint));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
    }

    /** @test */
    public function it_can_get_transaction_statistics()
    {
        Http::fake([
            '*/api/v1/transactions*' => Http::response([
                [
                    'id' => 1,
                    'startValue' => '1000',
                    'stopValue' => '15000',
                    'startTimestamp' => '2025-12-08T10:00:00.000Z',
                    'stopTimestamp' => '2025-12-08T12:00:00.000Z'
                ]
            ], 200)
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.steve-transactions.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'statistics' => [
                'total_count',
                'active_count',
                'stopped_count',
                'total_energy_kwh'
            ]
        ]);
    }
}

