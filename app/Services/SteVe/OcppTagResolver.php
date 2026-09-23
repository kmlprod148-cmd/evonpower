<?php

declare(strict_types=1);

namespace App\Services\SteVe;

use App\Services\SteVeHttpClientService;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the idTag to use when a RemoteStartTransaction is issued without
 * an explicit one in the request body.
 *
 * Resolution order:
 *   1. Caller-supplied tag (passed straight through if non-empty).
 *   2. STEVE_DEFAULT_ID_TAG (config('steve.default_id_tag')) — if SteVe's
 *      /ocppTags list confirms it is not blocked/expired/in-transaction.
 *   3. The first usable tag returned by /ocppTags filtered to
 *      expired=FALSE, blocked=FALSE, inTransaction=FALSE.
 *   4. null — caller must surface a 422.
 *
 * The /ocppTags lookup is the SteVe REST 3.9.0 surface that ChargePoint
 * controllers already speak; we intentionally do NOT fall back to a hard-coded
 * tag value if SteVe is unreachable, because dispatching a RemoteStart with an
 * unknown idTag would just be rejected by the charger and waste an OCPP round
 * trip.
 */
final class OcppTagResolver
{
    public function __construct(
        private readonly SteVeHttpClientService $steve,
    ) {
    }

    /**
     * Return a usable idTag for RemoteStart, or null if none is available.
     */
    public function resolve(?string $explicit = null): ?string
    {
        $explicit = is_string($explicit) ? trim($explicit) : '';
        if ($explicit !== '') {
            return $explicit;
        }

        $usable = $this->fetchUsableTags();
        if ($usable === null) {
            // Upstream call failed — propagate null so the controller emits 502
            // or a configuration error. Falling back to a stale default would
            // produce a misleading "tag not authorized" charger response.
            return null;
        }

        $configured = trim((string) config('steve.default_id_tag', ''));
        if ($configured !== '' && $this->tagIsUsable($configured, $usable)) {
            return $configured;
        }

        $first = $usable[0] ?? null;
        return is_array($first) ? ($first['idTag'] ?? null) : null;
    }

    /**
     * @return array<int, array<string, mixed>>|null  null = upstream failure,
     *                                                empty array = no usable tags
     */
    private function fetchUsableTags(): ?array
    {
        $result = $this->steve->listOcppTags([
            'expired'       => 'FALSE',
            'blocked'       => 'FALSE',
            'inTransaction' => 'FALSE',
        ]);

        if (($result['success'] ?? false) !== true) {
            Log::warning('OcppTagResolver: listOcppTags failed', [
                'error'   => $result['error']   ?? null,
                'message' => $result['message'] ?? null,
            ]);
            return null;
        }

        $rows = $result['data'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            $rows,
            static fn ($row): bool => is_array($row)
                && isset($row['idTag'])
                && is_string($row['idTag'])
                && $row['idTag'] !== '',
        ));
    }

    private function tagIsUsable(string $idTag, array $usable): bool
    {
        foreach ($usable as $row) {
            if (is_array($row) && ($row['idTag'] ?? null) === $idTag) {
                return true;
            }
        }
        return false;
    }
}
