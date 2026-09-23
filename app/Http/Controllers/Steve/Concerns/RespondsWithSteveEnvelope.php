<?php

declare(strict_types=1);

namespace App\Http\Controllers\Steve\Concerns;

use App\Http\Responses\ApiEnvelope;
use Illuminate\Http\JsonResponse;

/**
 * Shared envelope helpers for the SteVe manager-API BFF controllers.
 *
 * Keeps the wire shape (P3 envelope) consistent with OcppOperationsController:
 *   { success, message, data, error, errors, meta, timestamp }
 *
 * Status-code policy:
 *   200 — success
 *   404 — resource missing in SteVe (resolved via _not_found service error)
 *   422 — request validation failure (FormRequest handles it)
 *   502 — upstream SteVe error (HTTP 4xx/5xx that wasn't a known resource miss)
 *   503 — SteVe configuration missing (bubbles via SteVeConfigurationException)
 */
trait RespondsWithSteveEnvelope
{
    /**
     * Translate a SteVeHttpClientService result envelope to a JsonResponse.
     *
     * SteVeHttpClientService::executeRequest() returns:
     *   - success: ['success' => true,  'data' => ..., 'message' => ...]
     *   - failure: ['success' => false, 'error' => ..., 'message' => ...]
     *
     * On failure with a known *_not_found code we return 404; everything else
     * surfaces as 502 (upstream issue) so Bug #6's invariant — never return
     * 200 + success:false — holds for these pass-through endpoints too.
     */
    protected function respondFromServiceResult(array $result, ?string $okMessage = null, ?array $meta = null): JsonResponse
    {
        if (($result['success'] ?? false) === true) {
            $data = $result['data'] ?? null;
            $message = $okMessage ?? ($result['message'] ?? 'OK');

            return response()->json(ApiEnvelope::ok($message, $data, $meta), 200);
        }

        $errorCode = $result['error'] ?? 'upstream_error';
        $message   = $result['message'] ?? 'Erreur upstream SteVe';
        $status    = str_ends_with((string) $errorCode, '_not_found') ? 404 : 502;

        return response()->json(
            ApiEnvelope::error($message, $errorCode, $result['data'] ?? null, $meta),
            $status,
        );
    }

    protected function unauthorizedResponse(string $message = 'Non autorisé'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($message, 'unauthorized'), 403);
    }

    protected function serverErrorResponse(string $message, string $code = 'internal_error'): JsonResponse
    {
        return response()->json(ApiEnvelope::error($message, $code), 500);
    }
}
