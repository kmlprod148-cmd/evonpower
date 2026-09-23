<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChargingStationUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $stationId = $this->route('charging_station'); // Assuming route parameter is 'charging_station'

        return [
            'name' => 'required|string|max:255',
            'identifier' => [
                'required',
                'string',
                Rule::unique('charging_stations')->ignore($stationId),
            ],
            'group_id' => 'required|exists:station_groups,id',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:online,offline,maintenance',
        ];
    }
}