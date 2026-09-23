<?php

namespace Tests\Feature\Ocpp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * P6.3 — InjectRequestId middleware behaviour.
 *
 * Hits /api/health (a public no-auth endpoint already in routes/api.php) so the
 * test doesn't need a Sanctum token.
 */
class InjectRequestIdMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function response_carries_an_x_request_id_header(): void
    {
        $response = $this->getJson('/api/health');

        $this->assertNotEmpty($response->headers->get('X-Request-Id'));
    }

    #[Test]
    public function generated_id_is_a_uuid(): void
    {
        $id = $this->getJson('/api/health')->headers->get('X-Request-Id');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            (string) $id
        );
    }

    #[Test]
    public function client_supplied_request_id_is_honoured_when_safe(): void
    {
        $supplied = 'trace-abc.123_XYZ:99';

        $response = $this->withHeaders(['X-Request-Id' => $supplied])->getJson('/api/health');

        $this->assertSame($supplied, $response->headers->get('X-Request-Id'));
    }

    #[Test]
    public function client_supplied_request_id_with_unsafe_chars_is_replaced(): void
    {
        // Spaces, newlines, semicolons, etc. would let attackers smuggle log entries
        // or split headers. Middleware must reject them and synthesise a UUID.
        foreach (["bad value with spaces", "line1\nline2", "x; y", str_repeat('a', 65)] as $bad) {
            $response = $this->withHeaders(['X-Request-Id' => $bad])->getJson('/api/health');
            $echoed = (string) $response->headers->get('X-Request-Id');

            $this->assertNotSame(
                $bad,
                $echoed,
                'Middleware accepted unsafe X-Request-Id ' . json_encode($bad)
            );
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $echoed
            );
        }
    }
}
