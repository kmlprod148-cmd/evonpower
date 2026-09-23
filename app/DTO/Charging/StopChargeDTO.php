<?php

namespace App\DTO\Charging;

use Illuminate\Http\Request;

class StopChargeDTO
{
    /**
     * Identifiant externe de la transaction
     * @var string
     */
    public $transaction_id;

    /**
     * Raison de l'arrêt de la recharge
     * @var string|null
     */
    public $stop_reason;

    /**
     * Constructeur
     *
     * @param string $transaction_id
     * @param string|null $stop_reason
     */
    public function __construct(
        string $transaction_id,
        ?string $stop_reason = null
    ) {
        $this->transaction_id = $transaction_id;
        $this->stop_reason = $stop_reason;
    }

    /**
     * Crée un DTO à partir d'une requête HTTP
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->input('transaction_id'),
            $request->input('stop_reason')
        );
    }

    /**
     * Convertit le DTO en tableau
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'stop_reason' => $this->stop_reason,
        ];
    }
}