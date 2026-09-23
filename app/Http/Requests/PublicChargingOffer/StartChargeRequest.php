<?php

namespace App\Http\Requests\PublicChargingOffer;

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
            'session_token' => 'required|string|uuid',
            'terms_agreement' => 'required|boolean|accepted',
            'payment_method' => 'nullable|string|in:card,paypal,apple_pay,google_pay',
            'payment_token' => 'nullable|string',
            'max_amount' => 'nullable|numeric|min:0',
            'email' => 'nullable|email',
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
            'session_token.required' => 'Le token de session est obligatoire.',
            'session_token.string' => 'Le token de session doit être une chaîne de caractères.',
            'terms_agreement.required' => 'Vous devez accepter les conditions générales.',
            'terms_agreement.boolean' => 'La valeur de l\'acceptation des conditions générales est invalide.',
            'terms_agreement.accepted' => 'Vous devez accepter les conditions générales.',
            'payment_method.string' => 'La méthode de paiement doit être une chaîne de caractères.',
            'payment_method.in' => 'La méthode de paiement sélectionnée n\'est pas valide.',
            'payment_token.string' => 'Le token de paiement doit être une chaîne de caractères.',
            'max_amount.numeric' => 'Le montant maximum doit être un nombre.',
            'max_amount.min' => 'Le montant maximum doit être supérieur ou égal à 0.',
            'email.email' => 'L\'adresse email n\'est pas valide.',
        ];
    }
}