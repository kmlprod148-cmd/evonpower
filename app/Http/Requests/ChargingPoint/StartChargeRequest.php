<?php

namespace App\Http\Requests\ChargingPoint;

use Illuminate\Foundation\Http\FormRequest;

class StartChargeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête
     *
     * @return bool
     */
    public function authorize()
    {
        // Tout le monde peut démarrer une recharge (public)
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
            'connector_id' => 'required|integer|min:1',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'terms_agreement' => 'required|accepted',
            'max_energy' => 'nullable|numeric|min:0',
            'max_duration' => 'nullable|integer|min:1',
            'max_amount' => 'nullable|numeric|min:0',
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
            'connector_id.required' => 'L\'identifiant du connecteur est requis.',
            'connector_id.integer' => 'L\'identifiant du connecteur doit être un nombre.',
            'connector_id.min' => 'L\'identifiant du connecteur doit être un nombre positif.',
            'pricing_plan_id.required' => 'L\'identifiant du plan tarifaire est requis.',
            'pricing_plan_id.exists' => 'Le plan tarifaire sélectionné n\'existe pas.',
            'terms_agreement.required' => 'Vous devez accepter les conditions générales.',
            'terms_agreement.accepted' => 'Vous devez accepter les conditions générales.',
            'max_energy.numeric' => 'L\'énergie maximale doit être un nombre.',
            'max_energy.min' => 'L\'énergie maximale doit être positive.',
            'max_duration.integer' => 'La durée maximale doit être un nombre entier.',
            'max_duration.min' => 'La durée maximale doit être d\'au moins 1 minute.',
            'max_amount.numeric' => 'Le montant maximal doit être un nombre.',
            'max_amount.min' => 'Le montant maximal doit être positif.',
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
            'max_duration' => $this->max_duration ? (int) $this->max_duration : null,
        ]);
    }
}