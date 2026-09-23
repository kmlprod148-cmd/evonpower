<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SteveChargingPointStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     * According to Steve API docs for POST /api/v1/chargePoints
     */
    public function rules(): array
    {
        return [
            // Required fields
            'chargeBoxId' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/', // Alphanumeric, underscore, and dash only
            ],
            'description' => [
                'required',
                'string',
                'max:255',
            ],
            
            // Optional fields
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'adminAddress' => [
                'nullable',
                'string',
                'max:255',
            ],
            'registrationStatus' => [
                'nullable',
                'string',
                'in:Accepted,Pending,Rejected',
            ],
            
            // Location fields
            'locationLatitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'locationLongitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            
            // Address object fields
            'address.street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address.houseNumber' => [
                'nullable',
                'string',
                'max:50',
            ],
            'address.zipCode' => [
                'nullable',
                'string',
                'max:20',
            ],
            'address.city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address.country' => [
                'nullable',
                'string',
                'max:10',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'chargeBoxId.required' => 'Le ChargeBox ID est obligatoire.',
            'chargeBoxId.regex' => 'Le ChargeBox ID ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne peut pas dépasser 255 caractères.',
            'note.max' => 'La note ne peut pas dépasser 1000 caractères.',
            'registrationStatus.in' => 'Le statut d\'enregistrement doit être Accepted, Pending ou Rejected.',
            'locationLatitude.numeric' => 'La latitude doit être un nombre.',
            'locationLatitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'locationLongitude.numeric' => 'La longitude doit être un nombre.',
            'locationLongitude.between' => 'La longitude doit être comprise entre -180 et 180.',
        ];
    }
}

