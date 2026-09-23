<?php

declare(strict_types=1);

namespace App\Http\Controllers\Steve;

use App\Exceptions\SteVeConfigurationException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Steve\Concerns\RespondsWithSteveEnvelope;
use App\Services\SteVeHttpClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * BFF for SteVe's `/manager/api/v1/connectors` resource.
 *
 * Routes (all under /api/v1/steve, auth:sanctum gated):
 *   GET /connectors?chargeBoxId=...    → index
 *   GET /connectors/all                → all
 *   GET /connectors/status?chargeBoxId=...   → status (returns `online` flag too)
 */
class ConnectorController extends Controller
{
    use RespondsWithSteveEnvelope;

    public function __construct(
        private readonly SteVeHttpClientService $steve,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'chargeBoxId' => ['required', 'string', 'max:255'],
            ]);

            $result = $this->steve->listConnectors($validated['chargeBoxId']);

            return $this->respondFromServiceResult(
                $result,
                $result['message'] ?? 'Connecteurs récupérés',
                ['count' => $result['count'] ?? 0, 'chargeBoxId' => $validated['chargeBoxId']],
            );
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('index', $e, 'connectors_list_failed');
        }
    }

    public function all(): JsonResponse
    {
        try {
            $result = $this->steve->listAllConnectors();

            return $this->respondFromServiceResult(
                $result,
                $result['message'] ?? 'Tous les connecteurs récupérés',
                ['count' => $result['count'] ?? 0],
            );
        } catch (SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('all', $e, 'connectors_list_all_failed');
        }
    }

    public function status(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'chargeBoxId'   => ['required', 'string', 'max:255'],
                'connectorId'   => ['sometimes', 'integer', 'min:0'],
                'ocppIdTag'     => ['sometimes', 'nullable', 'string', 'max:20'],
                'useDefaultTag' => ['sometimes', 'boolean'],
            ]);

            $ocppIdTag = $validated['ocppIdTag'] ?? null;
            if (($validated['useDefaultTag'] ?? false) && empty($ocppIdTag)) {
                $ocppIdTag = config('steve.default_id_tag');
            }

            $result = $this->steve->getRealtimeChargePointStatus(
                $validated['chargeBoxId'],
                isset($validated['connectorId']) ? (int) $validated['connectorId'] : null,
                is_string($ocppIdTag) && trim($ocppIdTag) !== '' ? trim($ocppIdTag) : null,
            );

            $online = null;
            if (($result['success'] ?? false) === true && is_array($result['data'] ?? null)) {
                $online = $result['data']['online'] ?? null;
            }

            return $this->respondFromServiceResult(
                $result,
                $result['message'] ?? 'Statut des connecteurs récupéré',
                [
                    'chargeBoxId'        => $validated['chargeBoxId'],
                    'online'             => $online,
                    'status'             => $result['data']['status'] ?? null,
                    'count'              => $result['count'] ?? 0,
                    'available'          => $result['available'] ?? 0,
                    'charging'           => $result['charging'] ?? 0,
                    'faulted'            => $result['faulted'] ?? 0,
                    'activeTransactions' => $result['activeTransactions'] ?? 0,
                ],
            );
        } catch (ValidationException|SteVeConfigurationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->logAndFail('status', $e, 'connectors_status_failed');
        }
    }

    private function logAndFail(string $action, Throwable $e, string $code): JsonResponse
    {
        Log::error("Steve\\ConnectorController: {$action} failed", [
            'error' => $e->getMessage(),
        ]);

        return $this->serverErrorResponse('Erreur SteVe (' . $action . ')', $code);
    }
}
