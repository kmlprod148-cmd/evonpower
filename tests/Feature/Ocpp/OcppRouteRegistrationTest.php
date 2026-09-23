<?php

namespace Tests\Feature\Ocpp;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Locks the canonical /api/v1/ocpp/* surface and guards against the double-prefix
 * regression that produced /api/api/steve/... (Bug #7 from the live-test audit).
 */
class OcppRouteRegistrationTest extends TestCase
{
    private function uris(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => $r->uri())
            ->all();
    }

    #[Test]
    public function canonical_remote_start_route_is_registered(): void
    {
        $this->assertContains(
            'api/v1/ocpp/charging-points/{chargingPointId}/remote-start',
            $this->uris()
        );
    }

    #[Test]
    public function canonical_remote_stop_route_is_registered(): void
    {
        $this->assertContains(
            'api/v1/ocpp/charging-points/{chargingPointId}/remote-stop',
            $this->uris()
        );
    }

    #[Test]
    public function canonical_reset_route_is_registered(): void
    {
        $this->assertContains(
            'api/v1/ocpp/charging-points/{chargingPointId}/reset',
            $this->uris()
        );
    }

    #[Test]
    public function canonical_unlock_route_is_registered(): void
    {
        $this->assertContains(
            'api/v1/ocpp/charging-points/{chargingPointId}/connectors/{connectorId}/unlock',
            $this->uris()
        );
    }

    #[Test]
    public function misleading_lock_connector_route_is_not_registered(): void
    {
        // Bug #12: there is no OCPP 1.6 LockConnector command; the previous
        // endpoint sent ChangeAvailability(Inoperative) which is NOT a physical
        // cable lock. Operators relying on the name could be mis-led. The route
        // must remain absent; use /availability or /disable instead.
        $offending = array_values(array_filter(
            $this->uris(),
            fn (string $uri) => str_ends_with($uri, '/connectors/{connectorId}/lock')
        ));

        $this->assertSame([], $offending, 'Bug #12: /lock must stay removed. Use /availability or /disable.');
    }

    #[Test]
    public function double_prefix_api_api_steve_routes_are_not_registered(): void
    {
        $offending = array_values(array_filter(
            $this->uris(),
            fn (string $uri) => str_starts_with($uri, 'api/api/')
        ));

        $this->assertSame([], $offending, "Routes under 'api/api/' must be eliminated (Bug #7): \n" . implode("\n", $offending));
    }
}
