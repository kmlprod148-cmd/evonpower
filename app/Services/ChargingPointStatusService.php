<?php

namespace App\Services;

use App\DTO\Connector\ConnectorCollectionDTO;
use App\DTO\Connector\ConnectorStatusDTO;
use App\Models\ChargingPoint;

class ChargingPointStatusService
{
    public function __construct(
        protected ConnectorService $connectorService
    ) {}

    /**
     * Get a charging point status based on connector statuses.
     */
    public function getStatusFromConnectors(ChargingPoint $chargingPoint, bool $useCache = true): array
    {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return [
                'success' => false,
                'status' => 'unknown',
                'error' => 'missing_charge_box_id',
            ];
        }

        $collection = $this->connectorService->getConnectorStatuses($chargeBoxId, $useCache);

        if (!$collection->success || $collection->isEmpty()) {
            return [
                'success' => false,
                'status' => 'unknown',
                'error' => $collection->error ?? 'connectors_unavailable',
            ];
        }

        $status = $this->resolveChargingPointStatus($collection);

        return [
            'success' => $status !== 'unknown',
            'status' => $status,
            'summary' => $collection->getStatusSummary(),
            'charge_box_id' => $chargeBoxId,
        ];
    }

    /**
     * Resolve the charging point status from connector statuses.
     */
    public function resolveChargingPointStatus(ConnectorCollectionDTO $collection): string
    {
        if ($collection->isEmpty()) {
            return 'unknown';
        }

        $hasCharging = false;
        $hasReserved = false;
        $hasAvailable = false;
        $hasSuspended = false;
        $hasFaulted = false;
        $hasUnavailable = false;

        foreach ($collection->connectors as $connector) {
            $status = $connector->status;

            if (in_array($status, [
                ConnectorStatusDTO::STATUS_CHARGING,
                ConnectorStatusDTO::STATUS_FINISHING,
                ConnectorStatusDTO::STATUS_PREPARING,
            ], true)) {
                $hasCharging = true;
                continue;
            }

            if (in_array($status, [
                ConnectorStatusDTO::STATUS_SUSPENDED_EV,
                ConnectorStatusDTO::STATUS_SUSPENDED_EVSE,
            ], true)) {
                $hasSuspended = true;
                continue;
            }

            if ($status === ConnectorStatusDTO::STATUS_RESERVED) {
                $hasReserved = true;
                continue;
            }

            if ($status === ConnectorStatusDTO::STATUS_AVAILABLE) {
                $hasAvailable = true;
                continue;
            }

            if ($status === ConnectorStatusDTO::STATUS_FAULTED) {
                $hasFaulted = true;
                continue;
            }

            if ($status === ConnectorStatusDTO::STATUS_UNAVAILABLE) {
                $hasUnavailable = true;
            }
        }

        if ($hasCharging) {
            return 'charging';
        }

        if ($hasReserved) {
            return 'reserved';
        }

        if ($hasAvailable) {
            return 'online';
        }

        if ($hasFaulted) {
            return 'error';
        }

        if ($hasSuspended) {
            return 'maintenance';
        }

        if ($hasUnavailable) {
            return 'offline';
        }

        return 'unknown';
    }

    /**
     * Get charging point availability for public payment flow
     * 
     * @param int $chargingPointId
     * @return array
     */
    public function getChargingPointAvailability(int $chargingPointId): array
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            $statusResult = $this->getStatusFromConnectors($chargingPoint);

            $isAvailable = in_array($statusResult['status'] ?? 'unknown', ['online', 'available']);
            $isCharging = $statusResult['status'] === 'charging';
            $isOffline = $statusResult['status'] === 'offline';

            return [
                'available' => $isAvailable,
                'status' => $statusResult['status'] ?? 'unknown',
                'is_charging' => $isCharging,
                'is_offline' => $isOffline,
                'message' => $isAvailable 
                    ? ' Borne disponible' 
                    : ($isCharging 
                        ? 'Borne en cours d\'utilisation' 
                        : ($isOffline 
                            ? 'Borne hors ligne' 
                            : 'Borne temporairement indisponible')),
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'status' => 'error',
                'is_charging' => false,
                'is_offline' => true,
                'message' => 'Erreur lors de la vérification de disponibilité',
            ];
        }
    }
}
