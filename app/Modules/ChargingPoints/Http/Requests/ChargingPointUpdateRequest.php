<?php

namespace App\Modules\ChargingPoints\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChargingPointUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'serial_number' => [
                'sometimes',
                'string',
                Rule::unique('charging_points', 'serial_number')->ignore($this->chargingPoint)
            ],
            'manufacturer' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:online,offline,maintenance,error',
            'installation_date' => 'sometimes|date',
            'integrator_id' => 'nullable|exists:integrators,id',
            'partner_id' => 'nullable|exists:partners,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }
}