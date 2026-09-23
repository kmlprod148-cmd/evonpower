<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\BaseFormRequest;

class GroupStoreRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return auth()->user()->can('create_groups');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:public,private',
            'consumption_mode' => 'required|in:prepaid,postpaid',
            'description' => 'nullable|string',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'partner_id' => 'required|exists:partners,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages()
    {
        return [
            'name.required' => 'Le nom du groupe est obligatoire.',
            'name.max' => 'Le nom du groupe ne peut pas dépasser 255 caractères.',
            'type.required' => 'Le type de groupe est obligatoire.',
            'type.in' => 'Le type de groupe doit être public ou privé.',
            'consumption_mode.required' => 'Le mode de consommation est obligatoire.',
            'consumption_mode.in' => 'Le mode de consommation doit être prépayé ou postpayé.',
            'partner_id.required' => 'Le choix du partenaire est obligatoire.',
            'partner_id.exists' => 'Le partenaire sélectionné n\'existe pas.',
            'city.required' => 'La ville est obligatoire.',
            'city.max' => 'Le nom de la ville ne peut pas dépasser 255 caractères.',
            'address.required' => 'L\'adresse est obligatoire.',
            'address.max' => 'L\'adresse ne peut pas dépasser 255 caractères.',
            'postal_code.required' => 'Le code postal est obligatoire.',
            'postal_code.max' => 'Le code postal ne peut pas dépasser 20 caractères.',
            'country.required' => 'Le pays est obligatoire.',
            'country.max' => 'Le nom du pays ne peut pas dépasser 100 caractères.',
        ];
    }
}