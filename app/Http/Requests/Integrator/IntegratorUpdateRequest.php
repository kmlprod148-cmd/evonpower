<?php

namespace App\Http\Requests\Integrator;

use Illuminate\Foundation\Http\FormRequest;

class IntegratorUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Autoriser si l'utilisateur est admin ou a la permission edit_integrators
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) return true;
        if ($user->can('edit_integrators')) return true;
        // Optionnel : fallback sur la policy Laravel
        return $user->can('update', $this->route('integrator'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $integrator = $this->route('integrator');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:integrators,email,' . $integrator->id,
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
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
            'url' => 'Le champ :attribute doit être une URL valide (ex: https://www.example.com).',
        ];
    }
}