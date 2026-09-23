<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'type' => 'required|in:public,private,commercial',
            'status' => 'required|in:active,inactive,maintenance,planned',
            'group_id' => 'nullable|exists:groups,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description' => 'nullable|string|max:1000',
            'charging_point_ids' => 'nullable|array|max:2',
            'charging_point_ids.*' => 'exists:charging_points,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Le nom de la station est obligatoire.',
            'address.required' => 'L\'adresse est obligatoire.',
            'city.required' => 'La ville est obligatoire.',
            'type.required' => 'Le type de station est obligatoire.',
            'type.in' => 'Le type de station doit être public, privé ou commercial.',
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut doit être actif, inactif, maintenance ou planifié.',
            'latitude.required' => 'La latitude est obligatoire.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.required' => 'La longitude est obligatoire.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
            'group_id.exists' => 'Le groupe sélectionné n\'existe pas.',
            'charging_point_ids.max' => 'Une station ne peut contenir que 2 points de charge maximum.',
            'charging_point_ids.*.exists' => 'Un ou plusieurs points de charge sélectionnés n\'existent pas.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if charging points are already assigned to other stations
            if ($this->has('charging_point_ids')) {
                $chargingPointIds = $this->input('charging_point_ids');
                
                if (!empty($chargingPointIds)) {
                    $alreadyAssigned = \App\Models\ChargingPoint::whereIn('id', $chargingPointIds)
                        ->whereNotNull('station_id')
                        ->exists();

                    if ($alreadyAssigned) {
                        $validator->errors()->add('charging_point_ids', 'Un ou plusieurs points de charge sont déjà assignés à une autre station.');
                    }
                }
            }
        });
    }
}