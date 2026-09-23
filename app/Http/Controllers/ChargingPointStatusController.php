<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\ChargingPointStatusService;
use App\Services\SteveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChargingPointStatusController extends Controller
{
    protected SteveService $steve;
    protected ChargingPointStatusService $statusService;

    public function __construct(SteveService $steve, ChargingPointStatusService $statusService)
    {
        $this->steve = $steve;
        $this->statusService = $statusService;
    }

    /**
     * Return JSON status for a charging point
     * GET /charging-points/{id}/status
     */
    public function status(Request $request, $id)
    {
        $cp = ChargingPoint::findOrFail($id);

        $chargeBoxId = $cp->charge_box_id ?? $cp->steve_charging_point_id;

        if (!$chargeBoxId) {
            return response()->json([
                'ok' => false,
                'message' => 'No charge box id attached',
                'status' => $cp->status ?? 'unknown',
                'updated_at' => $cp->status_updated_at
            ], 200);
        }

        // Vérifier le cache (30 secondes)
        $cacheKey = "charging_point_status_{$cp->id}";
        $cached = Cache::get($cacheKey);
        if ($cached && !$request->has('force')) {
            return response()->json($cached);
        }

        $useCache = !$request->has('force');
        $connectorStatus = $this->statusService->getStatusFromConnectors($cp, $useCache);
        if ($connectorStatus['success'] ?? false) {
            $status = $connectorStatus['status'];
            Log::info("ChargingPointStatusController: Derived status from connectors", [
                'cp_id' => $cp->id,
                'charge_box_id' => $chargeBoxId,
                'extracted_status' => $status,
                'summary' => $connectorStatus['summary'] ?? null,
            ]);

            $cp->status = $status;
            $cp->status_updated_at = now();
            $cp->saveQuietly();

            $response = [
                'ok' => true,
                'status' => $status,
                'source' => 'connectors',
                'updated_at' => $cp->status_updated_at?->toIso8601String() ?? now()->toIso8601String()
            ];

            Cache::put($cacheKey, $response, now()->addSeconds(30));

            return response()->json($response);
        }

        // If ConnectorService already received a 404 from Steve, the charge point is not
        // registered there. Skip the multi-endpoint Steve fallback cascade — it would only
        // generate more 404 calls for the same ID and add several seconds of latency.
        $connectorError = $connectorStatus['error'] ?? '';
        $steveNotFound  = str_contains($connectorError, 'HTTP 404');

        if (!$cp->steve_charging_point_id || $steveNotFound) {
            $status = $cp->status ?? 'unknown';
            if ($steveNotFound) {
                Log::warning("ChargingPointStatusController: Failed to get status for CP {$cp->id} (steve_id: {$cp->steve_charging_point_id})");
            }
        } else {
            $detail = $this->steve->getChargingPoint($cp->steve_charging_point_id);

            $status = 'unknown';
            if ($detail['ok'] && is_array($detail['body'])) {
                // Parser le statut depuis la réponse de l'API Steve
                $status = $this->extractRealStatusFromResponse($detail['body']);
                Log::info("ChargingPointStatusController: Extracted status from getChargingPoint", [
                    'cp_id' => $cp->id,
                    'steve_id' => $cp->steve_charging_point_id,
                    'extracted_status' => $status,
                    'response_keys' => array_keys($detail['body'])
                ]);
            } else {
                // Fallback: essayer connector status endpoint
                $res = $this->steve->getConnectorStatus($cp->steve_charging_point_id);
                if ($res['ok'] && is_array($res['body'])) {
                    $online = $this->deriveOnlineFromConnectorBody($res['body']);
                    $status = $online ? 'online' : 'offline';
                    Log::info("ChargingPointStatusController: Extracted status from getConnectorStatus", [
                        'cp_id' => $cp->id,
                        'steve_id' => $cp->steve_charging_point_id,
                        'extracted_status' => $status
                    ]);
                } else {
                    $status = $cp->status ?? 'unknown';
                    Log::warning("ChargingPointStatusController: Failed to get status for CP {$cp->id} (steve_id: {$cp->steve_charging_point_id})");
                }
            }
        }

        // Update DB cache
        $cp->status = $status;
        $cp->status_updated_at = now();
        $cp->saveQuietly();

        $response = [
            'ok' => true,
            'status' => $status,
            'source' => 'charging_point',
            'updated_at' => $cp->status_updated_at?->toIso8601String() ?? now()->toIso8601String()
        ];

        // Cache pour 30 secondes
        Cache::put($cacheKey, $response, now()->addSeconds(30));

        return response()->json($response);
    }

    /**
     * Derive online status from connector response body
     */
    protected function deriveOnlineFromConnectorBody(array $body): bool
    {
        // Adjust according to the real Steve response structure.
        // Example: $body['connectors'][0]['status'] == 'AVAILABLE' or 'CHARGING'
        if (isset($body['connectors']) && is_array($body['connectors'])) {
            foreach ($body['connectors'] as $c) {
                $s = strtoupper($c['status'] ?? '');
                if (in_array($s, ['AVAILABLE', 'CHARGING', 'IN_USE', 'OCCUPIED', 'ACTIVE', 'ONLINE'])) {
                    return true;
                }
            }
        }
        
        // Vérifier aussi le status global du charge point
        if (isset($body['status'])) {
            $s = strtoupper($body['status']);
            if (in_array($s, ['ONLINE', 'CONNECTED', 'ACTIVE', 'AVAILABLE'])) {
                return true;
            }
        }

        // fallback false
        return false;
    }

    /**
     * Derive online status from charging point details response body
     */
    protected function deriveOnlineFromChargingPointBody($body): bool
    {
        if (!is_array($body)) {
            return false;
        }

        // Check status field
        $s = strtoupper($body['status'] ?? ($body['availability'] ?? ''));
        if (in_array($s, ['ONLINE', 'CONNECTED', 'ACTIVE', 'AVAILABLE'])) {
            return true;
        }
        
        // Check last seen timestamp
        if (!empty($body['lastSeen']) || !empty($body['last_seen'])) {
            try {
                $lastSeen = \Carbon\Carbon::parse($body['lastSeen'] ?? $body['last_seen']);
                if ($lastSeen->gt(now()->subMinutes(5))) {
                    return true;
                }
            } catch (\Exception $e) {
                Log::debug("Failed to parse lastSeen timestamp: {$e->getMessage()}");
            }
        }

        // Check connection status
        if (isset($body['connected']) && $body['connected'] === true) {
            return true;
        }

        return false;
    }

    /**
     * Extract real status from Steve API response
     * Parse various possible status fields from the API response
     */
    protected function extractRealStatusFromResponse(array $body): string
    {
        // Essayer plusieurs champs possibles pour le statut
        $statusFields = [
            'status',
            'availabilityStatus',
            'availability_status',
            'connectionStatus',
            'connection_status',
            'state',
            'availability'
        ];

        foreach ($statusFields as $field) {
            if (isset($body[$field])) {
                $statusValue = strtolower(trim($body[$field]));
                
                // Mapper les valeurs possibles vers nos statuts
                if (in_array($statusValue, ['available', 'online', 'connected', 'active', 'ready', 'idle'])) {
                    return 'online';
                }
                if (in_array($statusValue, ['unavailable', 'offline', 'disconnected', 'inactive', 'faulted'])) {
                    return 'offline';
                }
                if (in_array($statusValue, ['preparing', 'charging', 'finishing', 'occupied'])) {
                    return 'online'; // Considérer comme online si en charge
                }
                if (in_array($statusValue, ['maintenance', 'suspendedevse', 'suspendedev'])) {
                    return 'maintenance';
                }
            }
        }

        // Vérifier les connecteurs pour déterminer le statut
        if (isset($body['connectors']) && is_array($body['connectors']) && !empty($body['connectors'])) {
            foreach ($body['connectors'] as $connector) {
                if (isset($connector['status'])) {
                    $connectorStatus = strtolower(trim($connector['status']));
                    if (in_array($connectorStatus, ['available', 'preparing', 'charging', 'finishing'])) {
                        return 'online';
                    }
                    if (in_array($connectorStatus, ['unavailable', 'faulted'])) {
                        return 'offline';
                    }
                }
            }
        }

        // Vérifier lastSeen pour déterminer si en ligne
        if (isset($body['lastSeen']) || isset($body['last_seen'])) {
            try {
                $lastSeen = \Carbon\Carbon::parse($body['lastSeen'] ?? $body['last_seen']);
                // Si vu dans les 5 dernières minutes, considérer comme online
                if ($lastSeen->gt(now()->subMinutes(5))) {
                    return 'online';
                }
                return 'offline';
            } catch (\Exception $e) {
                Log::debug("Failed to parse lastSeen timestamp: {$e->getMessage()}");
            }
        }

        // Vérifier le flag connected
        if (isset($body['connected'])) {
            return $body['connected'] === true ? 'online' : 'offline';
        }

        // Vérifier isOnline
        if (isset($body['isOnline'])) {
            return $body['isOnline'] === true ? 'online' : 'offline';
        }

        // Par défaut, utiliser deriveOnlineFromChargingPointBody
        $online = $this->deriveOnlineFromChargingPointBody($body);
        return $online ? 'online' : 'offline';
    }
}
