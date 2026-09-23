<?php

namespace App\Http\Requests\PostpaidCharging;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ChargingPoint;

class StartPostpaidSessionRequest extends FormRequest
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
            'connector_id' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
            'min_threshold' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
            ],
            'id_tag' => [
                'nullable',
                'string',
                'max:255',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'metadata.*' => [
                'nullable',
                'string',
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
            'connector_id.integer' => 'L\'identifiant du connecteur doit être un nombre entier.',
            'connector_id.min' => 'L\'identifiant du connecteur doit être au moins 1.',
            'connector_id.max' => 'L\'identifiant du connecteur ne peut pas dépasser 10.',
            'min_threshold.numeric' => 'Le seuil minimum doit être un nombre.',
            'min_threshold.min' => 'Le seuil minimum ne peut pas être négatif.',
            'min_threshold.max' => 'Le seuil minimum ne peut pas dépasser 1000 EUR.',
            'id_tag.string' => 'L\'identifiant de tag doit être une chaîne de caractères.',
            'id_tag.max' => 'L\'identifiant de tag ne peut pas dépasser 255 caractères.',
            'metadata.array' => 'Les métadonnées doivent être un tableau.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Set default connector_id if not provided
        if (!$this->has('connector_id')) {
            $this->merge(['connector_id' => 1]);
        }
    }
}

