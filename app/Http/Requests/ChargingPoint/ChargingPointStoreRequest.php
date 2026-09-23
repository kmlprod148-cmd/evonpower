<?php

namespace App\Http\Requests\ChargingPoint;

use Illuminate\Foundation\Http\FormRequest;

class ChargingPointStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:charging_points',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'status' => 'required|string|in:online,offline,maintenance,error',
            'installation_date' => 'nullable|date',
            'integrator_id' => 'nullable|exists:integrators,id',
            'partner_id' => 'nullable|exists:partners,id',
            'group_id' => 'nullable|exists:groups,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90|decimal:8',
            'longitude' => 'nullable|numeric|between:-180,180|decimal:8',
            'firmware_version' => 'nullable|string|max:100',
            'communication_protocol' => 'nullable|string|max:100',
            'ip_address' => 'nullable|string|max:45',
            'mac_address' => [
                'nullable',
                'string',
                'max:17',
                // enforce proper MAC address format (six pairs of hex digits separated by : or -)
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'
            ],
            'power_output' => 'nullable|numeric|min:0',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after_or_equal:last_maintenance_date',
            'access_type' => 'nullable|string|in:public,private,restricted',
            'public_access' => 'nullable|boolean',
            'access_code' => 'nullable|string|max:100|required_if:public_access,false',
            'qr_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nom',
            'serial_number' => 'numéro de série',
            'manufacturer' => 'fabricant',
            'model' => 'modèle',
            'status' => 'statut',
            'installation_date' => 'date d\'installation',
            'integrator_id' => 'intégrateur',
            'partner_id' => 'partenaire',
            'group_id' => 'groupe',
            'pricing_plan_id' => 'plan tarifaire',
            'location' => 'emplacement',
            'address' => 'adresse',
            'city' => 'ville',
            'postal_code' => 'code postal',
            'country' => 'pays',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'firmware_version' => 'version du firmware',
            'communication_protocol' => 'protocole de communication',
            'ip_address' => 'adresse IP',
            'mac_address' => 'adresse MAC',
            'power_output' => 'puissance de sortie',
            'last_maintenance_date' => 'dernière date de maintenance',
            'next_maintenance_date' => 'prochaine date de maintenance',
            'access_type' => 'type d\'accès',
            'public_access' => 'accès public',
            'access_code' => 'code d\'accès',
            'qr_code' => 'code QR',
            'description' => 'description',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Le champ :attribute est obligatoire.',
            // ... (keep all messages from user's code)
        ];
    }
}