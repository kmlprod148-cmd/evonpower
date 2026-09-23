<?php

namespace App\Modules\ChargingPoints\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargingPointCreateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|unique:charging_points,serial_number',
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'status' => 'required|in:online,offline,maintenance,error',
            'installation_date' => 'required|date',
            'integrator_id' => 'nullable|exists:integrators,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'connectors' => 'required|array',
            'connectors.*.type' => 'required|string',
            'connectors.*.max_power' => 'required|numeric',
            'connectors.*.voltage' => 'nullable|numeric',
            'connectors.*.amperage' => 'nullable|numeric'
        ];
    }
}