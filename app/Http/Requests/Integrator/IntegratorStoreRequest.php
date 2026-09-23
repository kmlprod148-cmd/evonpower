<?php

namespace App\Http\Requests\Integrator;

use Illuminate\Foundation\Http\FormRequest;

class IntegratorStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Utiliser les Gates et Policies pour une autorisation plus précise
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:integrators',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'nom',
            'email' => 'adresse e-mail',
            'phone' => 'téléphone',
            'city' => 'ville',
            'business_profile_id' => 'profil business',
            'address' => 'adresse',
            'postal_code' => 'code postal',
            'country' => 'pays',
            'contact_name' => 'nom du contact',
            'website' => 'site web',
            'logo' => 'logo',
            'description' => 'description',
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
            'required' => 'Le champ :attribute est obligatoire.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
            'unique' => 'Cette valeur de :attribute est déjà utilisée.',
            'exists' => 'Le profil business sélectionné n\'existe pas.',
        ];
    }
}