<?php

namespace App\Services;

use App\Exceptions\SteVeConfigurationException;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\PricingPlan;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ChargingPointCrudService
 *
 * Single-responsibility service that:
 *  1. Creates / updates / deletes ChargingPoint rows in the local DB.
 *  2. Keeps the corresponding chargeBox record in Steve in sync.
 *  3. Provides real-time status by querying Steve's transactions and
 *     connectors endpoints (via SteVeHttpClientService::getRealtimeChargePointStatus).
 *  4. Dispatches all remote OCPP actions (start / stop / reset / reboot /
 *     unlock / lock / clearCache) directly to the Steve API — no queued jobs,
 *     so callers get a synchronous response.
 *
 * Why bypass the job queue for remote actions?
 *   SteVe processes OCPP commands synchronously and returns the station's
 *   response (Accepted / Rejected / …) in the HTTP reply. Queuing adds latency
 *   without benefit and makes it impossible to surface Steve's response to the
 *   operator in real time.
 */
class ChargingPointCrudService
{
    public function __construct(
        private readonly SteVeHttpClientService $steve,
    ) {}

    // =========================================================================
    // CRUD — LOCAL + STEVE
    // =========================================================================

    /**
     * Create a charging point locally and register it on Steve.
     *
     * @param  array  $validated  Already-validated request data.
     * @param  User   $actor      The authenticated user performing the action.
     * @return array{success:bool, charging_point?:ChargingPoint, steve_synced:bool, message:string, error?:string}
     */
    public function create(array $validated, User $actor): array
    {
        DB::beginTransaction();
        try {
            // ------------------------------------------------------------------
            // 1. Build local model data
            // ------------------------------------------------------------------
            $chargeBoxId = $validated['charge_box_id']
                ?? ($validated['serial_number']
                    ? 'CP-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $validated['serial_number']))
                    : 'CP-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)));

            $localData = array_merge($validated, [
                'charge_box_id'         => $chargeBoxId,
                'steve_charging_point_id' => $chargeBoxId,
                'created_by'            => $actor->id,
                'created_by_id'         => $actor->id,
                'created_by_type'       => get_class($actor),
            ]);

            $chargingPoint = ChargingPoint::create($localData);

            DB::commit();

            // ------------------------------------------------------------------
            // 2. Register on Steve (non-blocking: if it fails, local row exists)
            // ------------------------------------------------------------------
            $steveSynced = false;
            $steveError  = null;
            $steveAdopted = false;

            try {
                $stevePayload = $this->buildStevePayload($chargingPoint);
                $existing = $this->steve->findChargePointByChargeBoxId($chargeBoxId);

                if ($existing !== null && $this->applySteveProvisioningResult($chargingPoint, $existing, adopted: true)) {
                    $steveSynced = true;
                    $steveAdopted = true;
                } else {
                    $steveResult = $this->steve->createChargePoint($stevePayload);

                    if ($steveResult['success'] ?? false) {
                        $steveSynced = $this->applySteveProvisioningResult($chargingPoint, $steveResult['data'] ?? []);
                        if (!$steveSynced) {
                            $steveError = 'Steve create response did not include chargeBoxPk.';
                        }
                    } else {
                        $raced = $this->steve->findChargePointByChargeBoxId($chargeBoxId);
                        if ($raced !== null && $this->applySteveProvisioningResult($chargingPoint, $raced, adopted: true)) {
                            $steveSynced = true;
                            $steveAdopted = true;
                        } else {
                            $steveError = $steveResult['error'] ?? $steveResult['message'] ?? 'Unknown Steve error';
                            Log::warning('ChargingPointCrudService: Steve registration failed', [
                                'charging_point_id' => $chargingPoint->id,
                                'charge_box_id'     => $chargeBoxId,
                                'error'             => $steveError,
                            ]);
                        }
                    }
                }
            } catch (SteVeConfigurationException $e) {
                $steveError = 'Steve not configured: ' . $e->getMessage();
                Log::warning('ChargingPointCrudService: Steve not configured', ['error' => $e->getMessage()]);
            } catch (Exception $e) {
                $steveError = $e->getMessage();
                Log::warning('ChargingPointCrudService: Steve registration exception', ['error' => $e->getMessage()]);
            }

            return [
                'success'        => true,
                'charging_point' => $chargingPoint->fresh(),
                'steve_synced'   => $steveSynced,
                'steve_error'    => $steveError,
                'message'        => $steveSynced
                    ? ($steveAdopted
                        ? 'Charging point created locally and linked to existing Steve charge point.'
                        : 'Charging point created and registered on Steve.')
                    : 'Charging point created locally. Steve registration failed: ' . $steveError,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ChargingPointCrudService: create failed', ['error' => $e->getMessage()]);

            return [
                'success'      => false,
                'steve_synced' => false,
                'error'        => $e->getMessage(),
                'message'      => 'Failed to create charging point: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update a charging point locally and sync the changes to Steve.
     *
     * @param  array  $validated
     * @return array{success:bool, charging_point?:ChargingPoint, steve_synced:bool, message:string}
     */
    public function update(ChargingPoint $chargingPoint, array $validated): array
    {
        DB::beginTransaction();
        try {
            $chargingPoint->update($validated);
            DB::commit();

            // Sync to Steve only when the CP has a known chargeBoxPk
            $steveSynced = false;
            $steveError  = null;

            if ($chargingPoint->isProvisionedOnSteve()) {
                try {
                    $stevePayload = $this->buildStevePayload($chargingPoint);
                    $steveResult  = $this->steve->updateChargePoint(
                        $chargingPoint->steve_charge_box_pk,
                        $stevePayload
                    );

                    $steveSynced = $steveResult['success'] ?? false;
                    if (!$steveSynced) {
                        $steveError = $steveResult['error'] ?? $steveResult['message'] ?? 'Unknown';
                    }
                } catch (SteVeConfigurationException $e) {
                    $steveError = 'Steve not configured: ' . $e->getMessage();
                } catch (Exception $e) {
                    $steveError = $e->getMessage();
                }
            }

            return [
                'success'        => true,
                'charging_point' => $chargingPoint->fresh(),
                'steve_synced'   => $steveSynced,
                'steve_error'    => $steveError,
                'message'        => $steveSynced
                    ? 'Charging point updated and synced to Steve.'
                    : 'Charging point updated locally.' . ($steveError ? ' Steve sync failed: ' . $steveError : ''),
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('ChargingPointCrudService: update failed', ['error' => $e->getMessage()]);

            return [
                'success'      => false,
                'steve_synced' => false,
                'error'        => $e->getMessage(),
                'message'      => 'Failed to update charging point: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a charging point locally and unregister it from Steve.
     *
     * @param  bool  $forceDeleteSteve  When true, local record is always deleted
     *                                  even if Steve removal fails.
     */
    public function delete(ChargingPoint $chargingPoint, bool $forceDeleteSteve = false): array
    {
        $steveSynced = false;
        $steveError  = null;

        // Try to remove from Steve first so we still have the local ID
        if ($chargingPoint->isProvisionedOnSteve()) {
            try {
                $steveResult = $this->steve->deleteChargePoint($chargingPoint->steve_charge_box_pk);
                $steveSynced = $steveResult['success'] ?? false;

                if (!$steveSynced && !$forceDeleteSteve) {
                    return [
                        'success'      => false,
                        'steve_synced' => false,
                        'error'        => $steveResult['error'] ?? 'Steve deletion failed',
                        'message'      => 'Steve deletion failed. Use force_delete=true to delete locally only.',
                    ];
                }

                if (!$steveSynced) {
                    $steveError = $steveResult['error'] ?? 'Steve deletion failed';
                }
            } catch (SteVeConfigurationException $e) {
                $steveError = 'Steve not configured: ' . $e->getMessage();
                if (!$forceDeleteSteve) {
                    return [
                        'success'      => false,
                        'steve_synced' => false,
                        'error'        => $steveError,
                        'message'      => $steveError,
                    ];
                }
            } catch (Exception $e) {
                $steveError = $e->getMessage();
                if (!$forceDeleteSteve) {
                    return [
                        'success'      => false,
                        'steve_synced' => false,
                        'error'        => $steveError,
                        'message'      => 'Exception during Steve deletion. Use force_delete=true to skip.',
                    ];
                }
            }
        }

        try {
            $chargingPoint->delete();

            return [
                'success'      => true,
                'steve_synced' => $steveSynced,
                'steve_error'  => $steveError,
                'message'      => $steveSynced
                    ? 'Charging point deleted locally and from Steve.'
                    : 'Charging point deleted locally.' . ($steveError ? ' Steve error: ' . $steveError : ''),
            ];
        } catch (Exception $e) {
            return [
                'success'      => false,
                'steve_synced' => $steveSynced,
                'error'        => $e->getMessage(),
                'message'      => 'Failed to delete local charging point: ' . $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // REAL-TIME STATUS
    // =========================================================================

    /**
     * Retrieve real-time status from Steve for a given charging point.
     *
     * Strategy (per Steve OpenAPI spec):
     *  1. Query GET /api/v1/transactions?type=ACTIVE&chargeBoxId=<id>
     *     — gives active sessions (the authoritative source).
     *  2. Query GET /api/v1/connectors/status?chargeBoxId=<id>
     *     — gives per-connector OCPP status + online flag.
     *  3. Synthesize a human-readable summary.
     *
     * Additionally, if an idTag is configured (STEVE_DEFAULT_ID_TAG), filter
     * the transaction list to sessions started with that tag, enabling
     * "is this tag currently charging anywhere?" queries.
     *
     * @param  string       $chargeBoxId   Steve chargeBoxId string.
     * @param  int|null     $connectorId   Optional: only return status for this connector.
     * @param  string|null  $idTag         Optional: filter by OCPP idTag.
     */
    public function getRealtimeStatus(
        string $chargeBoxId,
        ?int $connectorId = null,
        ?string $idTag = null
    ): array {
        $defaultTag = config('steve.default_id_tag');

        return $this->steve->getRealtimeChargePointStatus(
            $chargeBoxId,
            $connectorId,
            $idTag ?? (is_string($defaultTag) && trim($defaultTag) !== '' ? $defaultTag : null)
        );
    }

    /**
     * Retrieve real-time status using the local ChargingPoint model.
     * Resolves chargeBoxId automatically from the model's steve_charging_point_id
     * or charge_box_id field.
     */
    public function getRealtimeStatusForModel(
        ChargingPoint $chargingPoint,
        ?int $connectorId = null,
        ?string $idTag = null
    ): array {
        $chargeBoxId = $chargingPoint->charge_box_id
            ?? $chargingPoint->steve_charging_point_id;

        if (empty($chargeBoxId)) {
            return [
                'success' => false,
                'error'   => 'no_steve_id',
                'message' => 'Charging point has no Steve chargeBoxId configured.',
            ];
        }

        return $this->getRealtimeStatus($chargeBoxId, $connectorId, $idTag);
    }

    // =========================================================================
    // REMOTE ACTIONS — direct synchronous calls to Steve API
    // =========================================================================

    /**
     * Remote Start Transaction.
     *
     * @param  array  $params  Keys: connector_id (int), id_tag (string),
     *                               charging_profile_pk (int, optional).
     */
    public function remoteStart(ChargingPoint $chargingPoint, array $params): array
    {
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint);
        if ($chargeBoxId === null) {
            return $this->noSteveIdError();
        }

        // Fall back to the site-wide default tag when no idTag supplied
        $params['id_tag'] ??= config('steve.default_id_tag', '');

        $result = $this->steve->remoteStartTransaction($chargeBoxId, $params);

        Log::info('ChargingPointCrudService: remoteStart', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargeBoxId,
            'params'            => $params,
            'result'            => $result,
        ]);

        return $result;
    }

    /**
     * Remote Stop Transaction.
     *
     * @param  int|string  $transactionId  Steve transaction ID to stop.
     */
    public function remoteStop(ChargingPoint $chargingPoint, int|string $transactionId): array
    {
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint);
        if ($chargeBoxId === null) {
            return $this->noSteveIdError();
        }

        $result = $this->steve->remoteStopTransaction($chargeBoxId, $transactionId);

        Log::info('ChargingPointCrudService: remoteStop', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargeBoxId,
            'transaction_id'    => $transactionId,
            'result'            => $result,
        ]);

        return $result;
    }

    /**
     * Reset (reboot) a charging point.
     *
     * @param  string  $type  'Soft' (default) or 'Hard'.
     */
    public function reset(ChargingPoint $chargingPoint, string $type = 'Soft'): array
    {
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint);
        if ($chargeBoxId === null) {
            return $this->noSteveIdError();
        }

        $result = $this->steve->reset($chargeBoxId, $type);

        Log::info('ChargingPointCrudService: reset', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargeBoxId,
            'type'              => $type,
            'result'            => $result,
        ]);

        return $result;
    }

    /**
     * Reboot alias — sends a Soft Reset to Steve.
     */
    public function reboot(ChargingPoint $chargingPoint, string $type = 'Soft'): array
    {
        return $this->reset($chargingPoint, $type);
    }

    /**
     * Unlock a specific connector.
     *
     * @param  int  $connectorId  1-based connector index.
     */
    public function unlockConnector(ChargingPoint $chargingPoint, int $connectorId): array
    {
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint);
        if ($chargeBoxId === null) {
            return $this->noSteveIdError();
        }

        $result = $this->steve->unlockConnector($chargeBoxId, $connectorId);

        Log::info('ChargingPointCrudService: unlockConnector', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargeBoxId,
            'connector_id'      => $connectorId,
            'result'            => $result,
        ]);

        return $result;
    }

    /**
     * Lock a connector by setting availability to Inoperative.
     * This is the "lock" action: connector_id=0 locks the whole station.
     */
    public function lockConnector(ChargingPoint $chargingPoint, int $connectorId = 0): array
    {
        return $this->changeAvailability($chargingPoint, $connectorId, 'Inoperative');
    }

    /**
     * Change connector availability (Operative / Inoperative).
     *
     * @param  int     $connectorId  0 = whole station; ≥1 = specific connector.
     * @param  string  $type         'Operative' or 'Inoperative'.
     */
    public function changeAvailability(
        ChargingPoint $chargingPoint,
        int $connectorId,
        string $type
    ): array {
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint);
        if ($chargeBoxId === null) {
            return $this->noSteveIdError();
        }

        $result = $this->steve->changeAvailability($chargeBoxId, $connectorId, $type);

        Log::info('ChargingPointCrudService: changeAvailability', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargeBoxId,
            'connector_id'      => $connectorId,
            'type'              => $type,
            'result'            => $result,
        ]);

        return $result;
    }

    /**
     * Clear the authorization cache on the charge point.
     */
    public function clearCache(ChargingPoint $chargingPoint): array
    {
        $chargeBoxId = $this->resolveChargeBoxId($chargingPoint);
        if ($chargeBoxId === null) {
            return $this->noSteveIdError();
        }

        $result = $this->steve->clearCache($chargeBoxId);

        Log::info('ChargingPointCrudService: clearCache', [
            'charging_point_id' => $chargingPoint->id,
            'chargeBoxId'       => $chargeBoxId,
            'result'            => $result,
        ]);

        return $result;
    }

    /**
     * List all charge points registered on the Steve server.
     */
    public function listOnSteve(array $filters = []): array
    {
        return $this->steve->listChargePoints($filters);
    }

    /**
     * Fetch a single charge point from Steve by its chargeBoxPk or chargeBoxId.
     */
    public function getFromSteve(int|string $chargePoint): array
    {
        return $this->steve->getChargePoint($chargePoint);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Map a local ChargingPoint to the Steve ChargePointForm payload.
     */
    protected function buildStevePayload(ChargingPoint $cp): array
    {
        $payload = [
            'chargeBoxId'  => $cp->charge_box_id ?? $cp->steve_charging_point_id,
            'description'  => $cp->name ?? 'Charging Point #' . $cp->id,
            'note'         => $cp->notes ?? $cp->installation_notes ?? null,
        ];

        if ($cp->latitude && $cp->longitude) {
            $payload['locationLatitude']  = (float) $cp->latitude;
            $payload['locationLongitude'] = (float) $cp->longitude;
        }

        if ($cp->address || $cp->city) {
            $payload['address'] = array_filter([
                'street'      => $cp->address,
                'zipCode'     => $cp->postal_code,
                'city'        => $cp->city,
                'country'     => $cp->country ?? 'UNDEFINED',
            ]);
        }

        return $payload;
    }

    protected function applySteveProvisioningResult(ChargingPoint $chargingPoint, array $body, bool $adopted = false): bool
    {
        $stevePk = $body['chargeBoxPk'] ?? $body['chargePointPk'] ?? $body['id'] ?? null;
        if (!is_numeric($stevePk) || (int) $stevePk <= 0) {
            return false;
        }

        $chargeBoxId = $body['chargeBoxId']
            ?? $chargingPoint->charge_box_id
            ?? $chargingPoint->steve_charging_point_id
            ?? null;

        $syncedAt = now();
        $updates = [
            'steve_charge_box_pk'  => (int) $stevePk,
            'steve_provisioned_at' => $syncedAt,
            'steve_connection_status' => array_merge(
                (array) ($chargingPoint->steve_connection_status ?? []),
                [
                    'status' => 'synced',
                    'synced_at' => $syncedAt->toIso8601String(),
                    'chargeBoxPk' => (int) $stevePk,
                    'adopted' => $adopted,
                ],
            ),
        ];

        if (is_string($chargeBoxId) && trim($chargeBoxId) !== '') {
            $updates['charge_box_id'] = trim($chargeBoxId);
            $updates['steve_charging_point_id'] = trim($chargeBoxId);
        }

        $chargingPoint->updateQuietly($updates);

        return true;
    }

    /**
     * Resolve the Steve chargeBoxId string from a local model.
     * Returns null if the CP has no Steve ID at all.
     */
    protected function resolveChargeBoxId(ChargingPoint $cp): ?string
    {
        $id = $cp->charge_box_id ?? $cp->steve_charging_point_id;

        return (is_string($id) && trim($id) !== '') ? $id : null;
    }

    protected function noSteveIdError(): array
    {
        return [
            'success' => false,
            'error'   => 'no_steve_id',
            'message' => 'This charging point has no Steve chargeBoxId configured. '
                       . 'Register it on Steve first.',
        ];
    }
}
