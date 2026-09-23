<?php

namespace App\Http\Requests\PublicChargingOffer;

use Illuminate\Foundation\Http\FormRequest;

class GetChargingStatusRequest extends FormRequest
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
            'token' => 'required|string|uuid',
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
            'token.required' => 'Le token de suivi est obligatoire.',
            'token.string' => 'Le token de suivi doit être une chaîne de caractères.',
        ];
    }
}