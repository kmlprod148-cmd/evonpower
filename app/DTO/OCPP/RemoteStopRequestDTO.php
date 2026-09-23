<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

/**
 * DTO pour la requête Remote Stop Transaction OCPP
 *
 * @see OCPP 1.6 Specification - RemoteStopTransaction.req
 */
class RemoteStopRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public ?int $transactionId;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId   = (string) ($data['chargeBoxId'] ?? $data['charge_box_id'] ?? '');
        $tx                  = $data['transactionId'] ?? $data['transaction_id'] ?? null;
        $this->transactionId = ($tx === null || $tx === '') ? null : (int) $tx;
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId'   => $request->input('chargeBoxId') ?? $request->input('charge_box_id'),
            'transactionId' => $request->input('transactionId') ?? $request->input('transaction_id'),
        ]);
    }

    public function toApiPayload(): array
    {
        $payload = ['chargeBoxId' => $this->chargeBoxId];

        if ($this->transactionId !== null) {
            $payload['transactionId'] = $this->transactionId;
        }

        return $payload;
    }

    public function toArray(): array
    {
        return [
            'chargeBoxId'   => $this->chargeBoxId,
            'transactionId' => $this->transactionId,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->chargeBoxId)) {
            $errors['chargeBoxId'] = 'L\'identifiant de la borne est requis';
        }

        if ($this->transactionId === null || $this->transactionId < 1) {
            $errors['transactionId'] = 'L\'identifiant de transaction est requis';
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
