<?php

namespace App\Http\Requests\Ocpp;

use Illuminate\Foundation\Http\FormRequest;

class ResetChargingPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Bug #2: must reject any value outside {Soft, Hard}. The DTO used to
        // silently downgrade unknown types to Soft.
        return [
            'type' => ['required', 'string', 'in:Soft,Hard,soft,hard'],
        ];
    }
}
