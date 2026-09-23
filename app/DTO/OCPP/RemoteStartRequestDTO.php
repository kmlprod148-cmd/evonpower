<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

/**
 * DTO pour la requête Remote Start Transaction OCPP
 * 
 * @see OCPP 1.6 Specification - RemoteStartTransaction.req
 */
class RemoteStartRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public int $connectorId;
    public string $ocppTag;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? $data['charge_box_id'] ?? '';
        $this->connectorId = (int) ($data['connectorId'] ?? $data['connector_id'] ?? 1);
        $this->ocppTag = $data['ocppTag'] ?? $data['idTag'] ?? $data['id_tag'] ?? '';
    }

    /**
     * Crée le DTO depuis une requête HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('chargeBoxId') ?? $request->input('charge_box_id'),
            'connectorId' => (int) ($request->input('connectorId') ?? $request->input('connector_id', 1)),
            'ocppTag' => $request->input('ocppTag') ?? $request->input('idTag') ?? $request->input('id_tag'),
        ]);
    }

    /**
     * Convertit en payload pour l'API SteVe
     */
    public function toApiPayload(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
            'ocppTag' => $this->ocppTag,
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
            'ocppTag' => $this->ocppTag,
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

        if (empty($this->ocppTag)) {
            $errors['ocppTag'] = 'Le tag OCPP est requis';
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
