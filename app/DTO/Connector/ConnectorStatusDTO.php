<?php

namespace App\DTO\Connector;

use App\Core\DTOs\BaseDTO;
use Carbon\Carbon;

/**
 * DTO pour représenter le statut d'un connecteur
 * Mappe les réponses de l'endpoint /api/v1/connectors/status de SteVe
 */
class ConnectorStatusDTO extends BaseDTO
{
    // Constantes pour les statuts OCPP 1.6
    public const STATUS_AVAILABLE = 'Available';
    public const STATUS_PREPARING = 'Preparing';
    public const STATUS_CHARGING = 'Charging';
    public const STATUS_SUSPENDED_EVSE = 'SuspendedEVSE';
    public const STATUS_SUSPENDED_EV = 'SuspendedEV';
    public const STATUS_FINISHING = 'Finishing';
    public const STATUS_RESERVED = 'Reserved';
    public const STATUS_UNAVAILABLE = 'Unavailable';
    public const STATUS_FAULTED = 'Faulted';
    public const STATUS_UNKNOWN = 'Unknown';

    // Statuts valides OCPP
    public const VALID_STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_PREPARING,
        self::STATUS_CHARGING,
        self::STATUS_SUSPENDED_EVSE,
        self::STATUS_SUSPENDED_EV,
        self::STATUS_FINISHING,
        self::STATUS_RESERVED,
        self::STATUS_UNAVAILABLE,
        self::STATUS_FAULTED,
    ];

    public string $chargeBoxId;
    public int $connectorId;
    public string $status;
    public ?string $errorCode;
    public ?string $timestamp;
    public ?string $info;
    public ?string $vendorId;
    public ?string $vendorErrorCode;

    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? $data['charge_box_id'] ?? '';
        $this->connectorId = (int) ($data['connectorId'] ?? $data['connector_id'] ?? 0);
        $this->status = $this->normalizeStatus($data['status'] ?? self::STATUS_UNKNOWN);
        $this->errorCode = $data['errorCode'] ?? $data['error_code'] ?? null;
        $this->timestamp = $data['timestamp'] ?? $data['statusTimestamp'] ?? null;
        $this->info = $data['info'] ?? null;
        $this->vendorId = $data['vendorId'] ?? $data['vendor_id'] ?? null;
        $this->vendorErrorCode = $data['vendorErrorCode'] ?? $data['vendor_error_code'] ?? null;
    }

    /**
     * Crée un DTO à partir de la réponse API SteVe
     */
    public static function fromApiResponse(array $data, string $chargeBoxId = ''): self
    {
        return new self([
            'chargeBoxId' => $chargeBoxId ?: ($data['chargeBoxId'] ?? ''),
            'connectorId' => $data['connectorId'] ?? $data['id'] ?? 0,
            'status' => $data['status'] ?? self::STATUS_UNKNOWN,
            'errorCode' => $data['errorCode'] ?? null,
            'timestamp' => $data['timestamp'] ?? $data['statusTimestamp'] ?? null,
            'info' => $data['info'] ?? null,
            'vendorId' => $data['vendorId'] ?? null,
            'vendorErrorCode' => $data['vendorErrorCode'] ?? null,
        ]);
    }

    /**
     * Normalise le statut en format OCPP standard
     */
    protected function normalizeStatus(string $status): string
    {
        $normalized = ucfirst(strtolower($status));
        
        // Mapping des variations possibles
        $statusMap = [
            'Available' => self::STATUS_AVAILABLE,
            'Preparing' => self::STATUS_PREPARING,
            'Charging' => self::STATUS_CHARGING,
            'SuspendedEvse' => self::STATUS_SUSPENDED_EVSE,
            'Suspendedevse' => self::STATUS_SUSPENDED_EVSE,
            'Suspendedev' => self::STATUS_SUSPENDED_EV,
            'Suspendedév' => self::STATUS_SUSPENDED_EV,
            'Finishing' => self::STATUS_FINISHING,
            'Reserved' => self::STATUS_RESERVED,
            'Unavailable' => self::STATUS_UNAVAILABLE,
            'Faulted' => self::STATUS_FAULTED,
            'Occupied' => self::STATUS_CHARGING, // Alias commun
            'Inoperative' => self::STATUS_UNAVAILABLE, // OCPP term
            'Operative' => self::STATUS_AVAILABLE, // OCPP term
        ];

        return $statusMap[$normalized] ?? self::STATUS_UNKNOWN;
    }

    /**
     * Vérifie si le connecteur est disponible pour recharge
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Vérifie si le connecteur est en cours de charge
     */
    public function isCharging(): bool
    {
        return in_array($this->status, [
            self::STATUS_CHARGING,
            self::STATUS_SUSPENDED_EVSE,
            self::STATUS_SUSPENDED_EV,
        ]);
    }

    /**
     * Vérifie si le connecteur est en préparation
     */
    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    /**
     * Vérifie si le connecteur est réservé
     */
    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    /**
     * Vérifie si le connecteur a une erreur
     */
    public function hasError(): bool
    {
        return $this->status === self::STATUS_FAULTED || !empty($this->errorCode);
    }

    /**
     * Vérifie si le connecteur est indisponible
     */
    public function isUnavailable(): bool
    {
        return $this->status === self::STATUS_UNAVAILABLE;
    }

    /**
     * Vérifie si le connecteur peut démarrer une session
     */
    public function canStartSession(): bool
    {
        return $this->isAvailable() || $this->isPreparing();
    }

    /**
     * Obtient le timestamp formaté
     */
    public function getFormattedTimestamp(): ?string
    {
        if (!$this->timestamp) {
            return null;
        }

        try {
            return Carbon::parse($this->timestamp)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $this->timestamp;
        }
    }

    /**
     * Obtient le libellé du statut en français
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_AVAILABLE => 'Disponible',
            self::STATUS_PREPARING => 'En préparation',
            self::STATUS_CHARGING => 'En charge',
            self::STATUS_SUSPENDED_EVSE => 'Suspendu (EVSE)',
            self::STATUS_SUSPENDED_EV => 'Suspendu (EV)',
            self::STATUS_FINISHING => 'Finalisation',
            self::STATUS_RESERVED => 'Réservé',
            self::STATUS_UNAVAILABLE => 'Indisponible',
            self::STATUS_FAULTED => 'En erreur',
            self::STATUS_UNKNOWN => 'Inconnu',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    /**
     * Obtient la couleur CSS associée au statut
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_PREPARING => 'blue',
            self::STATUS_CHARGING => 'blue',
            self::STATUS_SUSPENDED_EVSE => 'yellow',
            self::STATUS_SUSPENDED_EV => 'yellow',
            self::STATUS_FINISHING => 'blue',
            self::STATUS_RESERVED => 'orange',
            self::STATUS_UNAVAILABLE => 'gray',
            self::STATUS_FAULTED => 'red',
            self::STATUS_UNKNOWN => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
