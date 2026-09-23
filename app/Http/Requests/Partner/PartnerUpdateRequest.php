<?php

namespace App\Http\Requests\Partner;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class PartnerUpdateRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Adjust authorization logic as needed for your application
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // Informations générales
            'name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(['Exploitant', 'Propriétaire', 'Intégrateur'])],
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            
            // Champs de contact étendus
            'contact_title' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'technical_contact_name' => 'nullable|string|max:255',
            'technical_contact_email' => 'nullable|email|max:255',
            'technical_contact_phone' => 'nullable|string|max:20',
            'technical_contact_title' => 'nullable|string|max:255',
            'commercial_contact_name' => 'nullable|string|max:255',
            'commercial_contact_email' => 'nullable|email|max:255',
            'commercial_contact_phone' => 'nullable|string|max:20',
            'commercial_contact_title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            
            // Champs business étendus
            'tax_id' => 'nullable|string|max:100',
            'company_registration' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:34',
            'bic' => 'nullable|string|max:11',
            'sector' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'annual_revenue' => 'nullable|numeric|min:0',
            'employee_count' => 'nullable|integer|min:0',
            'billing_address' => 'nullable|string',
            'billing_email' => 'nullable|email|max:255',
            'payment_terms' => 'nullable|string|max:50',
            'currency' => 'nullable|string|size:3',
            
            // Champs de paramètres
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'receive_reports' => 'boolean',
            'receive_notifications' => 'boolean',
            'receive_marketing' => 'boolean',
            'receive_sms' => 'boolean',
            'access_level' => 'nullable|string|max:50',
            'api_access' => 'boolean',
            'max_users' => 'nullable|integer|min:1|max:1000',
            'max_charging_points' => 'nullable|integer|min:1|max:10000',
            'two_factor_auth' => 'boolean',
            'password_expiry' => 'boolean',
            'ip_restriction' => 'boolean',
            'allowed_ips' => 'nullable|string',
            'billing_frequency' => 'nullable|string|max:50',
            'auto_renewal' => 'boolean',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'collection_mode' => ['nullable', 'string', Rule::in(['admin', 'integrator', 'partner'])],
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
            'name.required' => 'Le nom du partenaire est requis.',
            'contact_name.required' => 'Le nom du contact principal est requis.',
            'type.required' => 'Le type de partenaire est requis.',
            'email.required' => 'L\'email principal est requis.',
            'email.email' => 'L\'email principal doit être une adresse email valide.',
            'phone.required' => 'Le téléphone principal est requis.',
            'city.required' => 'La ville est requise.',
            'website.url' => 'Le site web doit être une URL valide.',
            'business_profile_id.exists' => 'Le profil business sélectionné n\'existe pas.',
            'technical_contact_email.email' => 'L\'email du contact technique doit être valide.',
            'commercial_contact_email.email' => 'L\'email du contact commercial doit être valide.',
            'billing_email.email' => 'L\'email de facturation doit être valide.',
            'iban.max' => 'L\'IBAN ne peut pas dépasser 34 caractères.',
            'bic.max' => 'Le BIC ne peut pas dépasser 11 caractères.',
            'annual_revenue.numeric' => 'Le chiffre d\'affaires doit être un nombre.',
            'annual_revenue.min' => 'Le chiffre d\'affaires ne peut pas être négatif.',
            'employee_count.integer' => 'Le nombre d\'employés doit être un nombre entier.',
            'employee_count.min' => 'Le nombre d\'employés ne peut pas être négatif.',
            'max_users.integer' => 'Le nombre maximum d\'utilisateurs doit être un nombre entier.',
            'max_users.min' => 'Le nombre maximum d\'utilisateurs doit être au moins 1.',
            'max_users.max' => 'Le nombre maximum d\'utilisateurs ne peut pas dépasser 1000.',
            'max_charging_points.integer' => 'Le nombre maximum de points de charge doit être un nombre entier.',
            'max_charging_points.min' => 'Le nombre maximum de points de charge doit être au moins 1.',
            'max_charging_points.max' => 'Le nombre maximum de points de charge ne peut pas dépasser 10000.',
            'discount_percentage.numeric' => 'Le pourcentage de remise doit être un nombre.',
            'discount_percentage.min' => 'Le pourcentage de remise ne peut pas être négatif.',
            'discount_percentage.max' => 'Le pourcentage de remise ne peut pas dépasser 100%.',
            'credit_limit.numeric' => 'La limite de crédit doit être un nombre.',
            'credit_limit.min' => 'La limite de crédit ne peut pas être négative.',
        ];
    }
}