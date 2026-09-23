<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

/**
 * DTO pour la requête Reset OCPP
 * 
 * @see OCPP 1.6 Specification - Reset.req
 */
class ResetRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public ResetTypeEnum $type;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? $data['charge_box_id'] ?? '';
        
        if (isset($data['type'])) {
            if ($data['type'] instanceof ResetTypeEnum) {
                $this->type = $data['type'];
            } elseif (is_string($data['type'])) {
                $this->type = ResetTypeEnum::fromString($data['type']) ?? ResetTypeEnum::SOFT;
            } elseif (is_bool($data['type'])) {
                $this->type = $data['type'] ? ResetTypeEnum::HARD : ResetTypeEnum::SOFT;
            } else {
                $this->type = ResetTypeEnum::SOFT;
            }
        } else {
            $this->type = ResetTypeEnum::SOFT;
        }
    }

    /**
     * Crée le DTO depuis une requête HTTP
     */
    public static function fromRequest(Request $request): self
    {
        $typeValue = $request->input('type', 'Soft');
        $hard = $request->boolean('hard', false);
        
        // Si 'hard' est explicitement passé, l'utiliser
        if ($request->has('hard')) {
            $typeValue = $hard ? 'Hard' : 'Soft';
        }

        return new self([
            'chargeBoxId' => $request->input('chargeBoxId') ?? $request->input('charge_box_id'),
            'type' => $typeValue,
        ]);
    }

    /**
     * Crée un DTO pour soft reset
     */
    public static function soft(string $chargeBoxId): self
    {
        return new self([
            'chargeBoxId' => $chargeBoxId,
            'type' => ResetTypeEnum::SOFT,
        ]);
    }

    /**
     * Crée un DTO pour hard reset
     */
    public static function hard(string $chargeBoxId): self
    {
        return new self([
            'chargeBoxId' => $chargeBoxId,
            'type' => ResetTypeEnum::HARD,
        ]);
    }

    /**
     * Convertit en payload pour l'API SteVe
     */
    public function toApiPayload(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
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

        return $errors;
    }

    /**
     * Vérifie si les données sont valides
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Vérifie si c'est un soft reset
     */
    public function isSoftReset(): bool
    {
        return $this->type->isSoft();
    }

    /**
     * Vérifie si c'est un hard reset
     */
    public function isHardReset(): bool
    {
        return $this->type->isHard();
    }
}
