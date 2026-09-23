<?php

namespace App\Http\Requests\PostpaidCharging;

use Illuminate\Foundation\Http\FormRequest;

class StopPostpaidSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'stop_reason' => [
                'nullable',
                'string',
                'max:255',
                'in:user_stop,automatic,error,timeout,other',
            ],
            'meter_end' => [
                'nullable',
                'numeric',
                'min:0',
            ],
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
            'stop_reason.string' => 'La raison d\'arrêt doit être une chaîne de caractères.',
            'stop_reason.max' => 'La raison d\'arrêt ne peut pas dépasser 255 caractères.',
            'stop_reason.in' => 'La raison d\'arrêt doit être l\'une des valeurs suivantes: user_stop, automatic, error, timeout, other.',
            'meter_end.numeric' => 'La valeur finale du compteur doit être un nombre.',
            'meter_end.min' => 'La valeur finale du compteur ne peut pas être négative.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Set default stop_reason if not provided
        if (!$this->has('stop_reason')) {
            $this->merge(['stop_reason' => 'user_stop']);
        }
    }
}

