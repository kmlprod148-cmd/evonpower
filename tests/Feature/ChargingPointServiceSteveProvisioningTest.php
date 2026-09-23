<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Integrator;
use App\Models\Partner;
use App\Services\ChargingPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChargingPointServiceSteveProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_managed_provisioning_sends_only_one_steve_create_request(): void
    {
        config([
            'steve.api_url' => 'http://steve.test/steve/api/v1',
            'steve.username' => 'admin',
            'steve.password' => 'secret',
            'steve.retry_attempts' => 1,
            'services.steve.url' => 'http://legacy-steve.test/steve/api/v1',
            'services.steve.user' => 'legacy-admin',
            'services.steve.pass' => 'legacy-secret',
        ]);

        Http::fake([
            '*chargePoints?chargeBoxId=VIABOX' => Http::response([], 200),
            '*chargePoints' => Http::response([
                'chargeBoxPk' => 123,
                'chargeBoxId' => 'VIABOX',
            ], 201),
        ]);

        $integrator = Integrator::factory()->create();
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        $group = Group::factory()->create([
            'integrator_id' => $integrator->id,
            'partner_id' => $partner->id,
        ]);

        $chargingPoint = app(ChargingPointService::class)->createChargingPoint([
            'name' => 'VIABOX',
            'serial_number' => 'VIABOX',
            'manufacturer' => 'KMLDEV',
            'model' => 'VIABOX',
            'status' => 'online',
            'group_id' => $group->id,
            'address' => 'Casablanca, Maroc',
            'latitude' => '33.528783',
            'longitude' => '-7.657415',
        ], true);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), 'chargeBoxId=VIABOX'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://steve.test/steve/api/v1/chargePoints'
            && $request['chargeBoxId'] === 'VIABOX');

        $this->assertSame(123, $chargingPoint->fresh()->steve_charge_box_pk);
    }

    /**
     * Escape-hatch contract (STEVE_AUTO_PROVISION_ON_CREATE=false):
     *  - no HTTP call is made to SteVe,
     *  - the local row is persisted,
     *  - steve_provisioned_at stays null,
     *  - steve_connection_status carries the awaiting_sync marker so the admin
     *    UI / sync-steve route can pick the row up for manual provisioning.
     *
     * The chargeBoxId must still be seeded locally — either from serial_number
     * or from the CP-XXXXXX fallback — so a later sync has a stable identifier.
     */
    public function test_createChargingPoint_with_register_false_persists_row_and_marks_awaiting_sync(): void
    {
        Http::fake([
            '*' => Http::response(['unexpected' => true], 500),
        ]);

        $integrator = Integrator::factory()->create();
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        $group = Group::factory()->create([
            'integrator_id' => $integrator->id,
            'partner_id' => $partner->id,
        ]);

        $chargingPoint = app(ChargingPointService::class)->createChargingPoint([
            'name' => 'DEFERRED-BOX',
            'serial_number' => 'DEFERRED-BOX',
            'manufacturer' => 'KMLDEV',
            'model' => 'VIABOX',
            'status' => 'online',
            'group_id' => $group->id,
            'address' => 'Casablanca, Maroc',
            'latitude' => '33.528783',
            'longitude' => '-7.657415',
        ], false);

        Http::assertNothingSent();

        $fresh = $chargingPoint->fresh();

        $this->assertNotNull($fresh, 'Local row must be persisted when SteVe is skipped.');
        $this->assertDatabaseHas('charging_points', [
            'id' => $fresh->id,
            'serial_number' => 'DEFERRED-BOX',
        ]);

        $this->assertNull($fresh->steve_provisioned_at, 'steve_provisioned_at must stay null in deferred mode.');
        $this->assertNull($fresh->steve_charge_box_pk, 'No chargeBoxPk should be assigned without a SteVe call.');
        $this->assertSame('DEFERRED-BOX', $fresh->steve_charging_point_id, 'chargeBoxId must be seeded from serial_number for later sync.');

        $status = (array) $fresh->steve_connection_status;
        $this->assertTrue($status['awaiting_sync'] ?? false, 'steve_connection_status.awaiting_sync must be true.');
        $this->assertSame('auto_provision_disabled', $status['skipped_reason'] ?? null);
        $this->assertNotEmpty($status['marked_at'] ?? null, 'marked_at timestamp must be recorded.');
    }

    public function test_service_managed_provisioning_adopts_existing_steve_charge_point_without_posting(): void
    {
        config([
            'steve.api_url' => 'http://steve.test/steve/api/v1',
            'steve.username' => 'admin',
            'steve.password' => 'secret',
            'steve.retry_attempts' => 1,
        ]);

        Http::fake([
            '*chargePoints?chargeBoxId=VIABOX' => Http::response([
                ['chargeBoxPk' => 456, 'chargeBoxId' => 'VIABOX'],
            ], 200),
            '*chargePoints' => Http::response(['unexpected' => true], 500),
        ]);

        $integrator = Integrator::factory()->create();
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        $group = Group::factory()->create([
            'integrator_id' => $integrator->id,
            'partner_id' => $partner->id,
        ]);

        $chargingPoint = app(ChargingPointService::class)->createChargingPoint([
            'name' => 'VIABOX',
            'serial_number' => 'VIABOX',
            'manufacturer' => 'KMLDEV',
            'model' => 'VIABOX',
            'status' => 'online',
            'group_id' => $group->id,
            'address' => 'Casablanca, Maroc',
            'latitude' => '33.528783',
            'longitude' => '-7.657415',
        ], true);

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');

        $fresh = $chargingPoint->fresh();
        $this->assertSame(456, $fresh->steve_charge_box_pk);
        $this->assertSame('VIABOX', $fresh->charge_box_id);
        $this->assertTrue((bool) (($fresh->steve_connection_status ?? [])['adopted'] ?? false));
    }

    public function test_service_managed_provisioning_adopts_existing_steve_charge_point_after_duplicate_failure(): void
    {
        config([
            'steve.api_url' => 'http://steve.test/steve/api/v1',
            'steve.username' => 'admin',
            'steve.password' => 'secret',
            'steve.retry_attempts' => 1,
        ]);

        $lookupCount = 0;

        Http::fake(function ($request) use (&$lookupCount) {
            if ($request->method() === 'GET' && str_contains($request->url(), 'chargeBoxId=VIABOX')) {
                $lookupCount++;

                return $lookupCount === 1
                    ? Http::response([], 200)
                    : Http::response([
                        ['chargeBoxPk' => 789, 'chargeBoxId' => 'VIABOX'],
                    ], 200);
            }

            if ($request->method() === 'POST') {
                return Http::response([
                    'message' => "Failed to add the charge point with chargeBoxId 'VIABOX'",
                ], 500);
            }

            return Http::response([], 404);
        });

        $integrator = Integrator::factory()->create();
        $partner = Partner::factory()->create(['integrator_id' => $integrator->id]);
        $group = Group::factory()->create([
            'integrator_id' => $integrator->id,
            'partner_id' => $partner->id,
        ]);

        $chargingPoint = app(ChargingPointService::class)->createChargingPoint([
            'name' => 'VIABOX',
            'serial_number' => 'VIABOX',
            'manufacturer' => 'KMLDEV',
            'model' => 'VIABOX',
            'status' => 'online',
            'group_id' => $group->id,
            'address' => 'Casablanca, Maroc',
            'latitude' => '33.528783',
            'longitude' => '-7.657415',
        ], true);

        Http::assertSentCount(3);
        $this->assertSame(2, $lookupCount);

        $fresh = $chargingPoint->fresh();
        $this->assertSame(789, $fresh->steve_charge_box_pk);
        $this->assertSame('VIABOX', $fresh->charge_box_id);
        $this->assertTrue((bool) (($fresh->steve_connection_status ?? [])['adopted'] ?? false));
    }
}
