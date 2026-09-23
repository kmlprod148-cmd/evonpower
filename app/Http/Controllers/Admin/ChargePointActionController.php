<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargePointCommand;
use App\Models\ChargingPoint;
use App\Services\ChargingPointCrudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin controller for OCPP remote commands on charge points.
 *
 * Every action calls the Steve API **synchronously** through
 * ChargingPointCrudService → SteVeHttpClientService and returns
 * Steve's own response to the caller.  No queue jobs are used here,
 * so operators see the station's Accepted/Rejected/Faulted result
 * in real time.
 *
 * Routes (all under /admin/charge-points/{chargingPoint}/…):
 *   POST  /remote-start        — RemoteStartTransaction
 *   POST  /remote-stop         — RemoteStopTransaction
 *   POST  /reset               — Reset (Soft or Hard)
 *   POST  /reboot              — Reboot alias (Soft Reset)
 *   POST  /availability        — ChangeAvailability
 *   POST  /unlock              — UnlockConnector
 *   POST  /lock                — Lock connector (ChangeAvailability → Inoperative)
 *   POST  /clear-cache         — ClearCache
 *   GET   /status              — Real-time connector status (polling)
 *   GET   /commands            — Last 10 command log entries
 */
class ChargePointActionController extends Controller
{
    public function __construct(
        private readonly ChargingPointCrudService $crudService,
    ) {}

    // -------------------------------------------------------------------------
    // Remote Start Transaction
    // POST /admin/charge-points/{chargingPoint}/remote-start
    // -------------------------------------------------------------------------

    public function remoteStart(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'connector_id'        => 'required|integer|min:1',
            'id_tag'              => 'nullable|string|max:20',
            'charging_profile_pk' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $params = [
            'connector_id'        => (int) $request->connector_id,
            'id_tag'              => $request->id_tag ?? config('steve.default_id_tag', ''),
        ];

        if ($request->filled('charging_profile_pk')) {
            $params['chargingProfilePk'] = (int) $request->charging_profile_pk;
        }

        $result = $this->crudService->remoteStart($chargingPoint, $params);

        $this->logCommand('RemoteStart', $chargingPoint, $params, $result);

        return $this->steveResponse($result);
    }

    // -------------------------------------------------------------------------
    // Remote Stop Transaction
    // POST /admin/charge-points/{chargingPoint}/remote-stop
    // -------------------------------------------------------------------------

    public function remoteStop(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'transaction_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $result = $this->crudService->remoteStop($chargingPoint, (int) $request->transaction_id);

        $this->logCommand('RemoteStop', $chargingPoint, ['transaction_id' => $request->transaction_id], $result);

        return $this->steveResponse($result);
    }

    // -------------------------------------------------------------------------
    // Reset / Reboot
    // POST /admin/charge-points/{chargingPoint}/reset
    // POST /admin/charge-points/{chargingPoint}/reboot
    // -------------------------------------------------------------------------

    public function reset(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'type' => 'nullable|string|in:Soft,Hard',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $type = $request->input('type', 'Soft');

        // Hard reset is restricted to admins
        if (strtolower($type) === 'hard' && !auth()->user()?->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Hard Reset is restricted to administrators.',
            ], 403);
        }

        $result = $this->crudService->reset($chargingPoint, $type);

        $this->logCommand('Reset', $chargingPoint, ['type' => $type], $result);

        return $this->steveResponse($result);
    }

    public function reboot(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $type   = $request->input('type', 'Soft');
        $result = $this->crudService->reboot($chargingPoint, $type);

        $this->logCommand('Reboot', $chargingPoint, ['type' => $type], $result);

        return $this->steveResponse($result);
    }

    // -------------------------------------------------------------------------
    // Change Availability (lock / block)
    // POST /admin/charge-points/{chargingPoint}/availability
    // POST /admin/charge-points/{chargingPoint}/lock
    // -------------------------------------------------------------------------

    public function changeAvailability(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'connector_id' => 'nullable|integer|min:0',
            'type'         => 'required|string|in:Operative,Inoperative',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $connectorId = (int) ($request->connector_id ?? 0);
        $result = $this->crudService->changeAvailability($chargingPoint, $connectorId, $request->type);

        $this->logCommand('ChangeAvailability', $chargingPoint, [
            'connector_id' => $connectorId,
            'type'         => $request->type,
        ], $result);

        return $this->steveResponse($result);
    }

    public function lock(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $connectorId = (int) ($request->input('connector_id', 0));
        $result      = $this->crudService->lockConnector($chargingPoint, $connectorId);

        $this->logCommand('Lock', $chargingPoint, ['connector_id' => $connectorId], $result);

        return $this->steveResponse($result);
    }

    // -------------------------------------------------------------------------
    // Unlock Connector
    // POST /admin/charge-points/{chargingPoint}/unlock
    // -------------------------------------------------------------------------

    public function unlockConnector(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'connector_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->validationError($v->errors());
        }

        $result = $this->crudService->unlockConnector($chargingPoint, (int) $request->connector_id);

        $this->logCommand('UnlockConnector', $chargingPoint, ['connector_id' => $request->connector_id], $result);

        return $this->steveResponse($result);
    }

    // -------------------------------------------------------------------------
    // Clear Cache
    // POST /admin/charge-points/{chargingPoint}/clear-cache
    // -------------------------------------------------------------------------

    public function clearCache(ChargingPoint $chargingPoint): JsonResponse
    {
        $result = $this->crudService->clearCache($chargingPoint);

        $this->logCommand('ClearCache', $chargingPoint, [], $result);

        return $this->steveResponse($result);
    }

    // -------------------------------------------------------------------------
    // Live Real-time Status (polling endpoint)
    // GET /admin/charge-points/{chargingPoint}/status
    // -------------------------------------------------------------------------

    public function liveStatus(Request $request, ChargingPoint $chargingPoint): JsonResponse
    {
        $connectorId = $request->filled('connector_id') ? (int) $request->connector_id : null;
        $idTag       = $request->input('id_tag') ?: null;

        $result = $this->crudService->getRealtimeStatusForModel($chargingPoint, $connectorId, $idTag);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 502);
    }

    // -------------------------------------------------------------------------
    // Command history (lightweight audit log)
    // GET /admin/charge-points/{chargingPoint}/commands
    // -------------------------------------------------------------------------

    public function getCommands(ChargingPoint $chargingPoint): JsonResponse
    {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        $commands = ChargePointCommand::query()
            ->where('charge_box_id', $chargeBoxId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $commands]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Build a JSON response from a Steve result envelope. */
    private function steveResponse(array $result): JsonResponse
    {
        $success = $result['success'] ?? false;

        $code = match (true) {
            $success                                               => 200,
            ($result['error'] ?? '') === 'no_steve_id'            => 400,
            ($result['error'] ?? '') === 'transaction_id_required' => 400,
            ($result['error_code'] ?? '') === 'charger_not_connected' => 409,
            str_starts_with($result['error'] ?? '', 'HTTP 4')     => 400,
            default                                                => 502, // bad gateway — Steve unreachable / error
        };

        return response()->json($result, $code);
    }

    private function validationError(mixed $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $errors,
        ], 422);
    }

    private function logCommand(
        string $command,
        ChargingPoint $cp,
        array $params,
        array $result
    ): void {
        $level   = ($result['success'] ?? false) ? 'info' : 'warning';
        $context = [
            'command'           => $command,
            'charging_point_id' => $cp->id,
            'charge_box_id'     => $cp->charge_box_id ?? $cp->steve_charging_point_id,
            'actor_id'          => auth()->id(),
            'params'            => $params,
            'steve_result'      => $result,
        ];

        Log::$level("ChargePointActionController: {$command}", $context);

        // Persist to ChargePointCommand table if model exists
        try {
            ChargePointCommand::create([
                'charge_box_id'   => $cp->charge_box_id ?? $cp->steve_charging_point_id,
                'command'         => $command,
                'params'          => json_encode($params),
                'status'          => ($result['success'] ?? false) ? 'success' : 'failed',
                'response'        => json_encode($result),
                'initiated_by'    => auth()->id(),
            ]);
        } catch (\Exception $e) {
            // Non-fatal — command log is best-effort
            Log::debug('ChargePointActionController: Failed to persist command log', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
