<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

/**
 * DTO pour la requête Lock Connector OCPP
 */
class LockConnectorRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public int $connectorId;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? $data['charge_box_id'] ?? '';
        $this->connectorId = (int) ($data['connectorId'] ?? $data['connector_id'] ?? 0);
    }

    /**
     * Crée le DTO depuis une requête HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('chargeBoxId') ?? $request->input('charge_box_id'),
            'connectorId' => (int) ($request->input('connectorId') ?? $request->input('connector_id', 0)),
        ]);
    }

    /**
     * Convertit en payload pour l'API SteVe
     */
    public function toApiPayload(): array
    {
        return [
            'chargePointId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
        ];
    }

    /**
     * Convertit en tableau
     */
    public function toArray(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
        ];
    }

    /**
     * Validation des données
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->chargeBoxId)) {
            $errors['chargeBoxId'] = 'L\'identifiant de la borne est requis';
        }

        if ($this->connectorId < 1) {
            $errors['connectorId'] = 'L\'identifiant du connecteur doit être positif';
        }

        return $errors;
    }

    /**
     * Vérifie si les données sont valides
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
