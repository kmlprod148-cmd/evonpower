<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps each request with a correlation id (X-Request-Id), injects it into
 * every Log entry made during the request via Log::withContext, and echoes
 * the same id back on the response so clients can quote it in bug reports.
 *
 * P6.3: enables grep-friendly trace through controller → service →
 * SteVeHttpClient logs without per-call boilerplate.
 *
 * If the client supplies a `X-Request-Id` header (UUID), we honour it so
 * upstream proxies can join their traces with ours; otherwise we generate
 * a UUIDv4. Length-capped to 64 chars to defend against header-injection
 * abuse from untrusted clients.
 */
class InjectRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->headers->get(self::HEADER, '');
        $requestId = $this->normalise($incoming) ?? (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['requestId' => $requestId]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    /**
     * Accept only printable ASCII without whitespace or control chars,
     * up to 64 characters. Anything else is discarded and replaced with
     * a freshly generated UUID.
     */
    protected function normalise(string $candidate): ?string
    {
        $candidate = trim($candidate);
        if ($candidate === '' || strlen($candidate) > 64) {
            return null;
        }

        return preg_match('/^[A-Za-z0-9._:\-]+$/', $candidate) ? $candidate : null;
    }
}
