<?php

namespace App\Http\Requests\PublicChargingOffer;

use Illuminate\Foundation\Http\FormRequest;

class SelectPricingPlanRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête
     *
     * @return bool
     */
    public function authorize()
    {
        // Cette requête est publique, pas besoin d'authentification
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
            'pricing_plan_id' => 'required|integer|exists:pricing_plans,id',
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
            'pricing_plan_id.required' => 'Le plan tarifaire est obligatoire.',
            'pricing_plan_id.integer' => 'Le plan tarifaire doit être un nombre entier.',
            'pricing_plan_id.exists' => 'Le plan tarifaire sélectionné n\'existe pas.',
            'connector_id.required' => 'Le connecteur est obligatoire.',
            'connector_id.integer' => 'Le connecteur doit être un nombre entier.',
            'connector_id.min' => 'Le connecteur doit être supérieur à 0.',
        ];
    }
}