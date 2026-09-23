<?php

declare(strict_types=1);

namespace App\Http\Requests\Steve\ChargePoint;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body of POST /api/v1/steve/charge-points.
 *
 * Mirrors SteVe's ChargePointForm. Only `chargeBoxId` is structurally required;
 * the rest are pass-through optional fields that SteVe persists as-is.
 */
class StoreChargePointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chargeBoxId'                              => ['required', 'string', 'max:255'],
            'description'                              => ['nullable', 'string', 'max:500'],
            'note'                                     => ['nullable', 'string'],
            'adminAddress'                             => ['nullable', 'string', 'max:500'],
            'registrationStatus'                       => ['nullable', 'string', 'max:64'],
            'insertConnectorStatusAfterTransactionMsg' => ['nullable', 'boolean'],
            'locationLatitude'                         => ['nullable', 'numeric', 'between:-90,90'],
            'locationLongitude'                        => ['nullable', 'numeric', 'between:-180,180'],
            'address'                                  => ['nullable', 'array'],
            'address.street'                           => ['nullable', 'string', 'max:255'],
            'address.houseNumber'                      => ['nullable', 'string', 'max:32'],
            'address.zipCode'                          => ['nullable', 'string', 'max:32'],
            'address.city'                             => ['nullable', 'string', 'max:255'],
            'address.country'                          => ['nullable', 'string', 'max:64'],
        ];
    }
}
