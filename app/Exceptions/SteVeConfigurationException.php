<?php

namespace App\Exceptions;

use App\Http\Responses\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown when the application tries to talk to SteVe but its connection
 * details are missing or obviously wrong (empty STEVE_API_URL, etc.).
 *
 * Bug #1.4: previously these surfaced as cryptic "cURL error 6: Could not
 * resolve host: api" responses with HTTP 200 + success:false. The render()
 * hook below makes Laravel emit a clean 503 in the canonical P3 envelope —
 * `meta.code = 'steve_configuration_missing'` for machine matching.
 */
class SteVeConfigurationException extends RuntimeException
{
    public function render(Request $request): ?JsonResponse
    {
        if (!$request->expectsJson() && !$request->is('api/*')) {
            return null;
        }

        return response()->json(
            ApiEnvelope::error(
                $this->getMessage(),
                'steve_configuration_missing',
                null,
                ['code' => 'steve_configuration_missing']
            ),
            503
        );
    }
}
