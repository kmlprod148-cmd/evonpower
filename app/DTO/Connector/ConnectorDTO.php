<?php

namespace App\DTO\Connector;

use App\Core\DTOs\BaseDTO;
use App\Models\Connector;

/**
 * DTO pour représenter un connecteur de point de charge
 * Mappe les données du modèle Connector et les réponses API SteVe
 */
class ConnectorDTO extends BaseDTO
{
    public int $connectorId;
    public ?string $type;
    public string $status;
    public ?float $power;
    public ?string $format;
    public ?string $tariffId;
    public ?string $errorCode;
    public ?string $info;
    public ?string $vendorId;
    public ?int $chargingPointId;

    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        $this->connectorId = (int) ($data['connectorId'] ?? $data['connector_id'] ?? 0);
        $this->type = $data['type'] ?? null;
        $this->status = $data['status'] ?? 'Unknown';
        $this->power = isset($data['power']) ? (float) $data['power'] : null;
        $this->format = $data['format'] ?? null;
        $this->tariffId = $data['tariffId'] ?? $data['tariff_id'] ?? null;
        $this->errorCode = $data['errorCode'] ?? $data['error_code'] ?? null;
        $this->info = $data['info'] ?? null;
        $this->vendorId = $data['vendorId'] ?? $data['vendor_id'] ?? null;
        $this->chargingPointId = isset($data['chargingPointId']) || isset($data['charging_point_id']) 
            ? (int) ($data['chargingPointId'] ?? $data['charging_point_id']) 
            : null;
    }

    /**
     * Crée un DTO à partir de la réponse API SteVe
     */
    public static function fromApiResponse(array $data): self
    {
        return new self([
            'connectorId' => $data['connectorId'] ?? $data['id'] ?? 0,
            'type' => $data['type'] ?? $data['connectorType'] ?? null,
            'status' => $data['status'] ?? 'Unknown',
            'power' => $data['power'] ?? $data['maxPower'] ?? null,
            'format' => $data['format'] ?? null,
            'tariffId' => $data['tariffId'] ?? null,
            'errorCode' => $data['errorCode'] ?? null,
            'info' => $data['info'] ?? $data['vendorErrorCode'] ?? null,
            'vendorId' => $data['vendorId'] ?? null,
        ]);
    }

    /**
     * Crée un DTO à partir du modèle Connector
     */
    public static function fromModel(Connector $connector): self
    {
        return new self([
            'connectorId' => $connector->connector_id,
            'type' => $connector->type,
            'status' => $connector->status,
            'power' => $connector->power,
            'format' => $connector->format,
            'tariffId' => $connector->tariff_id,
            'chargingPointId' => $connector->charging_point_id,
        ]);
    }

    /**
     * Convertit le DTO en tableau pour persistance
     */
    public function toModelArray(): array
    {
        return [
            'connector_id' => $this->connectorId,
            'type' => $this->type,
            'status' => $this->status,
            'power' => $this->power,
            'format' => $this->format,
            'tariff_id' => $this->tariffId,
        ];
    }

    /**
     * Vérifie si le connecteur est disponible
     */
    public function isAvailable(): bool
    {
        return strtoupper($this->status) === 'AVAILABLE';
    }

    /**
     * Vérifie si le connecteur est en charge
     */
    public function isCharging(): bool
    {
        return strtoupper($this->status) === 'CHARGING';
    }

    /**
     * Vérifie si le connecteur a une erreur
     */
    public function hasError(): bool
    {
        return strtoupper($this->status) === 'FAULTED' || !empty($this->errorCode);
    }
}
