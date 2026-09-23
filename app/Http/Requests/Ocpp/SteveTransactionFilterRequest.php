<?php

namespace App\Http\Requests\Ocpp;

use Illuminate\Foundation\Http\FormRequest;

class SteveTransactionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transactionPk' => ['sometimes', 'integer', 'min:1'],
            'type'          => ['sometimes', 'string', 'in:ALL,ACTIVE,STOPPED,COMPLETED,EVERYTHING,all,active,stopped,completed,everything'],
            'periodType'    => ['sometimes', 'string', 'in:ALL,TODAY,LAST_10,LAST_30,LAST_90,FROM_TO,LAST_7,all,today,last_10,last_30,last_90,from_to,last_7'],
            'chargeBoxId'   => ['sometimes'],
            'chargeBoxId.*' => ['string', 'max:64'],
            'ocppIdTag'     => ['sometimes'],
            'ocppIdTag.*'   => ['string', 'max:64'],
            'connectorId'   => ['sometimes', 'integer', 'min:0'],
            'userId'        => ['sometimes', 'integer', 'min:1'],
            'from'          => ['sometimes', 'date'],
            'to'            => ['sometimes', 'date', 'after_or_equal:from'],
            'page'          => ['sometimes', 'integer', 'min:1'],
            'perPage'       => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }

    /**
     * Convenience for the controller: only the keys SteVe's filter API understands.
     */
    public function steveFilters(): array
    {
        return $this->only([
            'transactionPk', 'type', 'periodType', 'chargeBoxId',
            'ocppIdTag', 'connectorId', 'userId', 'from', 'to',
        ]);
    }
}
