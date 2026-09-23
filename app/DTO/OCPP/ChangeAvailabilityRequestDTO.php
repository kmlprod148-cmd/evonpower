<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

/**
 * DTO pour la requête ChangeAvailability OCPP
 * 
 * @see OCPP 1.6 Specification - ChangeAvailability.req
 */
class ChangeAvailabilityRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public int $connectorId;
    public AvailabilityTypeEnum $type;

    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? $data['charge_box_id'] ?? '';
        $this->connectorId = (int) ($data['connectorId'] ?? $data['connector_id'] ?? 0);
        
        // Gestion du type avec enum
        if (isset($data['type'])) {
            if ($data['type'] instanceof AvailabilityTypeEnum) {
                $this->type = $data['type'];
            } elseif (is_bool($data['type'])) {
                $this->type = AvailabilityTypeEnum::fromBoolean($data['type']);
            } elseif (is_string($data['type'])) {
                $this->type = AvailabilityTypeEnum::fromString($data['type']) 
                    ?? AvailabilityTypeEnum::OPERATIVE;
            } else {
                $this->type = AvailabilityTypeEnum::OPERATIVE;
            }
        } else {
            $this->type = AvailabilityTypeEnum::OPERATIVE;
        }
    }

    /**
     * Crée le DTO depuis une requête HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('charge_box_id') ?? $request->input('chargeBoxId'),
            'connectorId' => $request->input('connector_id') ?? $request->input('connectorId', 0),
            'type' => $request->input('type') ?? $request->input('availability_type'),
        ]);
    }

    /**
     * Crée le DTO pour rendre opérationnel
     */
    public static function makeOperative(string $chargeBoxId, int $connectorId = 0): self
    {
        return new self([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'type' => AvailabilityTypeEnum::OPERATIVE,
        ]);
    }

    /**
     * Crée le DTO pour rendre inopérationnel
     */
    public static function makeInoperative(string $chargeBoxId, int $connectorId = 0): self
    {
        return new self([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'type' => AvailabilityTypeEnum::INOPERATIVE,
        ]);
    }

    /**
     * Vérifie si le connecteur ID est 0 (toute la borne)
     */
    public function isWholeChargePoint(): bool
    {
        return $this->connectorId === 0;
    }

    /**
     * Vérifie si la demande est pour rendre opérationnel
     */
    public function isOperative(): bool
    {
        return $this->type->isOperative();
    }

    /**
     * Convertit en payload pour l'API SteVe
     */
    public function toApiPayload(): array
    {
        return [
            'chargePointId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
            'type' => $this->type->value,
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
            'type' => $this->type->value,
            'typeLabel' => $this->type->label(),
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

        if ($this->connectorId < 0) {
            $errors['connectorId'] = 'L\'identifiant du connecteur doit être positif ou zéro';
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
