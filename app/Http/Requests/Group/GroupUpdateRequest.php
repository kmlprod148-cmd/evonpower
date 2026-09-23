<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\BaseFormRequest;

class GroupUpdateRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return auth()->user()->can('edit_groups');
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
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages()
    {
        return [
            'name.required' => 'The group name is required.',
            'name.max' => 'The group name cannot exceed 255 characters.',
            'type.required' => 'The group type is required.',
            'type.in' => 'The group type must be either public or private.',
            'consumption_mode.required' => 'Le mode de consommation est obligatoire.',
            'consumption_mode.in' => 'Le mode de consommation doit être prépayé ou postpayé.',
            'business_profile_id.exists' => 'The selected partner does not exist.',
            'city.max' => 'The city name cannot exceed 255 characters.',
            'address.max' => 'The address cannot exceed 255 characters.',
            'postal_code.max' => 'The postal code cannot exceed 20 characters.',
            'country.max' => 'The country name cannot exceed 100 characters.',
        ];
    }
}