<?php

declare(strict_types=1);

namespace App\Http\Controllers\Steve;

use App\Exceptions\SteVeConfigurationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Steve\Concerns\RespondsWithSteveEnvelope;
use App\Http\Requests\Steve\ChargePoint\StoreChargePointRequest;
use App\Http\Requests\Steve\ChargePoint\UpdateChargePointRequest;
use App\Http\Responses\ApiEnvelope;
use App\Services\SteVeHttpClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * BFF for SteVe's `/manager/api/v1/chargePoints` resource.
 *
 * Routes (all under /api/v1/steve, auth:sanctum gated):
 *   GET    /charge-points                       → index
 *   POST   /charge-points                       → store
 *   GET    /charge-points/{pk}                  → show
 *   PUT    /charge-points/{pk}                  → update
 *   DELETE /charge-points/{pk}                  → destroy
 *   GET    /charge-points/{pk}/status           → status        (composite: online flag + connectors)
 *   GET    /charge-points/{pk}/transactions     → transactions  (filterable by type, period)
 *   POST   /charge-points/batch/show            → batchShow     (parallel GET fan-out)
 *   PUT    /charge-points/batch                 → batchUpdate   (parallel PUT fan-out)
 *   POST   /charge-points/batch/delete          → batchDestroy  (parallel DELETE fan-out)
 *
 * Batch endpoints use POST/PUT for body-bearing semantics; they fan out to
 * SteVe via Http::pool() and return per-PK results in the response data map.
 */
class ChargePointController extends Controller
{
    use RespondsWithSteveEnvelope;

    private const ALLOWED_OCPP_VERSIONS    = ['V_12', 'V_15', 'V_16'];
    private const ALLOWED_HEARTBEAT_PERIOD = ['ALL', 'TODAY', 'YESTERDAY', 'EARLIER'];

    public function __construct(
        private readonly SteVeHttpClientService $steve,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->validate([
                'chargeBoxId'     => ['sometimes', 'string', 'max:255'],
                'description'     => ['sometimes', 'string', 'max:500'],
                'note'            => ['sometimes', 'string'],
                'ocppVersion'     => ['sometimes', 'string', 'in:' . implode(',', self::ALLOWED_OCPP_VERSIONS)],
                'heartbeatPeriod' => ['sometimes', 'string', 'in:' . implode(',', self::ALLOWED_HEARTBEAT_PERIOD)],
            ]);

            $result = $this->steve->listChargePoints($filters);

            return $this->respondFromServiceResult(
                $result,
                $result['message'] ?? 'Liste des points de charge récupérée',
                ['count' => $result['count'] ?? 0],
            );
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('index', $e, 'charge_points_list_failed');
        }
    }

    public function store(StoreChargePointRequest $request): JsonResponse
    {
        try {
            $result = $this->steve->createChargePoint($request->validated());

            if (($result['success'] ?? false) === true) {
                return response()->json(
                    ApiEnvelope::ok(
                        $result['message'] ?? 'Point de charge créé avec succès',
                        $result['data'] ?? null,
                    ),
                    201,
                );
            }

            return $this->respondFromServiceResult($result);
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('store', $e, 'charge_point_create_failed');
        }
    }

    public function show(int $chargePointPk): JsonResponse
    {
        try {
            return $this->respondFromServiceResult($this->steve->getChargePoint($chargePointPk));
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('show', $e, 'charge_point_show_failed', $chargePointPk);
        }
    }

    public function update(UpdateChargePointRequest $request, int $chargePointPk): JsonResponse
    {
        try {
            return $this->respondFromServiceResult(
                $this->steve->updateChargePoint($chargePointPk, $request->validated()),
            );
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('update', $e, 'charge_point_update_failed', $chargePointPk);
        }
    }

    public function destroy(int $chargePointPk): JsonResponse
    {
        try {
            return $this->respondFromServiceResult($this->steve->deleteChargePoint($chargePointPk));
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('destroy', $e, 'charge_point_delete_failed', $chargePointPk);
        }
    }

    /**
     * GET /charge-points/{pk}/status
     *
     * Composite status view: resolves the PK to chargeBoxId, then hits
     * /connectors/status to surface SteVe's `online` flag plus the per-connector
     * status list (Available / Charging / Faulted / ...). This is the canonical
     * answer to "is this charge point reachable right now?".
     */
    public function status(Request $request, int $chargePointPk): JsonResponse
    {
        try {
            $filters = $request->validate([
                'connectorId'   => ['sometimes', 'integer', 'min:0'],
                'ocppIdTag'     => ['sometimes', 'nullable', 'string', 'max:20'],
                'useDefaultTag' => ['sometimes', 'boolean'],
            ]);

            $cp = $this->steve->getChargePoint($chargePointPk);
            if (($cp['success'] ?? false) !== true) {
                return $this->respondFromServiceResult($cp);
            }

            $chargeBoxId = $cp['data']['chargeBoxId'] ?? null;
            if (!is_string($chargeBoxId) || $chargeBoxId === '') {
                return response()->json(
                    ApiEnvelope::error(
                        'chargeBoxId manquant pour ce point de charge',
                        'charge_point_misconfigured',
                    ),
                    422,
                );
            }

            $ocppIdTag = $filters['ocppIdTag'] ?? null;
            if (($filters['useDefaultTag'] ?? false) && empty($ocppIdTag)) {
                $ocppIdTag = config('steve.default_id_tag');
            }

            $status = $this->steve->getRealtimeChargePointStatus(
                $chargeBoxId,
                isset($filters['connectorId']) ? (int) $filters['connectorId'] : null,
                is_string($ocppIdTag) && trim($ocppIdTag) !== '' ? trim($ocppIdTag) : null,
            );
            if (($status['success'] ?? false) !== true) {
                return $this->respondFromServiceResult($status);
            }

            $data = is_array($status['data'] ?? null) ? $status['data'] : [];

            return response()->json(
                ApiEnvelope::ok(
                    $status['message'] ?? 'Statut temps-réel récupéré',
                    array_merge($data, ['chargeBoxPk' => $chargePointPk]),
                    [
                        'count'              => $status['count'] ?? count($data['connectors'] ?? []),
                        'available'          => $status['available'] ?? 0,
                        'charging'           => $status['charging'] ?? 0,
                        'faulted'            => $status['faulted'] ?? 0,
                        'activeTransactions' => $status['activeTransactions'] ?? count($data['activeTransactions'] ?? []),
                    ],
                ),
                200,
            );
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('status', $e, 'charge_point_status_failed', $chargePointPk);
        }
    }

    /**
     * GET /charge-points/{pk}/transactions
     *
     * Convenience endpoint that resolves the PK to chargeBoxId and lists
     * transactions filtered to that charge box. Accepts the same documented
     * filters as /manager/api/v1/transactions (type, periodType, from/to,
     * ocppIdTag) so callers can scope to active vs. stopped sessions.
     */
    public function transactions(Request $request, int $chargePointPk): JsonResponse
    {
        try {
            $filters = $request->validate([
                'type'       => ['sometimes', 'string', 'in:ACTIVE,STOPPED,ALL'],
                'periodType' => ['sometimes', 'string', 'in:ALL,TODAY,LAST_10,LAST_30,LAST_90,FROM_TO'],
                'from'       => ['sometimes', 'date'],
                'to'         => ['sometimes', 'date'],
                'ocppIdTag'  => ['sometimes', 'string', 'max:20'],
            ]);

            $cp = $this->steve->getChargePoint($chargePointPk);
            if (($cp['success'] ?? false) !== true) {
                return $this->respondFromServiceResult($cp);
            }

            $chargeBoxId = $cp['data']['chargeBoxId'] ?? null;
            if (!is_string($chargeBoxId) || $chargeBoxId === '') {
                return response()->json(
                    ApiEnvelope::error(
                        'chargeBoxId manquant pour ce point de charge',
                        'charge_point_misconfigured',
                    ),
                    422,
                );
            }

            $filters['chargeBoxId'] = $chargeBoxId;
            $result = $this->steve->getTransactions($filters);

            return $this->respondFromServiceResult(
                $result,
                $result['message'] ?? 'Historique des transactions récupéré',
                [
                    'chargeBoxPk' => $chargePointPk,
                    'chargeBoxId' => $chargeBoxId,
                    'count'       => $result['count'] ?? 0,
                ],
            );
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('transactions', $e, 'charge_point_transactions_failed', $chargePointPk);
        }
    }

    /**
     * POST /charge-points/batch/show
     * Body: { "chargePointPks": [1,2,3,...] }
     *
     * Fan out N concurrent GET /chargePoints/{pk} calls to SteVe and return
     * per-PK rows in the response data map. 207-style partial success is
     * represented inside a single 200 envelope; clients inspect `meta.failures`
     * and the `errors` map to detect rejected items.
     */
    public function batchShow(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->batchValidationRules());
            $result = $this->steve->batchGetChargePoints($validated['chargePointPks']);
            return $this->respondFromBatchResult($result, 'Lecture parallèle des points de charge');
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('batchShow', $e, 'charge_points_batch_show_failed');
        }
    }

    /**
     * PUT /charge-points/batch
     * Body: { "items": [ { "chargePointPk": 1, "data": { ChargePointForm } }, ... ] }
     *
     * Fan out N concurrent PUT /chargePoints/{pk} calls and report per-PK
     * outcomes. Useful for bulk metadata updates without N round-trips from
     * the client.
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'items'                       => ['required', 'array', 'min:1', 'max:50'],
                'items.*.chargePointPk'       => ['required', 'integer', 'min:1'],
                'items.*.data'                => ['required', 'array'],
                'items.*.data.chargeBoxId'    => ['sometimes', 'string', 'max:255'],
                'items.*.data.description'    => ['sometimes', 'nullable', 'string', 'max:500'],
                'items.*.data.note'           => ['sometimes', 'nullable', 'string'],
                'items.*.data.ocppProtocol'   => ['sometimes', 'string'],
                'items.*.data.registrationStatus' => ['sometimes', 'string'],
            ]);

            $bodiesByPk = [];
            foreach ($validated['items'] as $item) {
                $bodiesByPk[(int) $item['chargePointPk']] = $item['data'];
            }

            $result = $this->steve->batchUpdateChargePoints($bodiesByPk);
            return $this->respondFromBatchResult($result, 'Mise à jour parallèle des points de charge');
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('batchUpdate', $e, 'charge_points_batch_update_failed');
        }
    }

    /**
     * POST /charge-points/batch/delete
     * Body: { "chargePointPks": [1,2,3,...] }
     *
     * Destructive — deletes related transactions/reservations/connector data
     * per the SteVe spec for each item.
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->batchValidationRules());
            $result = $this->steve->batchDeleteChargePoints($validated['chargePointPks']);
            return $this->respondFromBatchResult($result, 'Suppression parallèle des points de charge');
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('batchDestroy', $e, 'charge_points_batch_delete_failed');
        }
    }

    private function batchValidationRules(): array
    {
        return [
            'chargePointPks'   => ['required', 'array', 'min:1', 'max:50'],
            'chargePointPks.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Translate a batch envelope from SteVeHttpClientService into a JsonResponse.
     *
     * Batch is always reported under HTTP 200; clients use meta.failures > 0
     * and the `errors` map to detect partial success. The single exception
     * is `batch_size_exceeded` which goes out as 422.
     */
    private function respondFromBatchResult(array $result, string $okMessage): JsonResponse
    {
        if (($result['error'] ?? null) === 'batch_size_exceeded') {
            return response()->json(
                ApiEnvelope::error($result['message'], 'batch_size_exceeded'),
                422,
            );
        }

        $meta = [
            'count'    => $result['count'] ?? 0,
            'failures' => $result['failures'] ?? 0,
        ];

        // We surface both `data` and `errors` even on full success so clients
        // get a consistent shape across the 0/some/all-failed dimensions.
        $payload = [
            'items'  => $result['data']   ?? [],
            'errors' => $result['errors'] ?? [],
        ];

        return response()->json(
            ApiEnvelope::ok($result['message'] ?? $okMessage, $payload, $meta),
            200,
        );
    }

    private function logAndFail(string $action, Throwable $e, string $code, int|string|null $id = null): JsonResponse
    {
        Log::error("Steve\\ChargePointController: {$action} failed", [
            'id'    => $id,
            'error' => $e->getMessage(),
        ]);

        return $this->serverErrorResponse('Erreur SteVe (' . $action . ')', $code);
    }
}
