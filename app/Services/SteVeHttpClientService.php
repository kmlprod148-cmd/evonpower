<?php

namespace App\Services;

use App\Exceptions\SteVeConfigurationException;
use App\Support\UrlSanitizer;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Service HTTP unifié pour toutes les communications avec SteVe API
 * 
 * Ce service consolide :
 * - SteVeApiService (méthodes de base avec retry)
 * - SteVeApiEndpointService (tous les endpoints)
 * 
 * Organisé en 5 groupes principaux :
 * 1. Charge Point (ChargeBox) Management
 * 2. Connector / Port Status & Control
 * 3. Transactions & Sessions
 * 4. Configuration & Maintenance Commands
 * 5. Users / Authentication / Tag / Reservation
 * 
 * @deprecated Les anciens services SteVeApiService et SteVeApiEndpointService
 *             seront supprimés dans une future version.
 */
class SteVeHttpClientService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected int $maxRetries;
    protected int $retryDelay;
    protected string $websocketUrl;

    public function __construct()
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->maxRetries = config('steve.retry_attempts', 3);
        $this->retryDelay = config('steve.retry_delay', 1000);
        $this->websocketUrl = config('steve.websocket_url', 'ws://158.69.27.239:8180/steve/websocket/CentralSystemService/');
    }

    // ========================================================================
    // HTTP CLIENT BASE METHODS
    // ========================================================================

    /**
     * Make HTTP request with retry mechanism and exponential backoff
     */
    public function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        // Bug #1.4: fail fast with a clear diagnostic when SteVe is unconfigured.
        // Without this the underlying Guzzle call resolves the relative endpoint
        // path against an empty host and produces "cURL error 6: Could not resolve
        // host: api" — confusing for the caller and hard to grep in logs.
        if (trim($this->baseUrl) === '') {
            throw new SteVeConfigurationException(
                'SteVe base URL is not configured. Set STEVE_API_URL in .env.'
            );
        }

        $lastException = null;
        $permanentFailure = false;
        $startTime = microtime(true);

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $url = $this->buildRequestUrl($endpoint);
                
                Log::debug('SteVeHttpClient: Making request', [
                    'attempt' => $attempt,
                    'method' => $method,
                    'endpoint' => $endpoint,
                ]);
                
                $httpClient = Http::timeout($this->timeout)
                    ->withBasicAuth($this->username, $this->password)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]);

                $response = match (strtoupper($method)) {
                    'GET' => $httpClient->get($url),
                    'POST' => $data === []
                        ? $httpClient->send('POST', $url)
                        : $httpClient->post($url, $data),
                    'PUT' => $httpClient->put($url, $data),
                    'DELETE' => $httpClient->delete($url),
                    default => throw new Exception("Méthode HTTP non supportée: {$method}"),
                };

                if ($response->successful()) {
                    $duration = (microtime(true) - $startTime) * 1000;

                    Log::debug('SteVeHttpClient: Request successful', [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'duration_ms' => round($duration, 2)
                    ]);

                    // SteVe 3.9.0 OCPP command endpoints return a bare integer
                    // (the queued task ID) — not a JSON object. Wrap so this method
                    // can keep its `: array` return type.
                    $json = $response->json();
                    if ($json === null) {
                        return [];
                    }
                    if (is_array($json)) {
                        return $json;
                    }
                    return is_int($json) || ctype_digit((string) $json)
                        ? ['taskId' => (int) $json]
                        : ['result' => $json];
                }

                $statusCode = $response->status();
                $contentType = $response->header('Content-Type');
                $jsonBody = str_contains($contentType ?? '', 'application/json')
                    ? $response->json()
                    : null;
                $rawBody = (string) $response->body();

                $errorMessage = "HTTP {$statusCode}";
                if ($jsonBody !== null) {
                    $errorMessage .= ": " . json_encode($jsonBody);
                } elseif ($rawBody !== '') {
                    $errorMessage .= ': ' . substr($rawBody, 0, 1000);
                }

                if ($this->isNoActiveOcppSessionResponse($statusCode, $jsonBody, $rawBody)) {
                    Log::warning('SteVeHttpClient: OCPP command rejected because charger is not connected', [
                        'attempt' => $attempt,
                        'max_retries' => $this->maxRetries,
                        'error_code' => 'charger_not_connected',
                        'error' => UrlSanitizer::scrubMessage($errorMessage),
                    ]);
                    $lastException = new Exception($errorMessage);
                    $permanentFailure = true;
                    break;
                }

                // 4xx client errors (except 429 rate-limiting) are permanent — never retry.
                if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                    Log::warning('SteVeHttpClient: Request failed', [
                        'attempt' => $attempt,
                        'max_retries' => $this->maxRetries,
                        'error' => UrlSanitizer::scrubMessage($errorMessage),
                    ]);
                    $lastException = new Exception($errorMessage);
                    break; // Exit retry loop immediately — client error won't change on retry
                }

                throw new Exception($errorMessage);

            } catch (Exception $e) {
                $lastException = $e;

                Log::warning('SteVeHttpClient: Request failed', [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'error' => UrlSanitizer::scrubMessage($e->getMessage()),
                ]);

                if ($attempt < $this->maxRetries) {
                    $delay = $this->retryDelay * $attempt; // Exponential backoff
                    usleep($delay * 1000);
                }
            }
        }

        $duration = (microtime(true) - $startTime) * 1000;

        $failureContext = [
            'max_retries' => $this->maxRetries,
            'duration_ms' => round($duration, 2),
            'final_error' => UrlSanitizer::scrubMessage($lastException?->getMessage()),
        ];

        if ($permanentFailure) {
            Log::warning('SteVeHttpClient: Permanent request failure', $failureContext);
        } else {
            Log::error('SteVeHttpClient: All retry attempts failed', $failureContext);
        }

        throw $lastException ?? new Exception('Toutes les tentatives ont échoué');
    }

    /**
     * Wrapper for standardized response format
     */
    protected function executeRequest(string $method, string $endpoint, array $data = [], string $successMessage = 'Opération réussie'): array
    {
        try {
            $response = $this->makeRequest($method, $endpoint, $data);

            return [
                'success' => true,
                'data' => $response,
                'message' => $successMessage
            ];
        } catch (SteVeConfigurationException $e) {
            // Don't swallow this — let callers (controllers) map it to HTTP 503.
            throw $e;
        } catch (Exception $e) {
            $sanitized = UrlSanitizer::scrubMessage($e->getMessage());
            $errorCode = $this->classifySteveExceptionMessage($sanitized);

            Log::error('SteVeHttpClient: Request failed', [
                'method'   => $method,
                'endpoint' => $endpoint,
                'error'    => $sanitized,
                'error_code' => $errorCode,
            ]);

            if ($errorCode === 'charger_not_connected') {
                return [
                    'success' => false,
                    'error' => $sanitized,
                    'error_code' => $errorCode,
                    'message' => $this->messageForSteveErrorCode($errorCode),
                ];
            }

            return [
                'success' => false,
                'error'   => $sanitized,
                'error_code' => $errorCode,
                'message' => 'Échec de l\'opération',
            ];
        }
    }

    private function isNoActiveOcppSessionResponse(int $statusCode, mixed $jsonBody, string $rawBody): bool
    {
        if ($statusCode < 400) {
            return false;
        }

        $messageParts = [];
        if (is_array($jsonBody)) {
            foreach (['message', 'error', 'path'] as $key) {
                if (isset($jsonBody[$key]) && is_scalar($jsonBody[$key])) {
                    $messageParts[] = (string) $jsonBody[$key];
                }
            }
        } elseif (is_scalar($jsonBody)) {
            $messageParts[] = (string) $jsonBody;
        }

        $haystack = strtolower(trim(implode(' ', $messageParts) . ' ' . $rawBody));

        return str_contains($haystack, 'no active session found')
            && str_contains($haystack, 'chargeboxid');
    }

    private function classifySteveExceptionMessage(string $message): string
    {
        $haystack = strtolower($message);

        if (str_contains($haystack, 'no active session found')
            && str_contains($haystack, 'chargeboxid')) {
            return 'charger_not_connected';
        }

        return 'steve_request_failed';
    }

    private function messageForSteveErrorCode(string $errorCode): string
    {
        return match ($errorCode) {
            'charger_not_connected' => 'The charging point is provisioned in SteVe but is not connected over OCPP. Check that the charger is online, using the exact chargeBoxId, and connected to the SteVe WebSocket URL before trying RemoteStart again.',
            default => 'Echec de l\'operation',
        };
    }

    // ========================================================================
    // GROUP 1: CHARGE POINT (CHARGEBOX) MANAGEMENT
    // ========================================================================

    // ------------------------------------------------------------------------
    // ChargePoint CRUD — SteVe 3.9.0-SNAPSHOT
    //   GET    /chargePoints                  (filters: chargeBoxId, description, note, ocppVersion, heartbeatPeriod)
    //   POST   /chargePoints                  (ChargePointForm body)
    //   GET    /chargePoints/{chargePointPk}
    //   PUT    /chargePoints/{chargePointPk}  (ChargePointForm body)
    //   DELETE /chargePoints/{chargePointPk}
    //
    // The spec keys lookups by the integer chargePointPk (primary key on SteVe's
    // side). For backwards-compat we also accept a string chargeBoxId: passing
    // one triggers a list-call to resolve the PK first, at the cost of an
    // extra round-trip. Pass the int PK directly when you have it.
    // ------------------------------------------------------------------------

    /**
     * Create/register a charge point in SteVe.
     * Body: ChargePointForm — see SteVe REST spec.
     */
    public function createChargePoint(array $data): array
    {
        return $this->executeRequest(
            'POST',
            $this->apiPath('/chargePoints'),
            $data,
            'Point de charge créé avec succès'
        );
    }

    /**
     * Get charge point details by PK (int) or chargeBoxId (string).
     */
    public function getChargePoint(int|string $chargePoint): array
    {
        $pk = $this->resolveChargePointPk($chargePoint);
        if ($pk === null) {
            return $this->notFoundResponse('chargePoint', (string) $chargePoint);
        }

        return $this->executeRequest(
            'GET',
            $this->apiPath("/chargePoints/{$pk}"),
            [],
            'Détails du point de charge récupérés'
        );
    }

    /**
     * Update charge point metadata.
     */
    public function updateChargePoint(int|string $chargePoint, array $data): array
    {
        $pk = $this->resolveChargePointPk($chargePoint);
        if ($pk === null) {
            return $this->notFoundResponse('chargePoint', (string) $chargePoint);
        }

        return $this->executeRequest(
            'PUT',
            $this->apiPath("/chargePoints/{$pk}"),
            $data,
            'Point de charge mis à jour'
        );
    }

    /**
     * Delete/unregister a charge point.
     *
     * Destructive: deletes related transactions, reservations, connector status
     * and connector meter values per the SteVe spec. Use with care.
     */
    public function deleteChargePoint(int|string $chargePoint): array
    {
        $pk = $this->resolveChargePointPk($chargePoint);
        if ($pk === null) {
            return $this->notFoundResponse('chargePoint', (string) $chargePoint);
        }

        $result = $this->executeRequest(
            'DELETE',
            $this->apiPath("/chargePoints/{$pk}"),
            [],
            'Point de charge supprimé'
        );

        if (($result['success'] ?? false) === true) {
            $this->forgetChargePointPkCache($chargePoint);
        }

        return $result;
    }

    /**
     * Maximum number of charge points that can be requested in a single batch.
     * Keeps a hard ceiling so a runaway client can't fan out unbounded SteVe load.
     */
    public const BATCH_MAX_ITEMS = 50;

    /**
     * Fan out N concurrent GET /chargePoints/{pk} calls via Http::pool().
     *
     * Returns the same envelope shape as the single-item methods:
     *   ['success' => bool, 'data' => [pk => row|null, ...], 'errors' => [pk => ...]]
     *
     * Per-item failures land in `errors`; the top-level `success` is true
     * when every requested PK was fetched. `data` is keyed by PK so callers
     * can re-order results to match input.
     */
    public function batchGetChargePoints(array $chargePoints): array
    {
        return $this->batchOperation(
            'GET',
            $chargePoints,
            fn (int $pk) => "/chargePoints/{$pk}",
            successMessage: 'Détails des points de charge récupérés',
        );
    }

    /**
     * Fan out N concurrent PUT /chargePoints/{pk} calls via Http::pool().
     *
     * @param array<int, array<string, mixed>> $items Map of PK => ChargePointForm body.
     */
    public function batchUpdateChargePoints(array $items): array
    {
        return $this->batchOperation(
            'PUT',
            array_keys($items),
            fn (int $pk) => "/chargePoints/{$pk}",
            bodyFor: fn (int $pk) => $items[$pk] ?? [],
            successMessage: 'Points de charge mis à jour',
        );
    }

    /**
     * Fan out N concurrent DELETE /chargePoints/{pk} calls via Http::pool().
     * Cache eviction for each successfully deleted PK is best-effort.
     */
    public function batchDeleteChargePoints(array $chargePoints): array
    {
        $result = $this->batchOperation(
            'DELETE',
            $chargePoints,
            fn (int $pk) => "/chargePoints/{$pk}",
            successMessage: 'Points de charge supprimés',
        );

        foreach ($result['data'] ?? [] as $pk => $payload) {
            if ($payload !== null) {
                $this->forgetChargePointPkCache((int) $pk);
            }
        }

        return $result;
    }

    /**
     * Core fan-out helper. Resolves each input identifier to its SteVe PK
     * (cached, may issue an extra list-call for non-numeric chargeBoxIds),
     * builds the URL+body via the supplied closures, then pools the requests.
     *
     * Concurrency is bounded by BATCH_MAX_ITEMS so PHP-FPM workers and SteVe
     * itself stay protected from runaway fan-out.
     *
     * @param array<int, int|string> $identifiers Mixed PKs and chargeBoxIds.
     * @param callable(int):string   $endpointFor  PK → relative path under /manager/api/v1/*.
     * @param ?callable(int):array   $bodyFor      PK → JSON body (for PUT/POST).
     */
    protected function batchOperation(
        string $method,
        array $identifiers,
        callable $endpointFor,
        ?callable $bodyFor = null,
        string $successMessage = 'Opération réussie',
    ): array {
        if ($identifiers === []) {
            return [
                'success'  => true,
                'data'     => [],
                'errors'   => [],
                'message'  => $successMessage,
                'count'    => 0,
                'failures' => 0,
            ];
        }
        if (count($identifiers) > self::BATCH_MAX_ITEMS) {
            return [
                'success' => false,
                'data'    => [],
                'errors'  => [],
                'error'   => 'batch_size_exceeded',
                'message' => sprintf(
                    'Batch size %d exceeds the maximum of %d items per request.',
                    count($identifiers),
                    self::BATCH_MAX_ITEMS,
                ),
            ];
        }
        if (trim($this->baseUrl) === '') {
            throw new SteVeConfigurationException(
                'SteVe base URL is not configured. Set STEVE_API_URL in .env.'
            );
        }

        // Resolve PKs up front so non-numeric chargeBoxIds get their list-lookup
        // out of the way before we open the pool. PKs that can't be resolved
        // surface as `chargePoint_not_found` in the per-item errors map.
        $resolved = [];
        $errors   = [];
        foreach ($identifiers as $identifier) {
            $pk = $this->resolveChargePointPk($identifier);
            if ($pk === null) {
                $errors[(string) $identifier] = $this->notFoundResponse('chargePoint', (string) $identifier);
                continue;
            }
            $resolved[$pk] = $identifier;
        }

        $data = [];
        if ($resolved !== []) {
            $responses = Http::pool(function (Pool $pool) use ($method, $resolved, $endpointFor, $bodyFor) {
                $requests = [];
                foreach ($resolved as $pk => $_originalId) {
                    $request = $pool->as((string) $pk)
                        ->timeout($this->timeout)
                        ->withBasicAuth($this->username, $this->password)
                        ->withHeaders([
                            'Accept'       => 'application/json',
                            'Content-Type' => 'application/json',
                        ]);

                    $url = rtrim($this->baseUrl, '/') . '/' . ltrim($this->apiPath($endpointFor($pk)), '/');
                    $body = $bodyFor !== null ? $bodyFor($pk) : null;

                    $requests[] = match (strtoupper($method)) {
                        'GET'    => $request->get($url),
                        'DELETE' => $request->delete($url),
                        'PUT'    => $request->put($url, $body ?? []),
                        'POST'   => $request->post($url, $body ?? []),
                        default  => throw new Exception("Unsupported batch method: {$method}"),
                    };
                }
                return $requests;
            });

            foreach ($resolved as $pk => $_originalId) {
                $response = $responses[(string) $pk] ?? null;
                if ($response instanceof \Throwable) {
                    $errors[(string) $pk] = [
                        'success' => false,
                        'error'   => 'upstream_error',
                        'message' => UrlSanitizer::scrubMessage($response->getMessage()),
                    ];
                    continue;
                }
                if (!$response instanceof Response) {
                    $errors[(string) $pk] = [
                        'success' => false,
                        'error'   => 'upstream_error',
                        'message' => 'No response from upstream for PK ' . $pk,
                    ];
                    continue;
                }

                if ($response->successful()) {
                    $data[(string) $pk] = $this->normaliseResponseBody($response);
                    continue;
                }

                $errors[(string) $pk] = [
                    'success' => false,
                    'error'   => $response->status() === 404 ? 'chargePoint_not_found' : 'upstream_error',
                    'status'  => $response->status(),
                    'message' => "HTTP {$response->status()}",
                    'body'    => $this->safeBody($response),
                ];
            }
        }

        return [
            'success'  => $errors === [],
            'data'     => $data,
            'errors'   => $errors,
            'message'  => $errors === [] ? $successMessage : 'Partial failure across batch',
            'count'    => count($data),
            'failures' => count($errors),
        ];
    }

    /**
     * Decode a pooled response payload to the same shape used by makeRequest().
     */
    protected function normaliseResponseBody(Response $response): mixed
    {
        $json = $response->json();
        if ($json === null) {
            return null;
        }
        if (is_array($json)) {
            return $json;
        }
        return is_int($json) || ctype_digit((string) $json)
            ? ['taskId' => (int) $json]
            : ['result' => $json];
    }

    /**
     * Best-effort body extraction for error-side pooled responses; falls back
     * to truncated raw text when the body isn't valid JSON.
     */
    protected function safeBody(Response $response): mixed
    {
        $json = $response->json();
        return $json !== null ? $json : substr((string) $response->body(), 0, 4000);
    }

    /**
     * List charge points with optional filters.
     *
     * Accepted filter keys (per spec):
     *   chargeBoxId, description, note (substring),
     *   ocppVersion (V_12|V_15|V_16),
     *   heartbeatPeriod (ALL|TODAY|YESTERDAY|EARLIER)
     */
    public function listChargePoints(array $filters = []): array
    {
        $endpoint = $this->apiPath('/chargePoints');
        $qs = $this->buildQueryString($filters);
        if ($qs !== '') {
            $endpoint .= '?' . $qs;
        }

        $result = $this->executeRequest('GET', $endpoint, [], 'Liste des points de charge récupérée');

        if ($result['success']) {
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }

        return $result;
    }

    /**
     * Find one SteVe charge point by its OCPP chargeBoxId.
     *
     * SteVe exposes create as a plain INSERT and returns HTTP 500 when the
     * chargeBoxId already exists. Provisioning flows use this helper to adopt
     * a row left behind by a prior partial attempt instead of double-posting.
     */
    public function findChargePointByChargeBoxId(?string $chargeBoxId): ?array
    {
        if (!is_string($chargeBoxId) || trim($chargeBoxId) === '') {
            return null;
        }

        $chargeBoxId = trim($chargeBoxId);
        $lookup = $this->listChargePoints(['chargeBoxId' => $chargeBoxId]);

        if (!($lookup['success'] ?? false)) {
            return null;
        }

        foreach ($this->normalizeChargePointRows($lookup['data'] ?? null) as $entry) {
            if (($entry['chargeBoxId'] ?? null) !== $chargeBoxId) {
                continue;
            }

            $pk = $entry['chargeBoxPk'] ?? $entry['chargePointPk'] ?? $entry['id'] ?? null;
            if (!is_numeric($pk) || (int) $pk <= 0) {
                continue;
            }

            $entry['chargeBoxPk'] = (int) $pk;
            Cache::put(self::CHARGE_POINT_PK_CACHE_PREFIX . $chargeBoxId, (int) $pk, self::RESOLUTION_CACHE_TTL);

            return $entry;
        }

        return null;
    }

    /**
     * Cache key prefix for chargeBoxId → chargePointPk resolution.
     * Bumping this invalidates every cached entry (e.g. on a SteVe migration).
     */
    protected const CHARGE_POINT_PK_CACHE_PREFIX = 'steve:chargePointPk:v1:';

    /**
     * Resolution cache TTL (seconds). PKs are stable across the lifetime of a
     * charge point row in SteVe, so we cache generously and invalidate on
     * delete. A modest TTL still bounds staleness if a CP is deleted and
     * recreated out-of-band through SteVe's web UI.
     */
    protected const RESOLUTION_CACHE_TTL = 300;

    /**
     * Resolve a charge point identifier to its SteVe PK.
     * - Integer or all-digit string → cast directly (no round-trip).
     * - Other string → list-by-chargeBoxId with positive-result caching.
     *
     * Defensive: only an *exact* chargeBoxId match populates the PK. SteVe's
     * list filter is documented as an equality match on chargeBoxId, but we
     * verify here so a future spec change to substring-matching can't silently
     * point a delete/update at the wrong charge point.
     */
    protected function resolveChargePointPk(int|string $chargePoint): ?int
    {
        if (is_int($chargePoint)) {
            return $chargePoint > 0 ? $chargePoint : null;
        }
        if (ctype_digit($chargePoint)) {
            $pk = (int) $chargePoint;
            return $pk > 0 ? $pk : null;
        }

        $cacheKey = self::CHARGE_POINT_PK_CACHE_PREFIX . $chargePoint;
        $cached   = Cache::get($cacheKey);
        if (is_int($cached) && $cached > 0) {
            return $cached;
        }

        $lookup = $this->listChargePoints(['chargeBoxId' => $chargePoint]);
        if (!($lookup['success'] ?? false) || !is_array($lookup['data'] ?? null)) {
            return null;
        }

        foreach ($lookup['data'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (($entry['chargeBoxId'] ?? null) !== $chargePoint) {
                continue;
            }

            $pk = $entry['chargeBoxPk'] ?? $entry['chargePointPk'] ?? null;
            if (is_numeric($pk) && (int) $pk > 0) {
                Cache::put($cacheKey, (int) $pk, self::RESOLUTION_CACHE_TTL);
                return (int) $pk;
            }
        }

        return null;
    }

    /**
     * Forget the cached PK for a chargeBoxId. Called after a successful
     * delete so a subsequent lookup of the same ID won't hit a stale entry.
     */
    protected function forgetChargePointPkCache(int|string $chargePoint): void
    {
        if (is_string($chargePoint) && !ctype_digit($chargePoint)) {
            Cache::forget(self::CHARGE_POINT_PK_CACHE_PREFIX . $chargePoint);
        }
    }

    /**
     * Helper: shape a not-found upstream response without an HTTP call.
     */
    protected function notFoundResponse(string $resource, string $identifier): array
    {
        return [
            'success' => false,
            'error'   => "{$resource}_not_found",
            'message' => "{$resource} '{$identifier}' was not found in SteVe.",
            'data'    => null,
        ];
    }

    /**
     * Backward-compatible alias used by older SteVe consumers.
     */
    public function getChargePoints(array $filters = []): array
    {
        return $this->listChargePoints($filters);
    }

    /**
     * Get charger status (simplified API)
     */
    public function getChargerStatus(string $chargerId): array
    {
        return $this->executeRequest('GET', "/api/v1/chargers/{$chargerId}/status", [], 'Statut du chargeur récupéré');
    }

    /**
     * Get charger info
     */
    public function getChargerInfo(string $chargerId): array
    {
        return $this->executeRequest('GET', "/api/v1/chargers/{$chargerId}", [], 'Informations du chargeur récupérées');
    }

    /**
     * List all chargers (simplified API)
     */
    public function listChargers(): array
    {
        $result = $this->executeRequest('GET', '/api/v1/chargers', [], 'Liste des chargeurs récupérée');
        
        if ($result['success']) {
            $result['chargers'] = $result['data'];
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }
        
        return $result;
    }

    // ========================================================================
    // GROUP 2: CONNECTOR / PORT STATUS & CONTROL
    // ========================================================================

    /**
     * Get connector status
     */
    public function getConnectorStatus(string $chargePointId, ?int $connectorId = null): array
    {
        $filters = [
            'chargeBoxId' => [$chargePointId],
            'type' => 'ALL',
        ];

        if ($connectorId !== null) {
            $filters['connectorId'] = $connectorId;
        }

        $endpoint = $this->apiPath('/transactions') . '?' . $this->buildQueryString($filters);

        return $this->executeRequest('GET', $endpoint, [], 'Statut du connecteur récupéré');
    }

    // ------------------------------------------------------------------------
    // OCPP commands — aligned to SteVe 3.9.0-SNAPSHOT canonical REST surface:
    //   POST /api/v1/ocpp/remote-start                 JSON body
    //   POST /api/v1/ocpp/remote-stop?chargeBoxId=…    chargeBoxId in query
    //
    // The legacy multi-station `/operations/Remote{Start,Stop}Transaction` shape
    // with `chargeBoxIdList:[id]` does not exist in 3.9.0 — calling it returned
    // 404 against real instances. transactionId is still required by callers
    // (for local audit + state-machine pairing) even though SteVe resolves the
    // active txn server-side from chargeBoxId.
    // ------------------------------------------------------------------------

    /**
     * RemoteStartTransaction -> POST /ocpp/remote-start.
     */
    public function remoteStartTransaction(string $chargePointId, array $params): array
    {
        $connectorId = (int) ($params['connector_id'] ?? $params['connectorId'] ?? 1);
        $ocppTag = $params['ocpp_tag']
            ?? $params['ocppTag']
            ?? $params['id_tag']
            ?? $params['idTag']
            ?? config('steve.default_id_tag', '');

        $payload = [
            'chargeBoxId' => $chargePointId,
            'connectorId' => $connectorId,
            'ocppTag'     => (string) $ocppTag,
        ];

        $result = $this->executeRequest(
            'POST',
            $this->apiPath('/ocpp/remote-start'),
            $payload,
            'Transaction demarree a distance'
        );

        if ($result['success']) {
            $result['transaction_id'] = $result['data']['transaction']['id'] ?? null;
        } elseif (($result['error_code'] ?? null) === 'charger_not_connected') {
            $result['data'] = array_merge(is_array($result['data'] ?? null) ? $result['data'] : [], [
                'chargeBoxId' => $chargePointId,
                'connectorId' => $connectorId,
            ]);
        }

        return $result;
    }

    /**
     * RemoteStopTransaction -> POST /ocpp/remote-stop?chargeBoxId=...
     */
    public function remoteStopTransaction(string $chargePointId, int|string|null $transactionId = null): array
    {
        $chargePointId = trim($chargePointId);
        if ($chargePointId === '') {
            return [
                'success' => false,
                'error' => 'chargebox_id_required',
                'error_code' => 'chargebox_id_required',
                'message' => 'chargeBoxId is required for SteVe RemoteStopTransaction.',
            ];
        }

        $resolvedTransactionId = $transactionId;
        if ($resolvedTransactionId === null || $resolvedTransactionId === '') {
            $resolvedTransactionId = $this->findActiveTransactionId($chargePointId);
        }

        $endpoint = $this->apiPath('/ocpp/remote-stop')
            . '?' . $this->buildQueryString(['chargeBoxId' => $chargePointId]);

        Log::info('SteVeHttpClient: RemoteStopTransaction request', [
            'endpoint' => $endpoint,
            'chargeBoxId' => $chargePointId,
            'transactionId' => $resolvedTransactionId,
        ]);

        $result = $this->executeRequest('POST', $endpoint, [], 'Transaction arretee a distance');
        if (!($result['success'] ?? false) && $this->shouldTryRootApiFallback($result)) {
            $fallbackEndpoint = $this->rootApiUrl('/ocpp/remote-stop')
                . '?' . $this->buildQueryString(['chargeBoxId' => $chargePointId]);

            if ($fallbackEndpoint !== $this->buildRequestUrl($endpoint)) {
                Log::warning('SteVeHttpClient: retrying RemoteStopTransaction on root /api/v1 endpoint', [
                    'configured_endpoint' => $endpoint,
                    'fallback_endpoint' => $fallbackEndpoint,
                    'chargeBoxId' => $chargePointId,
                ]);

                $result = $this->executeRequest('POST', $fallbackEndpoint, [], 'Transaction arretee a distance');
            }
        }

        if ($result['success']) {
            $ocppStatus = strtoupper((string) ($result['data']['status'] ?? ''));
            $result['ocpp_status'] = $ocppStatus !== '' ? $ocppStatus : null;

            if ($ocppStatus !== '' && $ocppStatus !== 'ACCEPTED') {
                return [
                    'success' => false,
                    'error' => 'remote_stop_rejected',
                    'error_code' => 'remote_stop_rejected',
                    'ocpp_status' => $ocppStatus,
                    'data' => $result['data'] ?? null,
                    'transaction_id' => $resolvedTransactionId,
                    'message' => $result['data']['message']
                        ?? 'RemoteStopTransaction rejected by SteVe.',
                ];
            }

            $result['transaction_id'] = $result['data']['transaction']['id']
                ?? $result['data']['transaction']['transactionId']
                ?? $result['data']['transaction']['transactionPk']
                ?? $result['data']['transactionId']
                ?? $result['data']['transactionPk']
                ?? $resolvedTransactionId;
        }
        if (($result['error_code'] ?? null) === 'charger_not_connected') {
            $result['data'] = array_merge(is_array($result['data'] ?? null) ? $result['data'] : [], [
                'chargeBoxId' => $chargePointId,
                'transactionId' => $resolvedTransactionId,
            ]);
        }

        return $result;
    }

    private function findActiveTransactionId(string $chargePointId): int|string|null
    {
        $transactions = $this->getTransactions([
            'chargeBoxId' => $chargePointId,
            'type' => 'ACTIVE',
        ]);

        if (!($transactions['success'] ?? false)) {
            return null;
        }

        $rows = $this->normalizeRows($transactions['data'] ?? []);
        foreach ($rows as $row) {
            foreach (['transactionPk', 'transactionId', 'transaction_id', 'id'] as $key) {
                $value = $row[$key] ?? null;
                if (is_int($value) || (is_string($value) && trim($value) !== '')) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function unlockConnector(string $chargePointId, int $connectorId): array
    {
        $endpoint = $this->apiPath('/ocpp/unlock-connector') . '?' . $this->buildQueryString([
            'chargeBoxId' => $chargePointId,
            'connectorId' => $connectorId,
        ]);

        return $this->executeRequest('POST', $endpoint, [], 'Connecteur debloque');
    }

    public function changeAvailability(string $chargePointId, int $connectorId, string $type): array
    {
        $normalizedType = $this->normalizeAvailabilityType($type);
        if ($normalizedType === null) {
            return [
                'success' => false,
                'error'   => 'invalid_avail_type',
                'message' => "Invalid availType '{$type}'. Expected Operative or Inoperative.",
            ];
        }

        $endpoint = $this->apiPath('/ocpp/change-availability') . '?' . $this->buildQueryString([
            'chargeBoxId' => $chargePointId,
            'availType'   => $normalizedType,
        ]);

        return $this->executeRequest('POST', $endpoint, [], 'Disponibilite modifiee');
    }

    public function reboot(string $chargePointId, string $type = 'Soft'): array
    {
        return $this->reset($chargePointId, $type);
    }

    public function reset(string $chargePointId, string $type = 'Soft'): array
    {
        $resetType = $this->normalizeResetType($type);
        if ($resetType === null) {
            return [
                'success' => false,
                'error'   => 'invalid_reset_type',
                'message' => "Invalid resetType '{$type}'. Expected Soft or Hard.",
            ];
        }

        $payload = [
            'chargeBoxIdList' => [$chargePointId],
            'resetType'       => $resetType,
        ];

        return $this->executeRequest(
            'POST',
            $this->apiPath('/operations/Reset'),
            $payload,
            'Reinitialisation effectuee'
        );
    }

    public function clearCache(string $chargePointId): array
    {
        $payload = [
            'chargeBoxIdList' => [$chargePointId],
        ];

        return $this->executeRequest(
            'POST',
            $this->apiPath('/operations/ClearCache'),
            $payload,
            'Cache vide'
        );
    }

    // ========================================================================
    // GROUP 3: TRANSACTIONS & SESSIONS
    // ========================================================================

    /**
     * Get transactions list
     */
    public function getTransactions(array $filters = []): array
    {
        $queryString = $this->buildQueryString($filters);
        $endpoint = $this->apiPath('/transactions') . ($queryString ? "?{$queryString}" : '');

        $result = $this->executeRequest('GET', $endpoint, [], 'Transactions récupérées');
        
        if ($result['success']) {
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }
        
        return $result;
    }

    /**
     * Get transaction details
     */
    public function getTransaction(string $transactionId): array
    {
        return $this->executeRequest('GET', $this->apiPath("/transactions/{$transactionId}"), [], 'Détails de la transaction récupérés');
    }

    /**
     * Get meter values for a transaction
     */
    public function getMeterValues(string $transactionId, array $filters = []): array
    {
        $queryString = $this->buildQueryString($filters);
        $endpoint = $this->apiPath("/transactions/{$transactionId}") . ($queryString ? "?{$queryString}" : '');

        return $this->executeRequest('GET', $endpoint, [], 'Valeurs de compteur récupérées');
    }

    /**
     * Get session status
     */
    public function getSessionStatus(string $chargerId, string $sessionId): array
    {
        return $this->executeRequest('GET', "/api/v1/charges/{$sessionId}/status", [], 'Statut de la session récupéré');
    }

    /**
     * Start charge (simplified API)
     */
    public function startCharge(string $chargerId, string $sessionId): array
    {
        $payload = [
            'charger_id' => $chargerId,
            'session_id' => $sessionId,
            'timestamp' => now()->toISOString()
        ];

        return $this->executeRequest('POST', '/api/v1/charges/start', $payload, 'Charge démarrée');
    }

    /**
     * Stop charge (simplified API)
     */
    public function stopCharge(string $chargerId, string $sessionId): array
    {
        $payload = [
            'charger_id' => $chargerId,
            'session_id' => $sessionId,
            'timestamp' => now()->toISOString()
        ];

        return $this->executeRequest('POST', '/api/v1/charges/stop', $payload, 'Charge arrêtée');
    }

    // ========================================================================
    // GROUP 4: CONFIGURATION & MAINTENANCE
    // ========================================================================

    /**
     * Get configuration
     */
    public function getConfiguration(string $chargePointId, array $keys = []): array
    {
        $payload = ['chargePointId' => $chargePointId];
        
        if (!empty($keys)) {
            $payload['keys'] = $keys;
        }

        $result = $this->executeRequest('POST', '/api/v1/commands/getConfiguration', $payload, 'Configuration récupérée');
        
        if ($result['success']) {
            $result['configuration'] = $result['data']['configurationKey'] ?? [];
        }
        
        return $result;
    }

    /**
     * ChangeConfiguration → POST /ocpp/change-configuration?chargeBoxId=
     * Body: { keyType: 'PREDEFINED'|'CUSTOM', confKey?: string, customConfKey?: string, value: string }
     * Returns: task ID (integer)
     *
     * Predefined keys are documented in the OCPP 1.6 spec (e.g. HeartBeatInterval,
     * MeterValueSampleInterval). Anything outside that set is `CUSTOM` and uses
     * customConfKey on the wire — SteVe surfaces them under the same configuration
     * map but with `readonly: false` semantics on the charge point side.
     */
    public function changeConfiguration(
        string $chargePointId,
        string $key,
        string $value,
        string $keyType = 'PREDEFINED'
    ): array {
        $normalizedKeyType = strtoupper(trim($keyType));
        if (!in_array($normalizedKeyType, ['PREDEFINED', 'CUSTOM'], true)) {
            return [
                'success' => false,
                'error'   => 'invalid_key_type',
                'message' => "Invalid keyType '{$keyType}'. Expected PREDEFINED or CUSTOM.",
            ];
        }

        $body = ['keyType' => $normalizedKeyType, 'value' => $value];
        $body[$normalizedKeyType === 'CUSTOM' ? 'customConfKey' : 'confKey'] = $key;

        $endpoint = $this->apiPath('/ocpp/change-configuration')
            . '?' . $this->buildQueryString(['chargeBoxId' => $chargePointId]);

        return $this->executeRequest('POST', $endpoint, $body, 'Configuration modifiée');
    }

    /**
     * Get diagnostics
     */
    public function getDiagnostics(string $chargePointId, array $params = []): array
    {
        $payload = array_merge(['chargePointId' => $chargePointId], $params);

        $result = $this->executeRequest('POST', '/api/v1/commands/getDiagnostics', $payload, 'Diagnostics récupérés');
        
        if ($result['success']) {
            $result['file_name'] = $result['data']['fileName'] ?? null;
        }
        
        return $result;
    }

    /**
     * Get logs
     */
    public function getLog(string $chargePointId, array $params = []): array
    {
        $payload = array_merge(['chargePointId' => $chargePointId], $params);

        $result = $this->executeRequest('POST', '/api/v1/commands/getLog', $payload, 'Logs récupérés');
        
        if ($result['success']) {
            $result['file_name'] = $result['data']['fileName'] ?? null;
        }
        
        return $result;
    }

    /**
     * Update firmware
     */
    public function updateFirmware(string $chargePointId, string $location, ?string $retrieveDate = null): array
    {
        $payload = [
            'chargePointId' => $chargePointId,
            'location' => $location,
        ];

        if ($retrieveDate) {
            $payload['retrieveDate'] = $retrieveDate;
        }

        return $this->executeRequest('POST', '/api/v1/commands/updateFirmware', $payload, 'Mise à jour firmware lancée');
    }

    // Smart-charging profile commands (SetChargingProfile, ClearChargingProfile,
    // GetCompositeSchedule) were removed in Slice 3 — they used to POST to a
    // fictional `/api/v1/commands/*` surface that never existed under SteVe's
    // canonical 3.9.0-SNAPSHOT REST spec. The management UI still exposes
    // them via the SOAP/JSON OCPP transports but they're not part of the
    // documented management REST API this client targets.

    // ========================================================================
    // GROUP 5: USERS / AUTHENTICATION / RESERVATIONS
    // ========================================================================

    /**
     * List users
     */
    public function listUsers(array $filters = []): array
    {
        $queryString = http_build_query($filters);
        $endpoint = '/api/v1/users' . ($queryString ? "?{$queryString}" : '');

        $result = $this->executeRequest('GET', $endpoint, [], 'Utilisateurs récupérés');
        
        if ($result['success']) {
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }
        
        return $result;
    }

    /**
     * Get user details
     */
    public function getUser(string $userId): array
    {
        return $this->executeRequest('GET', "/api/v1/users/{$userId}", [], 'Détails utilisateur récupérés');
    }

    /**
     * Create user
     */
    public function createUser(array $data): array
    {
        return $this->executeRequest('POST', '/api/v1/users', $data, 'Utilisateur créé');
    }

    /**
     * Update user
     */
    public function updateUser(string $userId, array $data): array
    {
        return $this->executeRequest('PUT', "/api/v1/users/{$userId}", $data, 'Utilisateur mis à jour');
    }

    /**
     * Delete user
     */
    public function deleteUser(string $userId): array
    {
        return $this->executeRequest('DELETE', "/api/v1/users/{$userId}", [], 'Utilisateur supprimé');
    }

    /**
     * Reserve now
     */
    public function reserveNow(string $chargePointId, array $params): array
    {
        $payload = [
            'chargePointId' => $chargePointId,
            'connectorId' => $params['connector_id'] ?? 0,
            'expiryDate' => $params['expiry_date'] ?? now()->addHours(1)->toISOString(),
            'idTag' => $params['id_tag'],
            'parentIdTag' => $params['parent_id_tag'] ?? null,
            'reservationId' => $params['reservation_id'] ?? null
        ];

        $result = $this->executeRequest('POST', '/api/v1/commands/reserveNow', $payload, 'Réservation effectuée');
        
        if ($result['success']) {
            $result['reservation_id'] = $result['data']['reservationId'] ?? null;
            $result['status'] = $result['data']['status'] ?? null;
        }
        
        return $result;
    }

    /**
     * Cancel reservation
     */
    public function cancelReservation(string $chargePointId, int $reservationId): array
    {
        $payload = [
            'chargePointId' => $chargePointId,
            'reservationId' => $reservationId
        ];

        $result = $this->executeRequest('POST', '/api/v1/commands/cancelReservation', $payload, 'Réservation annulée');
        
        if ($result['success']) {
            $result['status'] = $result['data']['status'] ?? null;
        }
        
        return $result;
    }

    /**
     * Get local list version
     */
    public function getLocalListVersion(string $chargePointId): array
    {
        $payload = ['chargePointId' => $chargePointId];

        $result = $this->executeRequest('POST', '/api/v1/commands/getLocalListVersion', $payload, 'Version liste locale récupérée');
        
        if ($result['success']) {
            $result['list_version'] = $result['data']['listVersion'] ?? null;
        }
        
        return $result;
    }

    /**
     * Send local list
     */
    public function sendLocalList(string $chargePointId, int $listVersion, string $updateType, array $localAuthorizationList = []): array
    {
        $payload = [
            'chargePointId' => $chargePointId,
            'listVersion' => $listVersion,
            'updateType' => $updateType,
            'localAuthorizationList' => $localAuthorizationList
        ];

        $result = $this->executeRequest('POST', '/api/v1/commands/sendLocalList', $payload, 'Liste locale envoyée');
        
        if ($result['success']) {
            $result['status'] = $result['data']['status'] ?? null;
        }
        
        return $result;
    }

    // ========================================================================
    // GROUP 6: OCPP TAGS (RFID / USER IDENTIFIERS)
    // ========================================================================
    //
    // SteVe 3.9.0-SNAPSHOT REST:
    //   GET    /ocppTags                 (filters below)
    //   POST   /ocppTags                 (OcppTagForm body)
    //   GET    /ocppTags/{ocppTagPk}
    //   PUT    /ocppTags/{ocppTagPk}     (OcppTagForm body)
    //   DELETE /ocppTags/{ocppTagPk}
    // ========================================================================

    /**
     * List OCPP tags with optional filters.
     *
     * Accepted filter keys (per spec):
     *   ocppTagPk, idTag, parentIdTag, userId,
     *   expired (ALL|TRUE|FALSE), inTransaction (ALL|TRUE|FALSE),
     *   blocked (ALL|TRUE|FALSE), note (substring),
     *   userFilter (All|OnlyTagsWithUser|OnlyTagsWithoutUser)
     */
    public function listOcppTags(array $filters = []): array
    {
        $endpoint = $this->apiPath('/ocppTags');
        $qs = $this->buildQueryString($filters);
        if ($qs !== '') {
            $endpoint .= '?' . $qs;
        }

        $result = $this->executeRequest('GET', $endpoint, [], 'Liste des tags OCPP récupérée');

        if ($result['success']) {
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }

        return $result;
    }

    /**
     * Create a new OCPP tag (RFID card / user identifier).
     * Body: OcppTagForm { idTag, expiryDate?, maxActiveTransactionCount?, note?, parentIdTag? }
     */
    public function createOcppTag(array $data): array
    {
        return $this->executeRequest(
            'POST',
            $this->apiPath('/ocppTags'),
            $data,
            'Tag OCPP créé avec succès'
        );
    }

    /**
     * Get an OCPP tag by PK (int) or idTag (string).
     */
    public function getOcppTag(int|string $tag): array
    {
        $pk = $this->resolveOcppTagPk($tag);
        if ($pk === null) {
            return $this->notFoundResponse('ocppTag', (string) $tag);
        }

        return $this->executeRequest(
            'GET',
            $this->apiPath("/ocppTags/{$pk}"),
            [],
            'Détails du tag OCPP récupérés'
        );
    }

    /**
     * Update an OCPP tag.
     */
    public function updateOcppTag(int|string $tag, array $data): array
    {
        $pk = $this->resolveOcppTagPk($tag);
        if ($pk === null) {
            return $this->notFoundResponse('ocppTag', (string) $tag);
        }

        return $this->executeRequest(
            'PUT',
            $this->apiPath("/ocppTags/{$pk}"),
            $data,
            'Tag OCPP mis à jour'
        );
    }

    /**
     * Delete an OCPP tag. Returns the deleted tag per spec.
     */
    public function deleteOcppTag(int|string $tag): array
    {
        $pk = $this->resolveOcppTagPk($tag);
        if ($pk === null) {
            return $this->notFoundResponse('ocppTag', (string) $tag);
        }

        $result = $this->executeRequest(
            'DELETE',
            $this->apiPath("/ocppTags/{$pk}"),
            [],
            'Tag OCPP supprimé'
        );

        if (($result['success'] ?? false) === true) {
            $this->forgetOcppTagPkCache($tag);
        }

        return $result;
    }

    protected const OCPP_TAG_PK_CACHE_PREFIX = 'steve:ocppTagPk:v1:';

    /**
     * Resolve an OCPP tag identifier to its SteVe PK.
     * - Integer or all-digit string → cast directly.
     * - Other string → list-by-idTag with positive-result caching + exact match.
     */
    protected function resolveOcppTagPk(int|string $tag): ?int
    {
        if (is_int($tag)) {
            return $tag > 0 ? $tag : null;
        }
        if (ctype_digit($tag)) {
            $pk = (int) $tag;
            return $pk > 0 ? $pk : null;
        }

        $cacheKey = self::OCPP_TAG_PK_CACHE_PREFIX . $tag;
        $cached   = Cache::get($cacheKey);
        if (is_int($cached) && $cached > 0) {
            return $cached;
        }

        $lookup = $this->listOcppTags(['idTag' => $tag]);
        if (!($lookup['success'] ?? false) || !is_array($lookup['data'] ?? null)) {
            return null;
        }

        foreach ($lookup['data'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (($entry['idTag'] ?? null) !== $tag) {
                continue;
            }

            $pk = $entry['ocppTagPk'] ?? null;
            if (is_numeric($pk) && (int) $pk > 0) {
                Cache::put($cacheKey, (int) $pk, self::RESOLUTION_CACHE_TTL);
                return (int) $pk;
            }
        }

        return null;
    }

    protected function forgetOcppTagPkCache(int|string $tag): void
    {
        if (is_string($tag) && !ctype_digit($tag)) {
            Cache::forget(self::OCPP_TAG_PK_CACHE_PREFIX . $tag);
        }
    }

    // ========================================================================
    // GROUP 7: CONNECTORS — LISTINGS & STATUS
    // ========================================================================
    //
    // SteVe 3.9.0-SNAPSHOT REST:
    //   GET /connectors?chargeBoxId=...   (one CP)
    //   GET /connectors/all               (every CP)
    //   GET /connectors/status?chargeBoxId=...   (one CP, with `online` flag)
    // ========================================================================

    /**
     * List connectors for one charge box.
     */
    public function listConnectors(string $chargeBoxId): array
    {
        $endpoint = $this->apiPath('/connectors')
            . '?' . $this->buildQueryString(['chargeBoxId' => $chargeBoxId]);

        $result = $this->executeRequest('GET', $endpoint, [], 'Connecteurs récupérés');

        if ($result['success']) {
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }

        return $result;
    }

    /**
     * List connectors across every registered charge box.
     */
    public function listAllConnectors(): array
    {
        $result = $this->executeRequest(
            'GET',
            $this->apiPath('/connectors/all'),
            [],
            'Tous les connecteurs récupérés'
        );

        if ($result['success']) {
            $result['count'] = is_array($result['data']) ? count($result['data']) : 0;
        }

        return $result;
    }

    /**
     * Get connector status for a charge box.
     * Spec response shape: { connectors: ConnectorStatus[], online: bool }
     */
    public function getConnectorsStatus(string $chargeBoxId): array
    {
        $endpoint = $this->apiPath('/connectors/status')
            . '?' . $this->buildQueryString(['chargeBoxId' => $chargeBoxId]);

        return $this->executeRequest('GET', $endpoint, [], 'Statut des connecteurs récupéré');
    }

    /**
     * Build a real-time status view from the documented SteVe API.
     *
     * Transactions are the reliable official REST source in upstream SteVe. The
     * connectors endpoint is supported by some deployments/forks, so it is used
     * as an enrichment source when available but a missing connectors endpoint
     * no longer prevents status from being returned.
     */
    public function getRealtimeChargePointStatus(
        string $chargeBoxId,
        ?int $connectorId = null,
        ?string $ocppIdTag = null
    ): array {
        $transactionFilters = [
            'type'        => 'ACTIVE',
            'chargeBoxId' => $chargeBoxId,
        ];

        if (is_string($ocppIdTag) && trim($ocppIdTag) !== '') {
            $transactionFilters['ocppIdTag'] = trim($ocppIdTag);
        }

        $transactions = $this->getTransactions($transactionFilters);
        $activeTransactions = $this->normalizeRows($transactions['data'] ?? []);
        if ($connectorId !== null) {
            $activeTransactions = array_values(array_filter(
                $activeTransactions,
                fn (array $row): bool => $this->rowConnectorId($row) === $connectorId
            ));
        }

        $connectorsResult = $this->getConnectorsStatus($chargeBoxId);
        $connectorPayload = is_array($connectorsResult['data'] ?? null) ? $connectorsResult['data'] : [];
        $connectors = $this->normalizeRows($connectorPayload['connectors'] ?? $connectorPayload);
        if ($connectorId !== null) {
            $connectors = array_values(array_filter(
                $connectors,
                fn (array $row): bool => $this->rowConnectorId($row) === $connectorId
            ));
        }

        $transactionsOk = ($transactions['success'] ?? false) === true;
        $connectorsOk = ($connectorsResult['success'] ?? false) === true;

        if (!$transactionsOk && !$connectorsOk) {
            return [
                'success' => false,
                'error'   => 'realtime_status_unavailable',
                'message' => 'Unable to retrieve SteVe real-time status from transactions or connectors.',
                'data'    => [
                    'transactions' => $transactions,
                    'connectors'   => $connectorsResult,
                ],
            ];
        }

        $online = $connectorPayload['online'] ?? null;
        $summary = $this->summarizeRealtimeStatus($activeTransactions, $connectors, $online);

        return [
            'success' => true,
            'message' => 'Statut temps-réel récupéré',
            'data'    => [
                'chargeBoxId'        => $chargeBoxId,
                'connectorId'        => $connectorId,
                'ocppIdTag'          => $transactionFilters['ocppIdTag'] ?? null,
                'online'             => $online,
                'status'             => $summary['status'],
                'connectors'         => $connectors,
                'activeTransactions' => $activeTransactions,
                'sources'            => [
                    'transactions' => $transactionsOk,
                    'connectors'   => $connectorsOk,
                ],
                'sourceErrors'        => [
                    'transactions' => $transactionsOk ? null : ($transactions['error'] ?? $transactions['message'] ?? null),
                    'connectors'   => $connectorsOk ? null : ($connectorsResult['error'] ?? $connectorsResult['message'] ?? null),
                ],
            ],
            'count'              => count($connectors),
            'available'          => $summary['available'],
            'charging'           => $summary['charging'],
            'faulted'            => $summary['faulted'],
            'activeTransactions' => count($activeTransactions),
        ];
    }

    // ========================================================================
    // UTILITY METHODS
    // ========================================================================

    /**
     * Test connection to SteVe API
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->makeRequest('GET', $this->apiPath('/transactions?type=ALL'));
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'success' => true,
                'connected' => true,
                'response' => $response,
                'duration_ms' => round($duration, 2)
            ];
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'success' => false,
                'connected' => false,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2)
            ];
        }
    }

    /**
     * Check if SteVe API is available
     */
    public function isAvailable(): bool
    {
        try {
            $result = $this->testConnection();
            return $result['success'] && $result['connected'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Connect charger
     */
    public function connectCharger(string $chargerId): array
    {
        $payload = [
            'charger_id' => $chargerId,
            'timestamp' => now()->toISOString()
        ];

        return $this->executeRequest('POST', '/api/v1/chargers/connect', $payload, 'Chargeur connecté');
    }

    /**
     * Generate WebSocket URL for charger
     */
    public function generateWebSocketUrl(string $chargerId): string
    {
        return rtrim($this->websocketUrl, '/') . '/' . rawurlencode($chargerId);
    }

    /**
     * Get charging statistics
     */
    public function getChargingStats(?string $chargerId = null): array
    {
        $endpoint = $chargerId ? "/api/v1/chargers/{$chargerId}/stats" : '/api/v1/stats';
        
        return $this->executeRequest('GET', $endpoint, [], 'Statistiques récupérées');
    }

    /**
     * Get service configuration
     */
    public function getConfig(): array
    {
        // P6.4: never expose basic-auth credentials embedded in baseUrl/websocketUrl
        // — this method feeds admin diagnostics endpoints.
        return [
            'base_url'      => UrlSanitizer::strip($this->baseUrl),
            'timeout'       => $this->timeout,
            'max_retries'   => $this->maxRetries,
            'retry_delay'   => $this->retryDelay,
            'websocket_url' => UrlSanitizer::strip($this->websocketUrl),
        ];
    }

    /**
     * Get available endpoint groups
     */
    public function getAvailableEndpointGroups(): array
    {
        return [
            'group_1_charge_point_management' => [
                'name' => 'Charge Point (ChargeBox) Management',
                'endpoints' => ['createChargePoint', 'getChargePoint', 'updateChargePoint', 'deleteChargePoint', 'listChargePoints']
            ],
            'group_2_connector_control' => [
                'name' => 'Connector / Port Status & Control',
                'endpoints' => ['getConnectorStatus', 'getRealtimeChargePointStatus', 'remoteStartTransaction', 'remoteStopTransaction', 'unlockConnector', 'changeAvailability', 'reset', 'reboot', 'clearCache']
            ],
            'group_3_transactions' => [
                'name' => 'Transactions & Sessions',
                'endpoints' => ['getTransactions', 'getTransaction', 'getMeterValues', 'startCharge', 'stopCharge']
            ],
            'group_4_maintenance' => [
                'name' => 'Configuration & Maintenance Commands',
                'endpoints' => ['getConfiguration', 'changeConfiguration', 'getDiagnostics', 'getLog', 'updateFirmware'],
            ],
            'group_5_users_reservations' => [
                'name' => 'Users / Authentication / Reservations',
                'endpoints' => ['listUsers', 'getUser', 'createUser', 'updateUser', 'deleteUser', 'reserveNow', 'cancelReservation', 'getLocalListVersion', 'sendLocalList']
            ]
        ];
    }

    private function normalizeAvailabilityType(string $type): ?string
    {
        return match (strtolower(trim($type))) {
            'operative', 'available', 'online', 'enabled', 'active' => 'Operative',
            'inoperative', 'unavailable', 'offline', 'disabled', 'inactive' => 'Inoperative',
            default => null,
        };
    }

    private function normalizeResetType(string $type): ?string
    {
        return match (strtolower(trim($type))) {
            'soft', 'reboot' => 'Soft',
            'hard' => 'Hard',
            default => null,
        };
    }

    /**
     * Normalize list-like SteVe payloads while tolerating paginated/enveloped
     * variants used by forks.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        foreach (['data', 'items', 'content', 'transactions', 'connectors'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->normalizeRows($payload[$key]);
            }
        }

        return [];
    }

    /**
     * Normalize SteVe charge-point list responses. Official SteVe returns a
     * list, but forks/proxies sometimes wrap it in data/items/content or return
     * the single matching row directly.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeChargePointRows(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        if (isset($payload['chargeBoxId'])) {
            return [$payload];
        }

        foreach (['data', 'items', 'content', 'results', 'chargePoints', 'charge_points', 'chargePoint', 'charge_point'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->normalizeChargePointRows($payload[$key]);
            }
        }

        return [];
    }

    private function rowConnectorId(array $row): ?int
    {
        $value = $row['connectorId'] ?? $row['connector_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<int, array<string, mixed>> $activeTransactions
     * @param array<int, array<string, mixed>> $connectors
     * @return array{status:string, available:int, charging:int, faulted:int}
     */
    private function summarizeRealtimeStatus(array $activeTransactions, array $connectors, mixed $online): array
    {
        $available = 0;
        $charging = count($activeTransactions);
        $faulted = 0;

        foreach ($connectors as $connector) {
            $status = strtolower((string) ($connector['status'] ?? ''));
            if ($status === 'available') {
                $available++;
            }
            if (in_array($status, ['charging', 'occupied', 'preparing', 'suspendedev', 'suspendedevse', 'finishing'], true)) {
                $charging++;
            }
            if (in_array($status, ['faulted', 'unavailable'], true)) {
                $faulted++;
            }
        }

        $status = match (true) {
            count($activeTransactions) > 0 || $charging > 0 => 'Charging',
            $online === false => 'Offline',
            $faulted > 0 => 'Faulted',
            $available > 0 => 'Available',
            $connectors !== [] => (string) ($connectors[0]['status'] ?? 'Unknown'),
            default => 'Idle',
        };

        return [
            'status'    => $status,
            'available' => $available,
            'charging'  => $charging,
            'faulted'   => $faulted,
        ];
    }

    /**
     * Build an endpoint relative to the configured SteVe base URL.
     *
     * Aligned to SteVe REST: API controllers live under `/api/v1/*`.
     * We accept any of the following shapes so deployments
     * with subtly different base URLs all resolve to the same endpoint:
     *
     *  - STEVE_API_URL=http://host:8180/steve                  -> adds /api/v1
     *  - STEVE_API_URL=http://host:8180/steve/manager          -> adds /api/v1
     *  - STEVE_API_URL=http://host:8180/steve/manager/api/v1   -> adds nothing
     *  - STEVE_API_URL=http://host:8180/steve/api/v1           -> adds nothing
     */
    protected function apiPath(string $path): string
    {
        $basePath = rtrim(parse_url($this->baseUrl, PHP_URL_PATH) ?: '', '/');

        $prefix = match (true) {
            str_ends_with($basePath, '/manager/api/v1') => '',
            str_ends_with($basePath, '/manager')        => '/api/v1',
            str_ends_with($basePath, '/api/v1')         => '',
            default                                     => '/api/v1',
        };

        return $prefix . '/' . ltrim($path, '/');
    }

    protected function buildRequestUrl(string $endpoint): string
    {
        if (preg_match('#^https?://#i', $endpoint)) {
            return $endpoint;
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    protected function rootApiUrl(string $path): string
    {
        $parts = parse_url($this->baseUrl);
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}/api/v1/" . ltrim($path, '/');
    }

    protected function shouldTryRootApiFallback(array $result): bool
    {
        $error = (string) ($result['error'] ?? '');

        return str_contains($error, 'HTTP 404');
    }

    /**
     * SteVe expects repeated query keys for list filters, e.g.
     * chargeBoxId=A&chargeBoxId=B, not PHP's chargeBoxId[0]=A format.
     */
    protected function buildQueryString(array $filters): string
    {
        $parts = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            foreach ((array) $value as $item) {
                if ($item === null || $item === '') {
                    continue;
                }

                $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }
}
