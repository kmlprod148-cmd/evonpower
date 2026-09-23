<?php

namespace App\Http\Requests\PublicChargingOffer;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Public request, no authentication needed for basic validation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'reservation_type' => 'required|string|in:kwh,minute',
            'reservation_value' => 'required_if:reservation_type,kwh,minute|numeric|min:0',
            'start_time' => 'nullable|string', // Assuming string format for parsing with now()->parse()
            'payment_type' => 'required|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'reservation_type.required' => 'Le type de réservation est obligatoire.',
            'reservation_type.string' => 'Le type de réservation doit être une chaîne de caractères.',
            'reservation_type.in' => 'Le type de réservation sélectionné est invalide.',
            'reservation_value.required' => 'La valeur de la réservation est obligatoire.',
            'reservation_value.numeric' => 'La valeur de la réservation doit être un nombre.',
            'reservation_value.min' => 'La valeur de la réservation doit être supérieure ou égale à 0.',
            'start_time.string' => 'L\'heure de début de la réservation doit être une chaîne de caractères.',
            'payment_type.required' => 'Le type de paiement est obligatoire.',
        ];
    }
}