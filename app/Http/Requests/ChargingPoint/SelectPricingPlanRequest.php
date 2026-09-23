<?php

namespace App\Http\Requests\ChargingPoint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SelectPricingPlanRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête
     *
     * @return bool
     */
    public function authorize()
    {
        // Tout le monde peut sélectionner un plan tarifaire (public)
        return true;
    }

    /**
     * Récupère les règles de validation qui s'appliquent à la requête
     *
     * @return array
     */
    public function rules()
    {
        return [
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'connector_id' => 'required|integer|min:1',
        ];
    }

    /**
     * Récupère les messages d'erreur personnalisés pour les règles de validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'pricing_plan_id.required' => 'Veuillez sélectionner un plan tarifaire.',
            'pricing_plan_id.exists' => 'Le plan tarifaire sélectionné n\'existe pas.',
            'connector_id.required' => 'Veuillez sélectionner un connecteur.',
            'connector_id.integer' => 'L\'identifiant du connecteur doit être un nombre.',
            'connector_id.min' => 'L\'identifiant du connecteur doit être un nombre positif.',
        ];
    }

    /**
     * Configure les données de la requête avant la validation
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'connector_id' => (int) $this->connector_id,
        ]);
    }
}